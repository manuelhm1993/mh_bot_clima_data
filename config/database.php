<?php

return function (): PDO {
    $host = env('DB_HOST');
    $db   = env('DB_NAME');
    $user = env('DB_USER');
    $pass = env('DB_PASS');

    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
};