<?php
    /**
     * ToolsController
     *
     * Renders the internal utilities page (World Clock, Calculator,
     * Height & Weight Converter, Notes, File Converter) and exposes a
     * small JSON API for the Notes widget, which is the only tool here
     * that needs server-side persistence — everything else runs client
     * side in tools.js.
     */
    class ToolsController {

        // ── GET /tools ── //
        public function index(): void {
            \App\Middleware\AuthMiddleware::require();

            $userId = (int)($_SESSION['user_id'] ?? 0);
            $notes = $this->fetchNotes($userId);
            $leaveCredits = $this->leaveCreditsSummary($userId);

            define('BASE_LOADED', true);
            ob_start();
            require __DIR__ . '/../../views/tools/index.php';
            $content = ob_get_clean();
            $pageTitle = 'Tools';
            require __DIR__ . '/../../views/layouts/base.php';
        }

        // ── POST /tools/payslip/request ── //
        // Demo "Services" action — logs a self-notification so the
        // request shows up in the bell icon. Swap the body of this
        // method for real payroll logic once that data source exists;
        // the CSRF handling around it does not need to change.
        public function requestPayslip(): void {
            \App\Middleware\AuthMiddleware::require();
            \App\Helpers\Csrf::verify();

            $userId = (int)($_SESSION['user_id'] ?? 0);
            $period = trim($_POST['period'] ?? 'the selected period');

            try {
                $stmt = db()->prepare(
                    'INSERT INTO notifications (user_id, type, message, link)
                     VALUES (?, "info", ?, ?)'
                );
                $stmt->execute([
                    $userId,
                    "Payslip request received for {$period}. HR will process it shortly.",
                    url('tools'),
                ]);
            } catch (\Throwable $e) {
                // notifications table may not exist in older installs — non-fatal.
            }

            $_SESSION['success'] = 'Payslip request submitted for ' . $period . '.';
            header('Location: ' . url('tools#services'));
            exit;
        }

        // ── GET /tools/notes ── //
        // Returns the current user's notes as JSON (used to refresh the list).
        public function listNotes(): void {
            \App\Middleware\AuthMiddleware::require();
            $userId = (int)($_SESSION['user_id'] ?? 0);

            $this->json(['notes' => $this->fetchNotes($userId)]);
        }

        // ── POST /tools/notes ── //
        // "New note" always creates a blank note the user can then type
        // into — an empty title/content here is the expected starting
        // state, not an error condition.
        public function createNote(): void {
            \App\Middleware\AuthMiddleware::require();
            \App\Helpers\Csrf::verify();

            $userId = (int)($_SESSION['user_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $color = $this->safeColor($_POST['color'] ?? 'yellow');

            $stmt = db()->prepare(
                'INSERT INTO notes (user_id, title, content, color) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $title, $content, $color]);

            $this->json(['note' => $this->fetchNote((int)db()->lastInsertId(), $userId)], 201);
        }

        // ── POST /tools/notes/{id}/update ── //
        public function updateNote(int $id): void {
            \App\Middleware\AuthMiddleware::require();
            \App\Helpers\Csrf::verify();

            $userId = (int)($_SESSION['user_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $color = $this->safeColor($_POST['color'] ?? 'yellow');

            $stmt = db()->prepare(
                'UPDATE notes SET title = ?, content = ?, color = ?
                 WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$title, $content, $color, $id, $userId]);

            if ($stmt->rowCount() === 0) {
                $this->json(['error' => 'Note not found.'], 404);
                return;
            }

            $this->json(['note' => $this->fetchNote($id, $userId)]);
        }

        // ── POST /tools/notes/{id}/delete ── //
        public function deleteNote(int $id): void {
            \App\Middleware\AuthMiddleware::require();
            \App\Helpers\Csrf::verify();

            $userId = (int)($_SESSION['user_id'] ?? 0);

            $stmt = db()->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);

            $this->json(['deleted' => $stmt->rowCount() > 0]);
        }

        /**
         * Derives leave-credit usage from approved/completed leave_application
         * forms already in the `forms` table (form_type = 'leave_application').
         * Allotments are a placeholder policy default — adjust to match your
         * actual HR policy, or swap this for a real leave_credits table later.
         */
        private function leaveCreditsSummary(int $userId): array {
            $allotment = ['vacation' => 15, 'sick' => 15, 'parental' => 7, 'other' => 5];
            $used = ['vacation' => 0, 'sick' => 0, 'parental' => 0, 'other' => 0];

            if ($userId === 0) return $this->buildLeaveCredits($allotment, $used);

            try {
                $stmt = db()->prepare(
                    "SELECT data FROM forms
                     WHERE submitted_by = ? AND form_type = 'leave_application'
                     AND status IN ('completed', 'final_approved')"
                );
                $stmt->execute([$userId]);
                foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $json) {
                    $data = json_decode((string)$json, true) ?: [];
                    $type = $data['leave_type'] ?? 'other';
                    if (!isset($used[$type])) $type = 'other';
                    $used[$type] += (float)($data['num_of_leave'] ?? 0);
                }
            } catch (\Throwable $e) {
                // forms table not reachable — fall back to zeroed usage.
            }

            return $this->buildLeaveCredits($allotment, $used);
        }

        private function buildLeaveCredits(array $allotment, array $used): array {
            $out = [];
            foreach ($allotment as $type => $total) {
                $out[$type] = [
                    'label' => ucfirst($type) . ' Leave',
                    'total' => $total,
                    'used' => min($used[$type], $total),
                    'remaining' => max(0, $total - $used[$type]),
                ];
            }
            return $out;
        }

        // ── Private helpers ── //

        private function fetchNotes(int $userId): array {
            if ($userId === 0) return [];
            try {
                $stmt = db()->prepare(
                    'SELECT id, title, content, color, created_at, updated_at
                     FROM notes WHERE user_id = ? ORDER BY updated_at DESC'
                );
                $stmt->execute([$userId]);
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                // Table may not exist yet (migration not run).
                return [];
            }
        }

        private function fetchNote(int $id, int $userId): ?array {
            $stmt = db()->prepare(
                'SELECT id, title, content, color, created_at, updated_at
                 FROM notes WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$id, $userId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        }

        private function safeColor(string $color): string {
            $allowed = ['yellow', 'pink', 'blue', 'green', 'purple'];
            return in_array($color, $allowed, true) ? $color : 'yellow';
        }

        private function json(array $data, int $status = 200): void {
            http_response_code($status);
            header('Content-Type: application/json');
            echo json_encode($data);
            exit;
        }
    }
