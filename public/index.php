<?php
$host = env('DB_HOST') ?: 'mh_clima_db'; // nombre del servicio en compose
$db   = env('DB_NAME') ?: 'mh_clima';
$user = env('DB_USER') ?: 'mh_clima_user';
$pass = env('DB_PASS') ?: '';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
    
    echo "<h1 style='color: green;'>¡Sistemas en línea, MHenriquez!</h1>";
    echo "<p>PHP 8.3.30-Apache y MySQL 8.4.3 se están comunicando nativamente a través de la red interna de Docker.</p>";
    
} catch (\PDOException $e) {
    echo "<h1 style='color: red;'>Fallo en la Matriz</h1>";
    echo "<p>Error de conexión: " . $e->getMessage() . "</p>";
}