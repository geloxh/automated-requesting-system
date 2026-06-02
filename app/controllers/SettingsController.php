<?php
    class SettingsController {
        // ── GET /settings ────────────────────────────────────────────────────────── //
        public function index(): void {
            \App\Middleware\RoleMiddleware::requireRole(1); // only SysAdmin

            $settings = $this->getAllSettings();

            define('BASE_LOADED', true);
            ob_start();
            require __DIR__ . '/../../views/settings/index.php';
            $content = ob_get_clean();
            $pageTitle = 'Settings';
            require __DIR__ . '/../../views/layouts/base.php';
        
        }

        // ── POST /settings/general ───────────────────────────────────────────────── //
        public function updateGeneral(): void {
            \App\Middleware\RoleMiddleware::requireRole(1);
            \App\Helpers\Csrf::verify();

            $fields = [
                'app_name' => trim($_POST['app_name'] ?? ''),
                'app_url' => rtrim($_POST['app_url'] ?? ''),
                'app_timezone' => trim($_POST['app_timezone'] ?? 'Asia/Manila'),
                'app_env' => trim($_POST['app_env'] ?? 'local'),
                'items_per_page' => (int) ($_POST['items_per_page'] ?? 20),
                'session_lifetime' => (int) ($_POST['session_lifetime'] ?? 120),
            ];

            foreach ($fields as $key => $value) {
                $this->upsertSetting($key, $value);
            }

            $_SESSION['success'] = 'General settings saved successfully.';
            header('Location: /processing-system/public/settings');
            exit;
        }

        // ── POST /settings/mail ──────────────────────────────────────────────────── //
        public function updateMail(): void {
            \App\Middleware\RoleMiddleware::requireRole(1);
            \App\Helpers\Csrf::verify();

            $fields = [
                'mail_host' => trim($_POST['mail_host'] ?? ''),
                'mail_port' => (int) ($_POST['mail_port'] ?? 587),
                'mail_username' => trim($_POST['mail_username'] ?? ''),
                'mail_from_address' => trim($_POST['mail_from_address']  ?? ''),
                'mail_from_name' => trim($_POST['mail_from_name'] ?? ''),
                'mail_encryption' => trim($_POST['mail_encryption'] ?? 'tls'),
            ];

            // Only update password if a new one was actually typed
            if (!empty($_POST['mail_password'])) {
                $fields['mail_password'] = $_POST['mail_password'];
            }

            foreach ($fields as $key => $value) {
                $this->upsertSetting($key, (string) $value);
            }

            $_SESSION['success'] = 'Mail settings saved successfully.';
            header('Location: /processing-system/public/settings');
            exit;
        }

        // ── Helpers ──────────────────────────────────────────────────────────────── //

        /**
         * Read all rows from settings table into an associative array.
         */
        private function getAllSettings(): array {
            try {
                $rows = db()->query('SELECT `key`, `value` FROM settings')->fetchAlll(\PDO::FETCH_ASSOC);
                return array_column($rows, 'value', 'key');
            } catch (\Throwable $e) {
                return [];
            }
        }

        /**
         * INSERT ... ON DUPLICATE KEY UPDATE for a single setting row.
         */
        private function upsertSetting(string $key, string $value): void {
            $stmt = db()->prepare(
                'INSERT INTO settings (`key`, `value`) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = CURRENT_TIMESTAMP'
            );
            $stmt->execute([$key, $value]);
        }
    }