<?php
    namespace App\Controllers;

    use PDO;

    class ApprovalController {
        /**
         * GET /approvals
         * Displays a list of forms awaiting action from the current user.
         *
         * FIX: All future approval rows are seeded as 'pending' at form creation
         * time, so every approver in the pipeline had a pending row from day one.
         * The previous query filtered only on (status='pending' AND approver_id=X),
         * which meant Role 4 and Role 6 saw forms in their inbox the moment the
         * form was submitted — well before their turn.
         *
         * The fix adds a correlated subquery:
         *
         *   a.sequence = (
         *     SELECT MIN(sequence) FROM approvals
         *     WHERE form_id = a.form_id AND status = 'pending'
         *   )
         *
         * This ensures an approver's row is only surfaced when their sequence is
         * the LOWEST remaining pending sequence for that form — i.e. it is
         * genuinely their turn.  The server-side status guard in processApproval()
         * already blocks out-of-order actions; this fix closes the UX gap so
         * approvers never see misleading inbox entries in the first place.
         */
        public function inbox(): void {
            $userId = (int) $_SESSION['user_id'];
            $roleId = (int) $_SESSION['role_id'];

            // Active-sequence subquery — reused in both the admin and role-scoped
            // branches so the logic is defined once.
            $activeSeqSql = 'a.sequence = (
                SELECT MIN(a2.sequence) FROM approvals a2
                WHERE a2.form_id = a.form_id AND a2.status = \'pending\'
            )';

            $sql = "SELECT f.id, f.form_type, f.created_at,
                           e.full_name as owner_name, e.department,
                           a.sequence, a.status as step_status,
                           DATEDIFF(NOW(), f.created_at) as days_pending
                    FROM approvals a
                    JOIN forms f ON f.id = a.form_id
                    JOIN employees e ON e.id = f.submitted_by
                    WHERE a.status = 'pending'
                    AND {$activeSeqSql}";

            if ($roleId !== 1) {
                // Non-admins: further restrict to rows assigned to this user
                // and exclude terminal form states.
                $sql .=
                "
                AND a.approver_id = :userId
                AND f.status NOT IN ('draft', 'completed', 'rejected', 'cancelled')
                ";
            }

        // Priority sort toggle: overdue items (3+ days) float to top by default
        $currentSort = $_GET['sort'] ?? 'priority';
        if ($currentSort === 'priority') {
            $sql .= " ORDER BY (DATEDIFF(NOW(), f.created_at) >= 3) DESC, f.created_at ASC";
        } else {
            $sql .= " ORDER BY f.created_at ASC";
        }

            $stmt = db()->prepare($sql);
            if ($roleId !== 1) {
                $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            }
            $stmt->execute();
            $approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formLabel = \App\Helpers\FormLabels::all();

            $pageTitle = 'Approval Inbox';
        $this->render('approvals/inbox', compact('approvals', 'formLabel', 'pageTitle', 'currentSort'));
        }

        private function render(string $view, array $vars = []): void {
            $allowed = [
                'approvals/inbox',
            ];

            if (!in_array($view, $allowed, true)) {
                $this->renderError(404, 'Not Found', 'The requested view does not exist.');
            }

            $basePath = realpath(__DIR__ . '/../../views');
            $fullPath = realpath($basePath . '/' . $view . '.php');

            if ($fullPath === false || strpos($fullPath, $basePath) !== 0) {
                $this->renderError(403, 'Access Denied', 'You do not have permission to perform this action.');
            }

            define('BASE_LOADED', true);
            extract($vars);
            $uri = $_SERVER['REQUEST_URI'];

            ob_start();
            require $fullPath;
            $content = ob_get_clean();

            require __DIR__ . '/../../views/layouts/base.php';
        }

        private function renderError(int $code, string $title, string $message): never {
            http_response_code($code);
            $errorCode = $code;
            $errorTitle = $title;
            $errorMessage = $message;
            $pageTitle = "{$code} — {$title}";
            define('BASE_LOADED', true);
            ob_start();
            require __DIR__ . '/../../views/error/error.php';
            $content = ob_get_clean();
            require __DIR__ . '/../../views/layouts/base.php';
            exit;
        }
    }