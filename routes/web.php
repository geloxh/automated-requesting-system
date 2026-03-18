<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/processing-system/public', '', $uri) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

// Auth routes
if ($uri === '/login') {
    if ($method === 'POST') (new AuthController)->login();
    else require __DIR__ . '/../views/auth/login.php';
    exit;
}

if ($uri === '/logout' && $method === 'POST') {
    (new AuthController)->logout();
    exit;
}

// All routes below require auth
\App\Middleware\AuthMiddleware::require();

// Dashboard
if ($uri === '/' || $uri === '/dashboard') {
    require __DIR__ . '/../views/layouts/dashboard.php';
    exit;
}

// Forms: /forms/{slug}, /forms/{slug}/create, /forms/view/{id}
if (preg_match('#^/forms/view/(\d+)$#', $uri, $m)) {
    // TODO: (new FormController)->show((int)$m[1]);
    exit;
}

if (preg_match('#^/forms/([\w-]+)/create$#', $uri, $m)) {
    // TODO: (new FormController)->create($m[1]);
    exit;
}

if (preg_match('#^/forms/([\w-]+)$#', $uri, $m)) {
    // TODO: (new FormController)->index($m[1]);
    exit;
}

// Admin
if ($uri === '/employees') {
    // TODO: (new EmployeeController)->index();
    exit;
}

// 404
http_response_code(404);
echo '<h3>404 - Page not found</h3>';