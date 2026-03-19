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
        $content == ob_get_clean();
        $pageTitle = 'Add Employee';
        require __DIR__ . '/../../views/layouts/base.php';
    }

    private function store(): void {
        $fields = ['employee_code', 'full_name', 'email', 'password', 'role_id', 'department'];
        $data = [];
        foreach ($fields as $f) {
            $val = trim($_POST[$f] ?? '');
            if ($val === '' && $f !== 'department') {
                $_SESSION['error'] = "Field '{$f}' is required.";
                header('Location: /processing-system/public/employment/create');
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
}