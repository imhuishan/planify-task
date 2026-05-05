<?php
// Main router for Vercel Serverless Function
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($request_uri, '/');

// Define base directory
$base_dir = __DIR__ . '/..';

// Handle empty URI or index.php
if ($uri === '' || $uri === 'index.php') {
    require $base_dir . '/public/index.php';
    exit;
}

$path = $base_dir . '/' . $uri;

// If it's a directory, look for index.php
if (is_dir($path)) {
    $path = rtrim($path, '/') . '/index.php';
}

if (file_exists($path) && str_ends_with($path, '.php')) {
    require $path;
} else {
    http_response_code(404);
    echo "404 Not Found - Path: " . htmlspecialchars($uri);
}
