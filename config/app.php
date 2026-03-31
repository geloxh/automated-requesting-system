<?php
// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// DB
require_once __DIR__ . '/database.php';

// Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Manila');