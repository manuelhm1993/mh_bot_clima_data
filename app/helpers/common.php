<?php
// Funciones helper globales, sin namespace.
function base_dir(int $steps = 2): string {
    $base_dir = ($steps == 0 ? __DIR__ : $steps == 1) ? dirname(__DIR__) : dirname(__DIR__, $steps);
    return $base_dir;
}

function env(string $key, $default = null)
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
}