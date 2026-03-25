<?php
class AuthController {
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        \App\Helpers\Csrf::verify();

        // Rate limiting
        $ip  = $_SERVER['REMOTE_ADDR'];
        $key = 'login_attempts_' . md5($ip);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }

        // Reset counter after 15 minutes
        if (time() - $_SESSION[$key]['time'] > 900) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }

        if ($_SESSION[$key]['count'] >= 5) {
            $_SESSION['error'] = 'Too many login attempts. Try again in 15 minutes.';
            header('Location: /processing-system/public/login');
            exit;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = db()->prepare(
            'SELECT id, full_name, password_hash, role_id, is_active FROM employees WHERE email = ?'
        );
        $stmt->execute([$email]);
        $employee = $stmt->fetch();

        if (!$employee || !$employee['is_active'] || !password_verify($password, $employee['password_hash'])) {
            $_SESSION[$key]['count']++; // increment only on failure
            $_SESSION['error'] = 'Invalid credentials or account inactive.';
            header('Location: /processing-system/public/login');
            exit;
        }

        // Success — clear attempts and regenerate session
        unset($_SESSION[$key]);
        $_SESSION['user_id']   = $employee['id'];
        $_SESSION['user_name'] = $employee['full_name'];
        $_SESSION['role_id']   = $employee['role_id'];
        session_regenerate_id(true);

        header('Location: /processing-system/public/dashboard');
        exit;
    }

    public function logout(): void {
        session_destroy();
        header('Location: /processing-system/public/login');
        exit;
    }

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        \App\Helpers\Csrf::verify();

        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 2; // Default to employee

        // Validate input
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['error'] = 'All fields are required.';
            header('Location: /processing-system/public/register');
            exit;
        }

        if (strlen($password) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters long.';
            header('Location: /processing-system/public/register');
            exit;
        }

        // Check if email already exists
        $stmt = db()->prepare('SELECT id FROM employees WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Email already registered.';
            header('Location: /processing-system/public/register');
            exit;
        }

        // Hash password and insert new user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare(
            'INSERT INTO employees (full_name, email, password_hash, role_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $passwordHash, $role]);

        $_SESSION['success'] = 'Registration successful. You can now log in.';
        header('Location: /processing-system/public/login');
        exit;
    }
}