<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/FormController.php';
require_once __DIR__ . '/../app/controllers/EmployeeController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/processing-system/public', '', $uri) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

// PUBLIC - Auth routes (No auth required)
if ($uri === '/login') {
    if ($method === 'POST') (new AuthController)->login();
    else require __DIR__ . '/../views/auth/login.php';
    exit;
}

// ---------------------------------------------------------------
// AUTH GATGE = everything below requires login
// ---------------------------------------------------------------
\App\Middleware\AuthMiddleware::require();

if ($uri === '/logout' && $method === 'POST') {
    (new AuthController)->logout();
    exit;
}

// Dashboard
if ($uri === '/' || $uri === '/dashboard') {
    require __DIR__ . '/../views/layouts/dashboard.php';
    exit;
}

// ---------------------------------------------------------------
// Forms
// ---------------------------------------------------------------

// POST /forms/{id}/approve
if (preg_match('#^/forms/(\d+)/approve$#', $uri, $m) && $method === 'POST') {
    (new FormController)->approve((int)$m[1]);
    exit;
}

// POST /forms/{id}/reject
if (preg_match('#^/forms/(\d+)/reject$#', $uri, $m) && $method === 'POST') {
    (new FormController)->reject((int)$m[1]);
    exit;
}

// GET /forms/view/{id}
if (preg_match('#^/forms/view/(\d+)$#', $uri, $m)) {
    (new FormController)->show((int)$m[1]);
    exit;
}

// GET|POST /forms/{slug}/create
if (preg_match('#^/forms/([\w-]+)/create$#', $uri, $m)) {
    (new FormController)->create($m[1]);
    exit;
}

// GET /forms/{slug}
if (preg_match('#^/forms/([\w-]+)$#', $uri, $m)) {
    (new FormController)->index($m[1]);
    exit;
}

// ---------------------------------------------------------------
// ADMIN
// ---------------------------------------------------------------
if ($uri === '/employees') {
    \App\Middleware\RoleMiddleware::requireRole(1);
    (new EmployeeController)->index();
    exit;
}

if ($uri === '/employees/create') {
    \App\Middleware\RoleMiddleware::requireRole(1);
    (new EmployeeController)->create();
    exit;
}

// ---------------------------------------------------------------
// 404
// ---------------------------------------------------------------
http_response_code(404);
echo '<h3>404 - Page not found</h3>';