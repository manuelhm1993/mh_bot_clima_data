<?php

// Este primer require NO puede usar base_dir() — todavía no existe.
// bin/ está un nivel debajo de la raíz del proyecto (src/), así que:
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\WeatherService;
use App\Services\HydrationCalculator;
use App\Services\TelegramNotifier;

use App\Repositories\RunLogRepository;

use Dotenv\Dotenv;

// A partir de aquí, common.php ya se cargó vía el autoloader "files",
// así que base_dir() ya existe y la puedes usar tranquilo.
$base_dir = base_dir();

$dotenv = Dotenv::createImmutable($base_dir);
$dotenv->safeLoad();  // en vez de ->load() ya que se usan dos entornos local/producción

$pdoFactory = require "$base_dir/config/database.php";
$pdo = $pdoFactory();

$weather = new WeatherService(
    (float) getenv('WEATHER_LAT'),
    (float) getenv('WEATHER_LON'),
    getenv('WEATHER_TIMEZONE')
);

$calculator = new HydrationCalculator();
$repository = new RunLogRepository($pdo);

$temperature = $weather->getTodayMaxTemperature();
$adults      = (int) getenv('HOUSEHOLD_ADULTS');
$result      = $calculator->calculate($temperature, $adults);

$today = date('Y-m-d');
$now   = date('Y-m-d H:i:s');

$logId = $repository->insertToday([
    'run_date'            => $today,
    'executed_at'         => $now,
    'city'                => getenv('WEATHER_CITY') ?: 'Maracaibo',
    'temperature_celsius' => $temperature,
    'liters_per_adult'    => $result['liters_per_adult'],
    'liters_baby'         => $result['liters_baby'],
    'total_liters'        => $result['total_liters'],
    'boils_needed'        => $result['boils_needed'],
]);

if ($logId === null) {
    echo "Ya existe un cálculo para hoy ($today). No se duplicó.\n";
    exit(0);
}

// ------------------------------------------ Resumen del CronJob

echo "=== Bot Clima — {$today} ===
Temperatura máxima pronosticada: {$temperature}°C
Litros por adulto: {$result['liters_per_adult']}L
Litros bebé: {$result['liters_baby']}L
Total del día: {$result['total_liters']}L
Hervidas necesarias: {$result['boils_needed']}
Log guardado con ID: {$logId}\n";

// ------------------------------------------ Notificaciones
$telegram = new TelegramNotifier(
    getenv('TELEGRAM_BOT_TOKEN'),
    getenv('TELEGRAM_CHAT_ID')
);

$mensaje = "🌡️ <b>Bot Clima — {$today}</b>\n\n"
    . "Temperatura máxima: <b>{$temperature}°C</b>\n"
    . "Litros por adulto: {$result['liters_per_adult']}L\n"
    . "Litros bebé: {$result['liters_baby']}L\n"
    . "<b>Total del día: {$result['total_liters']}L</b>\n"
    . "<b>Hervidas necesarias: {$result['boils_needed']}</b>\n\n"
    . "Ronda 1: ahora. Quedan " . ($result['boils_needed'] - 1) . " rondas más hoy.";

$telegramSent = $telegram->send($mensaje);

echo $telegramSent
    ? "Notificación Telegram enviada.\n"
    : "Notificación Telegram FALLÓ (revisar error_log).\n";