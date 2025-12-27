<?php
// Simple router for PHP built-in server
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$fullPath = __DIR__ . $path;
if ($path !== '/' && file_exists($fullPath) && !is_dir($fullPath)) {
    return false; // serve static files
}

if (str_starts_with($path, '/api/')) {
    require __DIR__ . '/api/index.php';
    return true;
}

require __DIR__ . '/admin/index.php';
