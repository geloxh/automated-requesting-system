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
            $fields = ['full_name', 'email', 'password', 'role_id', 'department'];
            $data = [];
            foreach ($fields as $f) {
                $val = trim($_POST[$f] ?? '');
                if ($val === '' && $f !== 'department') {
                    $_SESSION['error'] = "Field '{$f}' is required.";
                    header('Location: /processing-system/public/employees/create');
                    exit;
                }
                $data[$f] = $val;
            }

            $pdo = db();
            $pdo->beginTransaction();
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
                    (int)$data['role_id'],
                    $data['department'],
                ]);
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'Failed to create employee.';
                header('Location: /processing-system/public/employees/create');
                exit;
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

            $stmt = db()->prepare('SELECT id FROM employees WHERE id = ?');
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $_SESSION['error'] = 'Employee not found.';
                header('Location: /processing-system/public/employees');
                exit;
            }

            db()->prepare('DELETE FROM employees WHERE id = ?')->execute([$id]);

            $_SESSION['success'] = 'Employee deleted.';
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

            $stmt = db()->prepare('SELECT * FROM forms WHERE id = ?');
            $stmt->execute([$formId]);
            $form = $stmt->fetch();

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
                } else {
                    $pending = $pdo->prepare(
                        'SELECT COUNT(*) FROM approvals WHERE form_id = ? AND status = \'pending\''
                    );
                    $pending->execute([$formId]);
                    $newStatus = (int)$pending->fetchColumn() === 0 ? 'approved' : 'in_approval';
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