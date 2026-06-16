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

        if (
            empty($_SESSION['csrf_token']) ||
            empty($token) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            // Regenerate so the form works again after a failed attempt
            unset($_SESSION['csrf_token']);
            http_response_code(403);
            echo '<h3>403 - Invalid CSRF token.</h3><p><a href="javascript:history.back()">Go back and try again</a></p>';
            exit;
        }

        // Rotate after successful use to prevent replay attacks
        unset($_SESSION['csrf_token']);
    }

    public static function field(): string {
        return '<input type="hidden" name="csrf_token" value="' . self::generate() . '">';
    }
}