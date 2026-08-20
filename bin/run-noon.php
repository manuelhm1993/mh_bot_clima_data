<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Repositories\RunLogRepository;
use App\Services\TelegramNotifier;
use App\Services\MailNotifier;
use Dotenv\Dotenv;

$base_dir = base_dir();
$dotenv = Dotenv::createImmutable($base_dir);
$dotenv->safeLoad();

$pdoFactory = require "$base_dir/config/database.php";
$pdo = $pdoFactory();

$repository = new RunLogRepository($pdo);
$today = date('Y-m-d');

$log = $repository->findToday($today);

if ($log === null) {
    echo "No hay registro de hoy ($today). El cron de las 6am no corrió aún o falló.\n";
    exit(1);
}

$remainingBoils = (int) $log['boils_needed'] - 1; // ya se hizo la ronda 1 en la mañana

$mensaje = "⏰ <b>Recordatorio Bot Clima</b>\n\n"
    . "Quedan <b>{$remainingBoils}</b> hervidas por hacer hoy.\n"
    . "Total del día: {$log['total_liters']}L a {$log['temperature_celsius']}°C.";

$telegram = new TelegramNotifier(
    env('TELEGRAM_BOT_TOKEN'),
    env('TELEGRAM_CHAT_ID')
);
$telegramSent = $telegram->send($mensaje);

$mailer = new MailNotifier(
    env('GMAIL_USER'),
    env('GMAIL_APP_PASSWORD'),
    array_filter(explode(',', env('GMAIL_TO'))),
    array_filter(explode(',', env('GMAIL_CC') ?: ''))
);
$mailSent = $mailer->send(
    "Recordatorio: {$remainingBoils} hervidas pendientes",
    "<h2>⏰ Recordatorio</h2><p>Quedan <strong>{$remainingBoils}</strong> hervidas por hacer hoy.</p>"
);

echo $telegramSent ? "Telegram OK.\n" : "Telegram FALLÓ.\n";
echo $mailSent ? "Gmail OK.\n" : "Gmail FALLÓ.\n";