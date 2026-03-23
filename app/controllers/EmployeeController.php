<?php
    class EmployeeController {
        public function index(): void {
            $employees = db()->query('SELECT id, employee_code, full_name, email, department, is_active FROM employees ORDER BY full_name')->fetchAll();
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
            $fields = ['employee_code', 'full_name', 'email', 'password', 'role_id', 'department'];
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

            db()->prepare(
                'INSERT INTO employees (employee_code, full_name, email, password_hash, role_id, department)
                VALUES (?, ?, ?, ?, ? , ?)'
            )->execute([
                $data['employee_code'],
                $data['full_name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_BCRYPT),
                (int)$data['role_id'],
                $data['department'],
            ]);

            $_SESSION['success'] = 'Employee created.';
            header('Location: /processing-system/public/employees');
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

            // Change password if provided
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