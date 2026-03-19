<?php
class AuthController {
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        \App\Helpers\Csrf::verify();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = db()->prepare(
            'SELECT id, full_name, password_hash, role_id, is_active FROM employees WHERE email = ?'
        );
        $stmt->execute([$email]);
        $employee = $stmt->fetch();

        if (!$employee || !$employee['is_active'] || !password_verify($password, $employee['password_hash'])) {
            $_SESSION['error'] = 'Invalid credentials or account inactive.';
            header('Location: /processing-system/public/login');
            exit;
        }

        $_SESSION['user_id'] = $employee['id'];
        $_SESSION['user_name'] = $employee['full_name'];
        $_SESSION['role_id'] = $employee['role_id'];

        header('Location: /processing-system/public/dashboard');
        exit;
    }

    public function logout(): void {
        session_destroy();
        header('Location: /processing-system/public/login');
        exit;
    }
}