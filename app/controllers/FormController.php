<?php
class FormController
{
    // slug → db form_type
    private array $typeMap = [
        'advance-payment' => 'advance_payment',
        'overtime'        => 'overtime_authorization',
        'request-payment' => 'request_for_payment',
        'work-permit'     => 'work_permit',
        'leave'           => 'leave_application',
        'reimbursement'   => 'reimbursement',
        'liquidation'     => 'liquidation',
        'vehicle-request' => 'vehicle_request',
    ];

    // Fields required per form type
    private array $fields = [
        'advance_payment'        => ['purpose', 'amount', 'date_needed'],
        'overtime_authorization' => ['date', 'start_time', 'end_time', 'reason'],
        'request_for_payment'    => ['payee', 'amount', 'particulars', 'date_needed'],
        'work_permit'            => ['work_description', 'location', 'date_start', 'date_end'],
        'leave_application'      => ['leave_type', 'date_start', 'date_end', 'reason'],
        'reimbursement'          => ['particulars', 'amount', 'date_incurred'],
        'liquidation'            => ['reference_form_id', 'amount_liquidated', 'particulars'],
        'vehicle_request'        => ['destination', 'purpose', 'date_needed', 'passengers'],
    ];

    // ----------------------------------------------------------------
    // GET /forms/{slug}  — list forms of this type for current user
    // ----------------------------------------------------------------
    public function index(string $slug): void
    {
        $type = $this->resolveType($slug);
        $userId = $_SESSION['user_id'];
        $roleId = $_SESSION['role_id'];

        if ($roleId == 1) {
            $stmt = db()->prepare(
                'SELECT f.id, f.status, f.created_at, e.full_name
                 FROM forms f JOIN employees e ON e.id = f.submitted_by
                 WHERE f.form_type = ? ORDER BY f.created_at DESC LIMIT 50'
            );
            $stmt->execute([$type]);
        } else {
            $stmt = db()->prepare(
                'SELECT id, status, created_at FROM forms
                 WHERE form_type = ? AND submitted_by = ?
                 ORDER BY created_at DESC LIMIT 30'
            );
            $stmt->execute([$type, $userId]);
        }

        $forms    = $stmt->fetchAll();
        $formType = $type;
        $slug     = $slug;
        $this->render('forms/list', compact('forms', 'formType', 'slug'));
    }

    // ----------------------------------------------------------------
    // GET  /forms/{slug}/create — show blank form
    // POST /forms/{slug}/create — save form
    // ----------------------------------------------------------------
    public function create(string $slug): void
    {
        $type = $this->resolveType($slug);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store($type, $slug);
            return;
        }

        $fields   = $this->fields[$type];
        $formType = $type;
        $this->render("forms/{$type}", compact('fields', 'formType', 'slug'));
    }

    // ----------------------------------------------------------------
    // GET /forms/view/{id}
    // ----------------------------------------------------------------
    public function show(int $id): void
    {
        $form = $this->findForm($id);

        $approvals = db()->prepare(
            'SELECT a.*, e.full_name FROM approvals a
             JOIN employees e ON e.id = a.approver_id
             WHERE a.form_id = ? ORDER BY a.sequence'
        );
        $approvals->execute([$id]);
        $approvalSteps = $approvals->fetchAll();

        $canAct = $this->canActOnForm($form, $approvalSteps);
        $data   = json_decode($form['data'], true);

        $this->render('forms/show', compact('form', 'approvalSteps', 'canAct', 'data'));
    }

    // ----------------------------------------------------------------
    // POST /forms/approve/{id}
    // ----------------------------------------------------------------
    public function approve(int $id): void
    {
        $this->processApproval($id, 'approved');
    }

    // ----------------------------------------------------------------
    // POST /forms/reject/{id}
    // ----------------------------------------------------------------
    public function reject(int $id): void
    {
        $this->processApproval($id, 'rejected');
    }

    // ================================================================
    // PRIVATE
    // ================================================================

    private function store(string $type, string $slug): void
    {
        $required = $this->fields[$type];
        $data     = [];

        foreach ($required as $field) {
            $val = trim($_POST[$field] ?? '');
            if ($val === '') {
                $_SESSION['error'] = "Field '{$field}' is required.";
                header("Location: /processing-system/public/forms/{$slug}/create");
                exit;
            }
            $data[$field] = htmlspecialchars($val, ENT_QUOTES);
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            // Insert form
            $stmt = $pdo->prepare(
                'INSERT INTO forms (form_type, status, submitted_by, data)
                 VALUES (?, \'submitted\', ?, ?)'
            );
            $stmt->execute([$type, $_SESSION['user_id'], json_encode($data)]);
            $formId = (int) $pdo->lastInsertId();

            // Build approval chain: first available approver role_id=2, sequence 1
            // Extend this array per business rule (e.g. amount-based chains)
            $approvers = $this->resolveApprovers($type, $data);
            foreach ($approvers as $seq => $approverId) {
                $pdo->prepare(
                    'INSERT INTO approvals (form_id, approver_id, sequence) VALUES (?, ?, ?)'
                )->execute([$formId, $approverId, $seq + 1]);
            }

            // Update status to in_approval if chain exists
            if (!empty($approvers)) {
                $pdo->prepare('UPDATE forms SET status = ? WHERE id = ?')
                    ->execute(['in_approval', $formId]);
            }

            // Audit log
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

    private function processApproval(int $id, string $action): void
    {
        $form      = $this->findForm($id);
        $approverId = $_SESSION['user_id'];
        $remarks   = trim($_POST['remarks'] ?? '');

        // Find the current pending step for this approver
        $step = db()->prepare(
            'SELECT * FROM approvals
             WHERE form_id = ? AND approver_id = ? AND status = \'pending\'
             ORDER BY sequence LIMIT 1'
        );
        $step->execute([$id, $approverId]);
        $approval = $step->fetch();

        if (!$approval) {
            $_SESSION['error'] = 'No pending approval step found for you.';
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            // Update this approval step
            $pdo->prepare(
                'UPDATE approvals SET status = ?, remarks = ?, approved_at = NOW()
                 WHERE id = ?'
            )->execute([$action, $remarks, $approval['id']]);

            if ($action === 'rejected') {
                $pdo->prepare('UPDATE forms SET status = \'rejected\' WHERE id = ?')
                    ->execute([$id]);
                $newStatus = 'rejected';
            } else {
                // Check if all steps are now approved
                $pending = db()->prepare(
                    'SELECT COUNT(*) FROM approvals WHERE form_id = ? AND status = \'pending\''
                );
                $pending->execute([$id]);
                $remainingPending = (int) $pending->fetchColumn();

                $newStatus = $remainingPending === 0 ? 'approved' : 'in_approval';
                $pdo->prepare('UPDATE forms SET status = ? WHERE id = ?')
                    ->execute([$newStatus, $id]);
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

    private function resolveApprovers(string $type, array $data): array
    {
        // Fetch all employees with role approver (role_id = 2)
        // Extend this with per-type or amount-based logic as needed
        $stmt = db()->prepare(
            'SELECT id FROM employees WHERE role_id = 2 AND is_active = 1 ORDER BY id LIMIT 1'
        );
        $stmt->execute();
        $approver = $stmt->fetchColumn();

        return $approver ? [$approver] : [];
    }

    private function canActOnForm(array $form, array $steps): bool
    {
        $userId = $_SESSION['user_id'];
        foreach ($steps as $step) {
            if ($step['approver_id'] == $userId && $step['status'] === 'pending') {
                return true;
            }
        }
        return false;
    }

    private function findForm(int $id): array
    {
        $stmt = db()->prepare('SELECT * FROM forms WHERE id = ?');
        $stmt->execute([$id]);
        $form = $stmt->fetch();

        if (!$form) {
            http_response_code(404);
            echo '<h3>Form not found.</h3>';
            exit;
        }

        // Ownership check: staff can only see their own
        if ($_SESSION['role_id'] == 3 && $form['submitted_by'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo '<h3>Access denied.</h3>';
            exit;
        }

        return $form;
    }

    private function audit(string $action, string $entity, int $entityId, ?array $old, ?array $new): void
    {
        db()->prepare(
            'INSERT INTO audit_logs (performed_by, action, entity_type, entity_id, old_values, new_values, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $_SESSION['user_id'],
            $action,
            $entity,
            $entityId,
            $old ? json_encode($old) : null,
            $new  ? json_encode($new)  : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    private function resolveType(string $slug): string
    {
        if (!isset($this->typeMap[$slug])) {
            http_response_code(404);
            echo '<h3>Unknown form type.</h3>';
            exit;
        }
        return $this->typeMap[$slug];
    }

    private function render(string $view, array $vars = []): void
    {
        define('BASE_LOADED', true);
        extract($vars);
        ob_start();
        require __DIR__ . '/../../views/' . $view . '.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/base.php';
    }
}