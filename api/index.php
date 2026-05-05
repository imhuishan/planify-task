<?php
// Main router for Vercel Serverless Function
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Clean up URI
$uri = ltrim($request_uri, '/');

if ($uri === '' || $uri === 'index.php') {
    require __DIR__ . '/../public/index.php';
} elseif (file_exists(__DIR__ . '/../' . $uri)) {
    require __DIR__ . '/../' . $uri;
} else {
    http_response_code(404);
    echo "404 Not Found";
}
