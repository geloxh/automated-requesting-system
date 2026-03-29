<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController {

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        \App\Helpers\Csrf::verify();

        $ip  = $_SERVER['REMOTE_ADDR'];
        $key = 'login_attempts_' . hash('sha256', $ip);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }

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
            $_SESSION[$key]['count']++;
            $_SESSION['error'] = 'Invalid credentials or account inactive.';
            header('Location: /processing-system/public/login');
            exit;
        }

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

        $name     = trim(($_POST['firstname'] ?? '') . ' ' . ($_POST['lastname'] ?? ''));
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 2;

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

        $stmt = db()->prepare('SELECT id FROM employees WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Email already registered.';
            header('Location: /processing-system/public/register');
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare(
            'INSERT INTO employees (full_name, email, password_hash, role_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $passwordHash, $role]);

        $_SESSION['success'] = 'Registration successful. You can now log in.';
        header('Location: /processing-system/public/login');
        exit;
    }

    public function forgotPassword(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $email = trim($_POST['email'] ?? '');

        $stmt = db()->prepare('SELECT id FROM employees WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $employee = $stmt->fetch();

        // Always show same message to prevent email enumeration
        $_SESSION['status'] = 'If that email exists, a reset link has been sent.';

        if ($employee) {
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            // Invalidate old tokens
            db()->prepare('DELETE FROM password_reset_tokens WHERE employee_id = ?')
               ->execute([$employee['id']]);

            db()->prepare(
                'INSERT INTO password_reset_tokens (employee_id, token, expires_at) VALUES (?, ?, ?)'
            )->execute([$employee['id'], $token, $expiresAt]);

            $resetLink = $_ENV['APP_URL'] . '/reset-password?token=' . $token;

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $_ENV['MAIL_HOST'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['MAIL_USERNAME'];
                $mail->Password   = $_ENV['MAIL_PASSWORD'];
                $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
                $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;

                $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME'] ?? 'Processing System');
                $mail->addAddress($email);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "Click the link below to reset your password (expires in 1 hour):\n\n{$resetLink}";

                $mail->send();
            } catch (Exception $e) {
                // Silently fail — don't expose mail errors to user
            }
        }

        header('Location: /processing-system/public/forgot-password');
        exit;
    }

    public function updatePassword(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $token    = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirmation'] ?? '';

        if (empty($token) || empty($password)) {
            $_SESSION['error'] = 'Invalid request.';
            header('Location: /processing-system/public/login');
            exit;
        }

        if ($password !== $confirm) {
            $_SESSION['error'] = 'Passwords do not match.';
            header("Location: /processing-system/public/reset-password?token={$token}");
            exit;
        }

        if (strlen($password) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters.';
            header("Location: /processing-system/public/reset-password?token={$token}");
            exit;
        }

        $stmt = db()->prepare(
            'SELECT id, employee_id FROM password_reset_tokens
             WHERE token = ? AND used = 0 AND expires_at > NOW()'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if (!$row) {
            $_SESSION['error'] = 'Reset link is invalid or has expired.';
            header('Location: /processing-system/public/forgot-password');
            exit;
        }

        db()->prepare('UPDATE employees SET password_hash = ? WHERE id = ?')
           ->execute([password_hash($password, PASSWORD_DEFAULT), $row['employee_id']]);

        db()->prepare('UPDATE password_reset_tokens SET used = 1 WHERE id = ?')
           ->execute([$row['id']]);

        $_SESSION['success'] = 'Password updated. You can now log in.';
        header('Location: /processing-system/public/login');
        exit;
    }
}