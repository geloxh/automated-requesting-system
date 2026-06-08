<?php
    class SettingsController {

        // ── GET /settings ────────────────────────────────────────────────────────── //
        public function index(): void {
            // All authenticated users can view settings; sensitive tabs are
            // hidden in the view for non-admins (role guard per POST action).
            \App\Middleware\AuthMiddleware::require();

            $settings = $this->getAllSettings();
            $isSysAdmin = ($_SESSION['role_id'] ?? 0) == 1;

            define('BASE_LOADED', true);
            ob_start();
            require __DIR__ . '/../../views/settings/index.php';
            $content = ob_get_clean();
            $pageTitle = 'Settings';
            require __DIR__ . '/../../views/layouts/base.php';
        }

        // ── POST /settings/general ───────────────────────────────────────────────── //
        // SysAdmin only: app_name, app_url, timezone, env, pagination, session
        public function updateGeneral(): void {
            \App\Middleware\RoleMiddleware::requireRole(1);
            \App\Helpers\Csrf::verify();

            $fields = [
                'app_name' => trim($_POST['app_name'] ?? ''),
                'app_url' => rtrim($_POST['app_url'] ?? ''),
                'app_timezone' => trim($_POST['app_timezone'] ?? 'Asia/Manila'),
                'app_env' => trim($_POST['app_env'] ?? 'local'),
                'items_per_page' => (int)($_POST['items_per_page'] ?? 20),
                'session_lifetime' => (int)($_POST['session_lifetime'] ?? 120),
            ];

            foreach ($fields as $key => $value) {
                $this->upsertSetting($key, (string)$value);
            }

            $_SESSION['success'] = 'General settings saved successfully.';
            header('Location: /processing-system/public/settings');
            exit;
        }

        // ── POST /settings/mail ──────────────────────────────────────────────────── //
        // SysAdmin only
        public function updateMail(): void {
            \App\Middleware\RoleMiddleware::requireRole(1);
            \App\Helpers\Csrf::verify();

            $fields = [
                'mail_host' => trim($_POST['mail_host'] ?? ''),
                'mail_port' => (int)($_POST['mail_port'] ?? 587),
                'mail_username' => trim($_POST['mail_username'] ?? ''),
                'mail_from_address' => trim($_POST['mail_from_address'] ?? ''),
                'mail_from_name' => trim($_POST['mail_from_name'] ?? ''),
                'mail_encryption' => trim($_POST['mail_encryption'] ?? 'tls'),
            ];

            if (!empty($_POST['mail_password'])) {
                $fields['mail_password'] = $_POST['mail_password'];
            }

            foreach ($fields as $key => $value) {
                $this->upsertSetting($key, (string)$value);
            }

            $_SESSION['success'] = 'Mail settings saved successfully.';
            header('Location: /processing-system/public/settings');
            exit;
        }

        // ── POST /settings/appearance ────────────────────────────────────────────── //
        // All authenticated users (theme preferences)
        public function updateAppearance(): void {
            \App\Middleware\AuthMiddleware::require();
            \App\Helpers\Csrf::verify();

            $allowed_colors = ['blue', 'purple', 'green', 'orange'];
            $color = trim($_POST['theme_color'] ?? 'blue');

            $fields = [
                'theme_color' => in_array($color, $allowed_colors, true) ? $color : 'blue',
                'theme_mode' => trim($_POST['theme_mode'] ?? 'light') === 'dark' ? 'dark' : 'light',
                'sidebar_collapsed' => isset($_POST['sidebar_collapsed']) ? '1' : '0',
            ];

            foreach ($fields as $key => $value) {
                $this->upsertSetting($key, $value);
            }

            $_SESSION['success'] = 'Appearance settings saved.';
            header('Location: /processing-system/public/settings#appearance');
            exit;
        }

        // ── POST /settings/notifications ─────────────────────────────────────────── //
        // All authenticated users
        public function updateNotifications(): void {
            \App\Middleware\AuthMiddleware::require();
            \App\Helpers\Csrf::verify();

            $fields = [
                'notify_on_submit' => isset($_POST['notify_on_submit']) ? '1' : '0',
                'notify_on_approval' => isset($_POST['notify_on_approval']) ? '1' : '0',
                'notify_on_rejection' => isset($_POST['notify_on_rejection']) ? '1' : '0',
                'notify_on_completion' => isset($_POST['notify_on_completion']) ? '1' : '0',
            ];

            foreach ($fields as $key => $value) {
                $this->upsertSetting($key, $value);
            }

            $_SESSION['success'] = 'Notification preferences saved.';
            header('Location: /processing-system/public/settings#notifications');
            exit;
        }

        // ── POST /settings/storage ───────────────────────────────────────────────── //
        // SysAdmin only (path changes are sensitive)
        public function updateStorage(): void {
            \App\Middleware\RoleMiddleware::requireRole(1);
            \App\Helpers\Csrf::verify();

            $uploadPath = trim($_POST['upload_path'] ?? 'public/uploads');
            // Strip any leading slash / path traversal
            $uploadPath = ltrim(str_replace(['..', '//'], '', $uploadPath), '/');

            $rawTypes = trim($_POST['allowed_file_types'] ?? 'pdf,jpg,png,docx');
            // Normalise: lowercase, strip spaces, remove dots
            $types = implode(',', array_filter(array_map(
                fn($t) => preg_replace('/[^a-z0-9]/', '', strtolower(trim($t))),
                explode(',', $rawTypes)
            )));

            $fields = [
                'upload_path' => $uploadPath,
                'max_file_size_mb' => max(1, min(100, (int)($_POST['max_file_size_mb'] ?? 10))),
                'allowed_file_types' => $types ?: 'pdf,jpg,png,docx',
            ];

            foreach ($fields as $key => $value) {
                $this->upsertSetting($key, (string)$value);
            }

            $_SESSION['success'] = 'Storage settings saved.';
            header('Location: /processing-system/public/settings#storage');
            exit;
        }

        // ── Helpers ──────────────────────────────────────────────────────────────── //

        private function getAllSettings(): array {
            try {
                // Fixed typo: fetchAlll → fetchAll
                $rows = db()->query('SELECT `key`, `value` FROM settings')->fetchAll(\PDO::FETCH_ASSOC);
                return array_column($rows, 'value', 'key');
            } catch (\Throwable $e) {
                return [];
            }
        }

        private function upsertSetting(string $key, string $value): void {
            $stmt = db()->prepare(
                'INSERT INTO settings (`key`, `value`) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = CURRENT_TIMESTAMP'
            );
            $stmt->execute([$key, $value]);
        }
    }