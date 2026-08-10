<?php
// Funciones helper globales, sin namespace.
function base_dir(): string {
    $base_dir = dirname(__DIR__, 2);
    return $base_dir;
}