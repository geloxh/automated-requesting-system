<?php
    class EmployeeController {
        public function index(): void {
            $employees = db()->query(
                'SELECT id, employee_code, full_name, email, department, is_active, employment_status, role_id FROM employees ORDER BY full_name'
            )->fetchAll();
            define('BASE_LOADED', true);
            ob_start();
            require __DIR__ . '/../../views/employees/index.php';
            $content = ob_get_clean();
            $pageTitle = 'Employees';
            require __DIR__ . '/../../views/layouts/base.php';
        }

        public function create(): void {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->store();
                return;
            }
            define('BASE_LOADED', true);
            ob_start();
            require __DIR__ . '/../../views/employees/create.php';
            $content = ob_get_clean();
            $pageTitle = 'Add Employee';
            require __DIR__ . '/../../views/layouts/base.php';
        }

        private function store(): void {
            \App\Helpers\Csrf::verify();

            $data = [];
            foreach (['full_name', 'email', 'password', 'role_id', 'department'] as $f) {
                $val = trim($_POST[$f] ?? '');
                if ($val === '' && $f !== 'department') {
                    $_SESSION['error'] = "Field '{$f}' is required.";
                    header('Location: /processing-system/public/employees/create'); exit;
                }
                $data[$f] = $val;
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'Invalid email address.';
                header('Location: /processing-system/public/employees/create'); exit;
            }

            if (strlen($data['password']) < 8) {
                $_SESSION['error'] = 'Password must be at least 8 characters.';
                header('Location: /processing-system/public/employees/create'); exit;
            }

            $stmt = db()->prepare('SELECT id FROM employees WHERE email = ?');
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Email already registered.';
                header('Location: /processing-system/public/employees/create'); exit;
            }

            $pdo = db();
            try {
                $empCode = \App\Helpers\generateEmployeeCode($pdo);
                $pdo->prepare(
                    'INSERT INTO employees (employee_code, full_name, email, password_hash, role_id, department)
                    VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([
                    $empCode,
                    $data['full_name'],
                    $data['email'],
                    password_hash($data['password'], PASSWORD_BCRYPT),
                    (int) $data['role_id'],
                    $data['department'],
                ]);
            } catch (\Throwable) {
                $_SESSION['error'] = 'Failed to create employee.';
                header('Location: /processing-system/public/employees/create'); exit;
            }

            $_SESSION['success'] = 'Employee created.';
            header('Location: /processing-system/public/employees');
            exit;
        }
        
        public function delete(int $id): void {
            \App\Helpers\Csrf::verify();

            // Prevent self-deletion
            if ($id === (int)$_SESSION['user_id']) {
                $_SESSION['error'] = 'You cannot delete your own account.';
                header('Location: /processing-system/public/employees');
                exit;
            }

            $pdo = db();
            $stmt = $pdo->prepare('SELECT id, role_id FROM employees WHERE id = ?');
            $stmt->execute([$id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                $_SESSION['error'] = 'Employee not found.';
                header('Location: /processing-system/public/employees');
                exit;
            }

            $pdo->beginTransaction();
            try {
                // Soft-delete: deactivate instead of hard delete so audit logs & FKs remain intact
                $pdo->prepare('UPDATE employees SET is_active = 0 WHERE id = ?')->execute([$id]);

                // Reassign any pending approval steps to the next least-loaded
                // active colleague in the same role
                $pendingApprovals = $pdo->prepare(
                    'SELECT id FROM approvals WHERE approver_id = ? AND status = \'pending\''
                );
                $pendingApprovals->execute([$id]);
                $rows = $pendingApprovals->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    // Find replacement: least-loaded active employee in same role (excluding deleted)
                    $replacement = $pdo->prepare(
                        'SELECT e.id, COUNT(a.id) AS workload
                         FROM employees e
                         LEFT JOIN approvals a ON a.approver_id = e.id AND a.status = \'pending\'
                         WHERE e.role_id = ? AND e.is_active = 1 AND e.id != ?
                         GROUP BY e.id
                         ORDER BY workload ASC, e.id ASC
                         LIMIT 1'
                    );
                    $replacement->execute([$employee['role_id'], $id]);
                    $replacementEmployee = $replacement->fetch(PDO::FETCH_ASSOC);

                    if ($replacementEmployee) {
                        $reassign = $pdo->prepare(
                            'UPDATE approvals SET approver_id = ? WHERE approver_id = ? AND status = \'pending\''
                        );
                        $reassign->execute([$replacementEmployee['id'], $id]);
                        $_SESSION['success'] = sprintf(
                            'Employee deactivated. %d pending approval(s) reassigned.',
                            count($rows)
                        );
                    } else {
                        // No replacement available — flag them so admins can see
                        $_SESSION['success'] = 'Employee deactivated. Warning: no replacement approver found for their ' . count($rows) . ' pending step(s). Please reassign manually.';
                    }
                } else {
                    $_SESSION['success'] = 'Employee deactivated.';
                }

                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'Failed to deactivate employee.';
            }

            header('Location: /processing-system/public/employees');
            exit;
        }

        public function updateStatus(int $id): void {
            \App\Helpers\Csrf::verify();

            $allowed = ['employed', 'resigned', 'floating'];
            $status = trim($_POST['employment_status'] ?? '');

            if (!in_array($status, $allowed, true)) {
                $_SESSION['error'] = 'Invalid employment status.';
                header('Location: /processing-system/public/employees');
                exit;
            }

            db()->prepare('UPDATE employees SET employment_status = ? WHERE id = ?')
                ->execute([$status, $id]);

            $_SESSION['success'] = 'Employment status updated.';
            header('Location: /processing-system/public/employees');
            exit;
        }

        public function actAsApprover(int $formId, string $action): void {
            \App\Helpers\Csrf::verify();

            if (!in_array($action, ['approved', 'rejected'], true)) {
                http_response_code(400);
                exit;
            }

            $stmt = db()->prepare('SELECT status FROM forms WHERE id = ?');
            $stmt->execute([$formId]);
            $form = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$form) {
                $_SESSION['error'] = 'Form not found.';
                header("Location: /processing-system/public/forms/view/{$formId}");
                exit;
            }

            $remarks = trim($_POST['remarks'] ?? '(SysAdmin override)');

            // Find the next pending approval step
            $step = db()->prepare(
                'SELECT * FROM approvals WHERE form_id = ? AND status = \'pending\' ORDER BY sequence LIMIT 1'
            );
            $step->execute([$formId]);
            $approval = $step->fetch();

            $pdo = db();
            $pdo->beginTransaction();

            try {
                if ($approval) {
                    $pdo->prepare(
                        'UPDATE approvals SET status = ?, remarks = ?, approved_at = NOW() WHERE id = ?'
                    )->execute([$action, $remarks, $approval['id']]);
                }

                if ($action === 'rejected') {
                    $newStatus = 'rejected';

                    // Mark ALL remaining pending steps as rejected too
                    $pdo->prepare(
                        "UPDATE approvals SET status = 'rejected', remarks = ?, approved_at = NOW()
                         WHERE form_id = ? AND status = 'pending' AND id != ?"
                    )->execute([$remarks, $formId, $approval['id']]);

                } else {
                    // Derive correct pipeline status from the sequence just approved
                    $seqToStatus = [
                        2 => 'supervisor_reviewed',
                        3 => 'department_checked',
                        4 => 'checker_approved',
                        5 => 'final_approved',
                        6 => 'completed',
                    ];
                    $seq = (int) ($approval['sequence'] ?? 0);
                    $newStatus = $seqToStatus[$seq] ?? 'submitted';
                }

                $pdo->prepare('UPDATE forms SET status = ? WHERE id = ?')->execute([$newStatus, $formId]);

                $pdo->prepare(
                    'INSERT INTO audit_logs (performed_by, action, entity_type, entity_id, old_values, new_values, ip_address)
                    VALUES (?, ?, \'form\', ?, ?, ?, ?)'
                )->execute([
                    $_SESSION['user_id'],
                    "sysadmin_form_{$action}",
                    $formId,
                    json_encode(['status' => $form['status']]),
                    json_encode(['status' => $newStatus, 'remarks' => $remarks]),
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]);

                $pdo->commit();
                $_SESSION['success'] = 'Form ' . $action . ' by SysAdmin.';
            } catch (\Throwable $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'Action failed.';
            }

            header("Location: /processing-system/public/forms/view/{$formId}");
            exit;
        }

        public function profile(): void {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->updateProfile();
                return;
            }
            $employee = db()->prepare('SELECT id, employee_code, full_name, email, department FROM employees WHERE id = ?');
            $employee->execute([$_SESSION['user_id']]);
            $employee = $employee->fetch();

            define('BASE_LOADED', true);
            ob_start();
            require __DIR__ . '/../../views/profile/index.php';
            $content = ob_get_clean();
            $pageTitle = 'Profile';
            require __DIR__ . '/../../views/layouts/base.php';
        }

        private function updateProfile(): void {
            \App\Helpers\Csrf::verify();
            $data = [
                'full_name' => trim($_POST['full_name'] ?? ''),
                'department' => trim($_POST['department'] ?? ''),
            ];

            if ($data['full_name'] === '') {
                $_SESSION['error'] = 'Full name is required.';
                header('Location: /processing-system/public/profile');
                exit;
            }

            if (!empty($_POST['new_password'])) {
                if (strlen($_POST['new_password']) < 8) {
                    $_SESSION['error'] = 'New password must be at least 8 characters.';
                    header('Location: /processing-system/public/profile');
                    exit;
                }

                $emp = db()->prepare('SELECT password_hash FROM employees WHERE id = ?');
                $emp->execute([$_SESSION['user_id']]);
                $emp = $emp->fetch();

                if (!password_verify($_POST['current_password'] ?? '', $emp['password_hash'])) {
                    $_SESSION['error'] = 'Current password is incorrect.';
                    header('Location: /processing-system/public/profile');
                    exit;
                }
                $data['password_hash'] = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
            }

            $sets = 'full_name = ?, department = ?';
            $params = [$data['full_name'], $data['department']];

            if (isset($data['password_hash'])) {
                $sets .= ', password_hash = ?';
                $params[] = $data['password_hash'];
            }

            $params[] = $_SESSION['user_id'];
            db()->prepare("UPDATE employees SET {$sets} WHERE id = ?")->execute($params);

            $_SESSION['user_name'] = $data['full_name'];
            $_SESSION['success'] = 'Profile updated.';
            header('Location: /processing-system/public/profile');
            exit;
        }
    }