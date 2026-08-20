<?php
// 1. Detección de Entorno (Docker vs Producción)
$autoloadPath = file_exists(__DIR__ . '/../vendor/autoload.php')
    ? __DIR__ . '/../vendor/autoload.php'  // Ruta Producción (cPanel)
    : '/var/www/app/vendor/autoload.php';  // Ruta Local (Docker)

require $autoloadPath;

use Dotenv\Dotenv;

$base_dir = file_exists(__DIR__ . '/../.env')
    ? dirname(__DIR__)
    : '/var/www/app';

// 2. Cargar variables de entorno
$dotenv = Dotenv::createImmutable($base_dir);
$dotenv->safeLoad();

// 3. Conexión a la DB
$pdoFactory = require "$base_dir/config/database.php";
$pdo = $pdoFactory();

// 4. Extracción de datos
$sql = "SELECT run_date, temperature_celsius, total_liters, boils_needed, notification_status 
    FROM run_logs 
    ORDER BY run_date DESC 
    LIMIT 7
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$logs = array_reverse($stmt->fetchAll());

// 5. Respuesta HTTP pura
header('Content-Type: application/json');
echo json_encode($logs);