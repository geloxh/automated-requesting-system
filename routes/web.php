<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/processing-system/public', '', $uri);

match($uri) {
    '/', '/dashboard' => require __DIR__ . '/../views/layouts/dashboard.php',
    '/login' => require __DIR__ . '/../views/auth/login.php',
    default => http_response_code(404) && require __DIR__ . '/../views/layouts/404.php',
};