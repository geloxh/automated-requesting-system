<!-- this is the orginal FormController -->
<?php
    class FormController {
        private array $typeMap = [
            'advance-payment' => 'advance_payment',
            'overtime' => 'overtime_authorization',
            'request-payment' => 'request_for_payment',
            'work-permit' => 'work_permit',
            'leave' => 'leave_application',
            'reimbursement' => 'reimbursement',
            'liquidation' => 'liquidation',
            'vehicle-request' => 'vehicle_request',
        ];

        private array $fields = [
            'advance_payment' => ['purpose', 'payment_type', 'payee', 'date'],
            'overtime_authorization' => ['employee_name', 'department', 'request_date'],
            'request_for_payment' => ['payee', 'payment_type', 'purpose', 'date'],
            'work_permit' => ['unit_owner', 'bearer_name', 'date', 'service_type'],
            'leave_application' => ['leave_type', 'from_date', 'to_date', 'payment_term'],
            'reimbursement' => ['employee_name', 'department', 'request_date'],
            'liquidation' => ['employee_name', 'department', 'request_date'],
            'vehicle_request' => ['car_available', 'employee_name', 'date', 'trip_type'],
        ];

        // ----------------------------------------------------------------
        // GET /forms/{slug}
        // ----------------------------------------------------------------
        public function index(string $slug): void {
            $type   = $this->resolveType($slug);
            $userId = $_SESSION['user_id'];
            $roleId = $_SESSION['role_id'];

            if ($roleId == 1) {
                $stmt = db()->prepare(
                    'SELECT f.id, f.status, f.created_at, e.full_name
                    FROM forms f JOIN employees e ON e.id = f.submitted_by
                    WHERE f.form_type = ? ORDER BY f.created_at DESC LIMIT 50'
                );
                $stmt->execute([$type]);
            } elseif ($roleId == 2) {
                
                $stmt = db()->prepare(
                    'SELECT DISTINCT f.id, f.status, f.created_at, e.full_name
                    FROM forms f JOIN employees e ON e.id = f.submitted_by
                    JOIN approvals a ON a.form_id = f.id
                    WHERE f.form_type = ? AND a.approver_id = ?
                    ORDER BY f.created_at DESC LIMIT 50'
                );
                $stmt->execute([$type, $userId]);
            } else {
                $stmt = db()->prepare(
                    'SELECT f.id, f.status, f.created_at, e.full_name
                    FROM forms f JOIN employees e ON e.id = f.submitted_by
                    WHERE f.form_type = ? AND f.submitted_by = ?
                    ORDER BY f.created_at DESC LIMIT 30'
                );
                $stmt->execute([$type, $userId]);
            }

            $forms = $stmt->fetchAll();
            $formType = $type;
            $pageTitle = ucwords(str_replace('_', ' ', $type));

            $this->render('forms/list', compact('forms', 'formType', 'slug', 'pageTitle'));
        }

        // ----------------------------------------------------------------
        // GET  /forms/{slug}/create — show blank form
        // POST /forms/{slug}/create — save form
        // ----------------------------------------------------------------
        public function create(string $slug): void {
            $type = $this->resolveType($slug);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->store($type, $slug);
                return;
            }

            $fields = $this->fields[$type];
            $formType = $type;

            $noSuffix = ['list', 'show', 'request_for_payment'];
            $viewName = in_array($type, $noSuffix) ? $type : "{$type}_form";
            $pageTitle = ucwords(str_replace('_', ' ', $type));

            $departments = db()->query(
                'SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department'
            )->fetchAll(PDO::FETCH_COLUMN);

            $this->render("forms/{$viewName}", compact('fields', 'formType', 'slug', 'pageTitle', 'departments'));
        }

        // ----------------------------------------------------------------
        // GET /forms/view/{id}
        // ----------------------------------------------------------------
        public function show(int $id): void {
            $form = $this->findForm($id);

            $approvals = db()->prepare(
                'SELECT a.*, e.full_name FROM approvals a
                JOIN employees e ON e.id = a.approver_id
                WHERE a.form_id = ? ORDER BY a.sequence'
            );
            $approvals->execute([$id]);
            $approvalSteps = $approvals->fetchAll();

            $canAct = $this->canActOnForm($form, $approvalSteps);
            $data = json_decode($form['data'], true) ?? [];

            $formLabel = [
                'advance_payment' => 'Advance Payment',
                'overtime_authorization' => 'Overtime Authorization',
                'request_for_payment' => 'Request for Payment',
                'work_permit' => 'Work Permit',
                'leave_application' => 'Leave Application',
                'reimbursement' => 'Reimbursement',
                'liquidation' => 'Liquidation',
                'vehicle_request' => 'Vehicle Request',
            ];
            $pageTitle = ($formLabel[$form['form_type']] ?? $form['form_type']) . ' #' . $id;

            $this->render('forms/show', compact('form', 'approvalSteps', 'canAct', 'data', 'pageTitle'));
        }

        // ----------------------------------------------------------------
        // POST /forms/{id}/approve
        // ----------------------------------------------------------------
        public function approve(int $id): void {
            $this->processApproval($id, 'approved');
        }

        // ----------------------------------------------------------------
        // POST /forms/{id}/reject
        // ----------------------------------------------------------------
        public function reject(int $id): void {
            $this->processApproval($id, 'rejected');
        }

        // ================================================================
        // PRIVATE
        // ================================================================

        private function store(string $type, string $slug): void {
            \App\Helpers\Csrf::verify();

            $required = $this->fields[$type];
            $data = [];

            foreach ($required as $field) {
                $val = $_POST[$field] ?? '';
                if (is_string($val)) $val = trim($val);
                if ($val === '' || (is_array($val) && empty(array_filter($val)))) {
                    $_SESSION['error'] = "Field '{$field}' is required.";
                    header("Location: /processing-system/public/forms/{$slug}/create");
                    exit;
                }
            }

            foreach ($_POST as $key => $val) {
                if ($key === 'csrf_token') continue;
                if (is_array($val)) {
                    $data[$key] = array_map(fn($v) => htmlspecialchars(trim($v), ENT_QUOTES), $val);
                } else {
                    $data[$key] = htmlspecialchars(trim($val), ENT_QUOTES);
                }
            }

            $pdo = db();
            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO forms (form_type, status, submitted_by, data)
                    VALUES (?, \'submitted\', ?, ?)'
                );
                $stmt->execute([$type, $_SESSION['user_id'], json_encode($data)]);
                $formId = (int) $pdo->lastInsertId();

                $approvers = $this->resolveApprovers($type, $data);
                foreach ($approvers as $seq => $approverId) {
                    $pdo->prepare(
                        'INSERT INTO approvals (form_id, approver_id, sequence) VALUES (?, ?, ?)'
                    )->execute([$formId, $approverId, $seq + 1]);
                }

                if (!empty($approvers)) {
                    $pdo->prepare('UPDATE forms SET status = ? WHERE id = ?')
                        ->execute(['in_approval', $formId]);
                }

                $this->audit('form_submitted', 'form', $formId, null, ['type' => $type, 'status' => 'in_approval']);

                $pdo->commit();

                $_SESSION['success'] = 'Form submitted successfully.';
                header("Location: /processing-system/public/forms/view/{$formId}");
                exit;

            } catch (\Throwable $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'Submission failed. Please try again.';
                header("Location: /processing-system/public/forms/{$slug}/create");
                exit;
            }
        }

        private function processApproval(int $id, string $action): void {
            \App\Helpers\Csrf::verify();

            $form = $this->findForm($id);
            $remarks = trim($_POST['remarks'] ?? '');
            $isSysAdmin = $_SESSION['role_id'] == 1;

            if ($isSysAdmin) {
                // Find the next pending step regardless of who the approver is
                $step = db()->prepare(
                    'SELECT * FROM approvals WHERE form_id = ? AND status = \'pending\' ORDER BY sequence LIMIT 1'
                );
                $step->execute([$id]);
            } else {
                $step = db()->prepare(
                    'SELECT * FROM approvals WHERE form_id = ? AND approver_id = ? AND status = \'pending\' ORDER BY sequence LIMIT 1'
                );
                $step->execute([$id, $_SESSION['user_id']]);
            }

            $approval = $step->fetch();

            if (!$approval && !$isSysAdmin) {
                $_SESSION['error'] = 'No pending approval step found for you.';
                header("Location: /processing-system/public/forms/view/{$id}");
                exit;
            }

            $pdo = db();
            $pdo->beginTransaction();

            try {
                if ($approval) {
                    $pdo->prepare(
                        'UPDATE approvals SET status = ?, remarks = ?, approved_at = NOW() WHERE id = ?'
                    )->execute([$action, $remarks ?: ($isSysAdmin ? '(SysAdmin override)' : ''), $approval['id']]);
                }

                if ($action === 'rejected') {
                    $pdo->prepare('UPDATE forms SET status = \'rejected\' WHERE id = ?')->execute([$id]);
                    $newStatus = 'rejected';
                } else {
                    $pending = $pdo->prepare(
                        'SELECT COUNT(*) FROM approvals WHERE form_id = ? AND status = \'pending\''
                    );
                    $pending->execute([$id]);
                    $newStatus = (int)$pending->fetchColumn() === 0 ? 'approved' : 'in_approval';
                    $pdo->prepare('UPDATE forms SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
                }

                $this->audit("form_{$action}", 'form', $id, ['status' => $form['status']], ['status' => $newStatus, 'remarks' => $remarks]);

                $pdo->commit();
                $_SESSION['success'] = 'Form ' . $action . ' successfully.';
            } catch (\Throwable $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'Action failed. Please try again.';
            }

            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        private function resolveApprovers(string $type, array $data): array {
            $stmt = db()->prepare(
                'SELECT id FROM employees WHERE role_id = 2 AND is_active = 1 ORDER BY id'
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        private function canActOnForm(array $form, array $steps): bool {
            if ($_SESSION['role_id'] == 1) {
                return in_array($form['status'], ['submitted', 'in_approval']);
            }
            $userId = $_SESSION['user_id'];
            foreach ($steps as $step) {
                if ($step['approver_id'] == $userId && $step['status'] === 'pending') {
                    return true;
                }
            }
            return false;
        }


        private function findForm(int $id): array {
            $stmt = db()->prepare('SELECT * FROM forms WHERE id = ?');
            $stmt->execute([$id]);
            $form = $stmt->fetch();

            if (!$form) {
                http_response_code(404);
                echo '<h3>Form not found.</h3>';
                exit;
            }

            if ($_SESSION['role_id'] == 3 && $form['submitted_by'] != $_SESSION['user_id']) {
                http_response_code(403);
                echo '<h3>Access denied.</h3>';
                exit;
            }

            return $form;
        }

        private function audit(string $action, string $entity, int $entityId, ?array $old, ?array $new): void {
            db()->prepare(
                'INSERT INTO audit_logs (performed_by, action, entity_type, entity_id, old_values, new_values, ip_address)
                VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $_SESSION['user_id'],
                $action,
                $entity,
                $entityId,
                $old ? json_encode($old) : null,
                $new ? json_encode($new)  : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        }

        private function resolveType(string $slug): string {
            if (!isset($this->typeMap[$slug])) {
                http_response_code(404);
                echo '<h3>Unknown form type.</h3>';
                exit;
            }
            return $this->typeMap[$slug];
        }

        // Path Traversal — validate with realpath() + boundary check
        private function render(string $view, array $vars = []): void {
            $allowed = [
                'forms/list',
                'forms/show',
                'forms/advance_payment_form',
                'forms/overtime_authorization_form',
                'forms/request_for_payment',         
                'forms/work_permit_form',
                'forms/leave_application_form',
                'forms/reimbursement_form',
                'forms/liquidation_form',
                'forms/vehicle_request_form',
            ];

            if (!in_array($view, $allowed, true)) {
                http_response_code(404);
                echo '<h3>View not found.</h3>';
                exit;
            }

            // realpath() boundary check prevents path traversal
            $basePath = realpath(__DIR__ . '/../../views');
            $fullPath = realpath($basePath . '/' . $view . '.php');

            if ($fullPath === false || strpos($fullPath, $basePath) !== 0) {
                http_response_code(403);
                echo '<h3>Access denied.</h3>';
                exit;
            }

            define('BASE_LOADED', true);
            extract($vars);
            if (!isset($pageTitle)) $pageTitle = 'Processing System';
            ob_start();
            require $fullPath;
            $content = ob_get_clean();
            require __DIR__ . '/../../views/layouts/base.php';
        }
    }