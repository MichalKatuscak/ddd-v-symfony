<?php
// Dev router for PHP built-in server: serve existing static files with correct
// MIME, route everything else through Symfony's front controller.
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri) && is_file(__DIR__ . '/public' . $uri)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
require __DIR__ . '/public/index.php';
