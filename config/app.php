<?php
// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB
require_once __DIR__ . '/database.php';

// Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Manila');