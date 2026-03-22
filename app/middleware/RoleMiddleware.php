<?php
    namespace App\Middleware;

    class RoleMiddleware {
        public static function requireRole(int $roleId): void {
            if (($_SESSION['role_id'] ?? 0) != $roleId) {
                http_response_code(403);
                echo '<h3>403 - Access Denied</h3>';
                exit;
            }
        }
    }