<?php

// Router pro PHP built-in server (lokální vývoj a CI):
//   php -S 127.0.0.1:8765 -t public scripts/dev-router.php
// Existující soubory v public/ servíruje přímo, zbytek jde přes Symfony.

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . '/../public' . $path)) {
    return false;
}

require __DIR__ . '/../public/index.php';
