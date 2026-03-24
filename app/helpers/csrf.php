<?php
namespace App\Helpers;

class Csrf {
    public static function generate(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // empty() checks + unset after use to prevent replay attacks
    public static function verify(): void {
        $token = $_POST['csrf_token'] ?? '';

        if (
            empty($_SESSION['csrf_token']) ||
            empty($token) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            http_response_code(403);
            echo '<h3>403 - Invalid CSRF token.</h3>';
            exit;
        }

        unset($_SESSION['csrf_token']); // force regeneration on next request
    }

    public static function field(): string {
        return '<input type="hidden" name="csrf_token" value="' . self::generate() . '">';
    }
}