<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\MailNotifier;
use Dotenv\Dotenv;

$base_dir = base_dir();
$dotenv = Dotenv::createImmutable($base_dir);
$dotenv->safeLoad();

$mailer = new MailNotifier(
    getenv('GMAIL_USER'),
    getenv('GMAIL_APP_PASSWORD'),
    array_filter(explode(',', getenv('GMAIL_TO'))),
    array_filter(explode(',', getenv('GMAIL_CC') ?: ''))
);

$ok = $mailer->send('🤖 Prueba de conexión — si ves esto, el bot está vivo.', '<h1>Éxito</h1>');

echo $ok ? "Enviado correctamente.\n" : "Falló el envío.\n";