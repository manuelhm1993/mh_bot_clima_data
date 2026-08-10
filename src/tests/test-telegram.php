<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\TelegramNotifier;
use Dotenv\Dotenv;

$base_dir = base_dir();
$dotenv = Dotenv::createImmutable($base_dir);
$dotenv->safeLoad();

$telegram = new TelegramNotifier(
    getenv('TELEGRAM_BOT_TOKEN'),
    getenv('TELEGRAM_CHAT_ID')
);

$ok = $telegram->send('🤖 Prueba de conexión — si ves esto, el bot está vivo.');

echo $ok ? "Enviado correctamente.\n" : "Falló el envío.\n";