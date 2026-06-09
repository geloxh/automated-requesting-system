<?php
    namespace App\Controllers;

    /**
     * NotificationController
     * 
     * Handles:
     *  GET /notifications/unread - JSON list for the panel (called by base.php)
     *  POST /notifications/{id}/read - mark one notification read
     *  POST /notifications/read-all - mark all read for current user
     * 
     * Static helper:
     *  NotificationController::create() - called by FormController after each
     *  pipeline action to insert a notification row.
     * 
     */
    
    class NotificationController {
        // ── Routes ────────────────────────────────────────────────────

        /** GET /notifications/unread - returns JSON for the bell panel */
        public function unread(): void {
            $userId = (int) $_SESSION['user_id'];
            $rows = $this->fetchForUser($userId, 10);

            header('Content-Type: application/json');
            echo json_encode([
                'unread_count' => array_reduce($rows, fn($c, $r) => $c + (int)!$r['is_read'], 0), 
                'items' => $rows,
            ]);
            exit;
        }

        /** POST /notifications/{id}/read */
        public function markRead(int $id): void {
            \App\Helpers\Csrf::verify();
            $userId = (int) $_SESSION['user_id'];

            db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$id, $userId]);
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;
        }

        /** POST /notifications/read-all */
        public function markAllRead(): void {
            \App\Helpers\Csrf::verify();
            $userId = (int) $_SESSION['user_id'];

            db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$userId]);
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;
        }

        // ── Static helpers (called from FormController) ───────────────
 
        /**
         * Insert a notification for one user.
         *
         * @param int $userId Recipient employee id
         * @param string $message Plain-text notification message
         * @param string $type 'info' | 'success' | 'warning' | 'danger'
         * @param int|null $formId Related form id (for the link)
         */

        public static function create(
            int $userId, 
            string $message, 
            string $type = 'info', 
            ?int $formId = null
        ): void {
            try {
                $link = $formId
                    ? '/automated-requesting-system/public/forms/view/' . $formId
                    : null;

                db()->prepare(
                    'INSERT INTO notifications (user_id, form_id, type, message, link)
                    VALUES (?, ?, ?, ?, ?)'
                )->execute([$userId, $formId, $type, $message, $link]);
            } catch (\Throwable $e) {
                // Never crash a user action due to a  notification failure
                error_log('[NotificationController] Insert failed: ' . $e->getMessage());
            }
        }

        // ── Private ───────────────────────────────────────────────────
        private function fetchForUser(int $userId, int $limit = 10): array {
            $stmt = db()->prepare(
                'SELECT id, form_id, type, message, link, is_read, created_at
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?'
            );
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
    }