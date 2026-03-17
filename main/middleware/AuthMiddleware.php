<?php
class AuthMiddleware {
    public static function require(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: /processing-system/public/login');
            exit;
        }
    }

    public static function requireRole(int ...$roleIds): void {
        self::require();
        if (!in_array($_SESSION['role_id'], $roleIds, true)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}