<?php
    namespace App\Helpers;

    class Csrf {
        public static function generate(): string {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['csrf_token'];
        }

        public static function verify(): void {
            $token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                http_response_code(403);
                echo '<h3>403 - INvalid CSRF token.</h3>';
                exit;
            }
        }

        public static function field(): string {
            return '<input type="hidden" name="csrf_token" value="' . self::generate() . '">';
        }
    }