<?php
namespace App\Middleware;

class AuthMiddleware
{
    public static function require(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (empty($_SESSION['user_id'])) {
            header('Location: /automated-requesting-system/public/login');
            exit;
        }
    }
}