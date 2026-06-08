<?php
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    require_once __DIR__ . '/../app/controllers/SettingsController.php';
    require_once __DIR__ . '/../app/controllers/FormController.php';
    require_once __DIR__ . '/../app/controllers/ApprovalController.php';
    require_once __DIR__ . '/../app/controllers/EmployeeController.php';

    require_once __DIR__ . '/../app/controllers/DepartmentController.php';

    require_once __DIR__ . '/../app/controllers/NotificationController.php';
    require_once __DIR__ . '/../app/Helpers/EmployeeCode.php';

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = str_replace('/processing-system/public', '', $uri) ?: '/';
    $method = $_SERVER['REQUEST_METHOD'];

    // PUBLIC - Auth routes (No auth required)
    if ($uri === '/login') {
        if ($method === 'POST') (new AuthController)->login();
        else require __DIR__ . '/../views/auth/login.php';
        exit;
    }

    if ($uri === '/register') {
        if ($method === 'POST') (new AuthController)->register();
        else require __DIR__ . '/../views/auth/register.php';
        exit;
    }

    if ($uri === '/forgot-password') {
        if ($method === 'POST') (new AuthController)->forgotPassword();
        else require __DIR__ . '/../views/auth/authchange/forgot_password.php';
        exit;
    }

    if ($uri === '/reset-password') {
        require __DIR__ . '/../views/auth/authchange/reset_password.php';
        exit;
    }

    if ($uri === '/update-password' && $method === 'POST') {
        (new AuthController)->updatePassword();
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



    // Multilevel Approval Routes
   $approvalActions = ['submit', 'checker-approval', 'review-approval', 'process-approval', 'evaluation-approval', 'grant-approval', 'complete'];

    foreach ($approvalActions as $action) {
        $safeAction = preg_quote($action, '#');
        if (preg_match("#^/forms/(\d+)/approve/{$safeAction}$#", $uri, $m) && $method === 'POST') {
            (new FormController)->approve((int)$m[1], $action);
            exit;
        }
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

    // GET|POST /forms/{slug}
    if (preg_match('#^/forms/([\w-]+)$#', $uri, $m)) {
        if ($method === 'POST') {
            (new FormController)->create($m[1]);
        } else {
            (new FormController)->index($m[1]);
        }
        exit;
    }

    // ---------------------------------------------------------------
    // ADMIN
    // ---------------------------------------------------------------

    // employees
    if ($uri === '/employees') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new EmployeeController)->index();
        exit;
    }

    // GET|POST /employees/create
    if ($uri === '/employees/create') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new EmployeeController)->create();
        exit;
    }

    // GET /employees/edit/{id}
    if (preg_match('#^/employees/edit/(\d+)$#', $uri, $m)) {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new EmployeeController)->edit((int)$m[1]);
        exit;
    }

    // POST /employees/update/{id}
    if (preg_match('#^/employees/update/(\d+)$#', $uri, $m) && $method === 'POST') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new EmployeeController)->update((int)$m[1]);
        exit;
    }

    // POST /employees/{id}/delete
    if (preg_match('#^/employees/(\d+)/delete$#', $uri, $m) && $method === 'POST') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new EmployeeController)->delete((int)$m[1]);
        exit;
    }

    // POST /employees/{id}/status
    if (preg_match('#^/employees/(\d+)/status$#', $uri, $m) && $method === 'POST') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new EmployeeController)->updateStatus((int)$m[1]);
        exit;
    }

    // POST /forms/{id}/admin-approve  and  /forms/{id}/admin-reject
    if (preg_match('#^/forms/(\d+)/admin-(approve|reject)$#', $uri, $m) && $method === 'POST') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new EmployeeController)->actAsApprover((int)$m[1], $m[2]  === 'approve' ? 'approved' : 'rejected');
        exit;
    }

    // ---------------------------------------------------------------
    // DEPARTMENTS (SysAdmin only)
    // ---------------------------------------------------------------
    if ($uri === '/departments') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new DepartmentController)->index();
        exit;
    }
    if ($uri === '/departments/create' && $method === 'POST') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new DepartmentController)->store();
        exit;
    }
    if (preg_match('#^/departments/(\d+)/update$#', $uri, $m) && $method === 'POST') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new DepartmentController)->update((int)$m[1]);
        exit;
    }
    if (preg_match('#^/departments/(\d+)/delete$#', $uri, $m) && $method === 'POST') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new DepartmentController)->delete((int)$m[1]);
        exit;
    }
    if (preg_match('#^/departments/(\d+)/members$#', $uri, $m) && $method === 'GET') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new DepartmentController)->members((int)$m[1]);
        exit;
    }

    // ---------------------------------------------------------------
    // Profile
    // ---------------------------------------------------------------
    if ($uri === '/profile/avatar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        (new EmployeeController)->uploadAvatar();
        exit;
    }
    
    if ($uri === '/profile') {
        (new EmployeeController)->profile();
        exit;
    }

    // GET /approvals — pending approval inbox
    if ($uri === '/approvals') {
        \App\Middleware\RoleMiddleware::requireAnyRole([1, 2, 3, 4, 5, 6]); // extend for roles 4,5,6 as needed
        (new \App\Controllers\ApprovalController)->inbox();
        exit;
    }

    // GET /my-submissions — current user's own forms
    if ($uri === '/my-submissions') {
        (new FormController)->mySubmissions();
        exit;
    }

    // GET /requests — all requests (admin only)
    if ($uri === '/requests') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new FormController)->allRequests();
        exit;
    }

    // ---------------------------------------------------------------
    // Notifications
    // ---------------------------------------------------------------
    if ($uri === '/notifications/unread' && $method === 'GET') {
        (new \App\Controllers\NotificationController)->unread();
        exit;
    }

    if ($uri === '/notifications/read-all' && $method === 'POST') {
        (new \App\Controllers\NotificationController)->markAllRead();
        exit;
    }

    if (preg_match('#^/notifications/(\d+)/read$#', $uri, $m) && $method === 'POST') {
        (new \App\Controllers\NotificationController)->markRead((int)$m[1]);
        exit;
    }

    // ---------------------------------------------------------------
    // Settings (SysAdmin only)
    // ---------------------------------------------------------------
    if ($uri === '/settings') {
        // All authenticated users — tab visibility is controlled in the view
        (new SettingsController)->index();
        exit;
    }

    if ($uri === '/settings/general' && $method === 'POST') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new SettingsController)->updateGeneral();
        exit;
    }

    if ($uri === '/settings/mail' && $method === 'POST') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new SettingsController)->updateMail();
        exit;
    }

    if ($uri === '/settings/appearance' && $method === 'POST') {
        (new SettingsController)->updateAppearance();
        exit;
    }

    if ($uri === '/settings/notifications' && $method === 'POST') {
        (new SettingsController)->updateNotifications();
        exit;
    }

    if ($uri === '/settings/storage' && $method === 'POST') {
        \App\Middleware\RoleMiddleware::requireRole(1);
        (new SettingsController)->updateStorage();
        exit;
    }

    // GET /search?q= - typeahead JSON endpoint
    if ($uri === '/search' && $method === 'GET') {
        (new FormController)->search();
        exit;
    }

    // ---------------------------------------------------------------
    // 404
    // ---------------------------------------------------------------
    http_response_code(404);
    echo '<h3>404 - Page not found</h3>';