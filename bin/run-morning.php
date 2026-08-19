<?php

// Este primer require NO puede usar base_dir() — todavía no existe.
// bin/ está un nivel debajo de la raíz del proyecto (src/), así que:
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\WeatherService;
use App\Services\HydrationCalculator;
use App\Services\TelegramNotifier;
use App\Services\MailNotifier;

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
    (float) env('WEATHER_LAT'),
    (float) env('WEATHER_LON'),
    env('WEATHER_TIMEZONE')
);

$calculator = new HydrationCalculator();
$repository = new RunLogRepository($pdo);

$temperature = $weather->getTodayMaxTemperature();
$adults      = (int) env('HOUSEHOLD_ADULTS');
$result      = $calculator->calculate($temperature, $adults);

$today = date('Y-m-d');
$now   = date('Y-m-d H:i:s');

$logId = $repository->insertToday([
    'run_date'            => $today,
    'executed_at'         => $now,
    'city'                => env('WEATHER_CITY') ?: 'Maracaibo',
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
$total_dia = round($result['total_liters'], 2);

echo "=== Bot Clima — {$today} ===
Temperatura máxima pronosticada: {$temperature}°C
Litros por adulto: {$result['liters_per_adult']}L
Litros bebé: {$result['liters_baby']}L
Total del día: {$total_dia}L
Hervidas necesarias: {$result['boils_needed']}
Log guardado con ID: {$logId}\n";

// ------------------------------------------ Notificaciones
//
// ------------------------------------------ Telegram
$telegram = new TelegramNotifier(
    env('TELEGRAM_BOT_TOKEN'),
    env('TELEGRAM_CHAT_ID')
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

// ------------------------------------------ Correo
$mailer = new MailNotifier(
    env('GMAIL_USER'),
    env('GMAIL_APP_PASSWORD'),
    array_filter(explode(',', env('GMAIL_TO'))),
    array_filter(explode(',', env('GMAIL_CC') ?: ''))
);

$htmlBody = "<h2>🌡️ Bot Clima — {$today}</h2>"
    . "<p>Temperatura máxima: <strong>{$temperature}°C</strong></p>"
    . "<p>Litros por adulto: {$result['liters_per_adult']}L</p>"
    . "<p>Litros bebé: {$result['liters_baby']}L</p>"
    . "<p><strong>Total del día: {$result['total_liters']}L</strong></p>"
    . "<p><strong>Hervidas necesarias: {$result['boils_needed']}</strong></p>"
    . "<p>Ronda 1: ahora. Quedan " . ($result['boils_needed'] - 1) . " rondas más hoy.</p>";

$mailSent = $mailer->send("Bot Clima {$today} — {$result['boils_needed']} hervidas", $htmlBody);

echo $mailSent
    ? "Notificación Gmail enviada.\n"
    : "Notificación Gmail FALLÓ (revisar error_log).\n";