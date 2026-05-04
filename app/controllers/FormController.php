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
        'advance_payment'        => ['purpose', 'payment_type', 'payee', 'date'],
        'overtime_authorization' => ['employee_name', 'department', 'request_date'],
        'request_for_payment'    => ['payee', 'payment_type', 'purpose', 'date'],
        'work_permit'            => ['unit_owner', 'bearer_name', 'date', 'service_type'],
        'leave_application'      => ['leave_type', 'from_date', 'to_date', 'payment_term'],
        'reimbursement'          => ['employee_name', 'department', 'request_date'],
        'liquidation'            => ['employee_name', 'department', 'request_date'],
        'vehicle_request'        => ['car_available', 'employee_name', 'date', 'trip_type'],
    ];

    /**
     *     * Role map (align with the employees table):
     *   1 = Admin
     *   2 = Approver / Manager
     *   3 = Regular Employee
     *   4 = Department Head
     *   5 = Checker / 
     *   6 = Final Approver
     */
    private const PIPELINE = [
        'submit' => [
            'sequence' => 1,
            'from'     => 'draft',
            'to'       => 'submitted',
            'role_id'  => 3,   // the employee who owns the form
            'label'    => 'Submitted',
        ],
        'supervisor-review' => [
            'sequence' => 2,
            'from'     => 'submitted',
            'to'       => 'supervisor_reviewed',
            'role_id'  => 2,   // supervisor / manager
            'label'    => 'Supervisor Reviewed',
        ],
        'department-check' => [
            'sequence' => 3,
            'from'     => 'supervisor_reviewed',
            'to'       => 'department_checked',
            'role_id'  => 4,   // department head
            'label'    => 'Department Checked',
        ],
        'checker-supervisor' => [
            'sequence' => 4,
            'from'     => 'department_checked',
            'to'       => 'checker_approved',
            'role_id'  => 5,   // checker
            'label'    => 'Checker Supervisor Approved',
        ],
        'final-approval' => [
            'sequence' => 5,
            'from'     => 'checker_approved',
            'to'       => 'final_approved',
            'role_id'  => 6,   // final approver
            'label'    => 'Final Approval Granted',
        ],
        'complete' => [
            'sequence' => 6,
            'from'     => 'final_approved',
            'to'       => 'completed',
            'role_id'  => 6,   // final approver completes the request
            'label'    => 'Completed',
        ],
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

        $forms     = $stmt->fetchAll();
        $formType  = $type;
        $pageTitle = ucwords(str_replace('_', ' ', $type));

        $this->render('forms/list', compact('forms', 'formType', 'slug', 'pageTitle'));
    }

    // ----------------------------------------------------------------
    // GET  /forms/{slug}/create — show blank form
    // POST /forms/{slug}/create — save form as draft
    // ----------------------------------------------------------------
    public function create(string $slug): void {
        $type = $this->resolveType($slug);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store($type, $slug);
            return;
        }

        $fields    = $this->fields[$type];
        $formType  = $type;
        $noSuffix  = ['list', 'show', 'request_for_payment'];
        $viewName  = in_array($type, $noSuffix) ? $type : "{$type}_form";
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

        $canAct   = $this->canActOnForm($form, $approvalSteps);
        $data     = json_decode($form['data'], true) ?? [];
        $pipeline = self::PIPELINE;          // pass to view for timeline UI

        $formLabel = [
            'advance_payment'        => 'Advance Payment',
            'overtime_authorization' => 'Overtime Authorization',
            'request_for_payment'    => 'Request for Payment',
            'work_permit'            => 'Work Permit',
            'leave_application'      => 'Leave Application',
            'reimbursement'          => 'Reimbursement',
            'liquidation'            => 'Liquidation',
            'vehicle_request'        => 'Vehicle Request',
        ];
        $pageTitle = ($formLabel[$form['form_type']] ?? $form['form_type']) . ' #' . $id;

        $this->render('forms/show', compact('form', 'approvalSteps', 'canAct', 'data', 'pageTitle', 'pipeline'));
    }

    // ----------------------------------------------------------------
    // POST /forms/{id}/approve/{action}
    // Route passes $action from: submit | supervisor-review |
    //   department-check | checker-supervisor | final-approval | complete
    // ----------------------------------------------------------------
    public function approve(int $id, string $action): void {
        $this->processApproval($id, $action);
    }

    // ----------------------------------------------------------------
    // POST /forms/{id}/reject
    // ----------------------------------------------------------------
    public function reject(int $id): void {
        \App\Helpers\Csrf::verify();

        $form    = $this->findForm($id);
        $remarks = trim($_POST['remarks'] ?? '');
        $userId  = $_SESSION['user_id'];
        $roleId  = $_SESSION['role_id'];

        // Rejection is allowed by admin or any role that has a pending step
        $allowedRoles = [1, 2, 4, 5, 6];
        if (!in_array($roleId, $allowedRoles, true)) {
            $_SESSION['error'] = 'You are not authorised to reject this form.';
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        if (in_array($form['status'], ['completed', 'rejected'], true)) {
            $_SESSION['error'] = 'This form is already finalised.';
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        if ($remarks === '') {
            $_SESSION['error'] = 'A rejection reason is required.';
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            // Mark every remaining pending step as rejected
            $pdo->prepare(
                "UPDATE approvals SET status = 'rejected', remarks = ?, approved_at = NOW()
                 WHERE form_id = ? AND status = 'pending'"
            )->execute([$remarks, $id]);

            $pdo->prepare("UPDATE forms SET status = 'rejected' WHERE id = ?")
                ->execute([$id]);

            $this->audit(
                'form_rejected', 'form', $id,
                ['status' => $form['status']],
                ['status' => 'rejected', 'remarks' => $remarks]
            );

            $pdo->commit();
            $_SESSION['success'] = 'Form rejected successfully.';

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Rejection failed. Please try again.';
        }

        header("Location: /processing-system/public/forms/view/{$id}");
        exit;
    }

    // ================================================================
    // PRIVATE
    // ================================================================

    // ----------------------------------------------------------------
    // Core pipeline processor
    // ----------------------------------------------------------------
    private function processApproval(int $id, string $action): void {
        \App\Helpers\Csrf::verify();

        $form    = $this->findForm($id);
        $remarks = trim($_POST['remarks'] ?? '');
        $userId  = (int) $_SESSION['user_id'];
        $roleId  = (int) $_SESSION['role_id'];
        $isAdmin = $roleId === 1;

        // ── Guard: valid pipeline action ──────────────────────────────
        if (!isset(self::PIPELINE[$action])) {
            $_SESSION['error'] = "Unknown approval action: '{$action}'.";
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        $step = self::PIPELINE[$action];

        // ── form must be in the expected status ─────────────────
        if ($step['from'] !== '*' && $form['status'] !== $step['from']) {
            $_SESSION['error'] = sprintf(
                "Cannot perform '%s': form is currently '%s', expected '%s'.",
                $action, $form['status'], $step['from']
            );
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        // ── form must not be finalised ──────────────────────────
        if (in_array($form['status'], ['completed', 'rejected'], true)) {
            $_SESSION['error'] = 'This form is already finalised.';
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        // ── role authorisation ──────────────────────────────────
        // Admin can act on any stage.
        // For 'submit' the owner (any role) is allowed.
        // For all other stages the session role must match the step role.
        $actorAllowed = $isAdmin
            || $action === 'submit'
            || $roleId === $step['role_id'];

        if (!$actorAllowed) {
            $_SESSION['error'] = 'You are not authorised to perform this action.';
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        // ── 'submit' must be performed by the form owner ────────
        if ($action === 'submit' && !$isAdmin && (int)$form['submitted_by'] !== $userId) {
            $_SESSION['error'] = 'Only the form owner can submit this form.';
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        // ── Find the matching pending approval row for this sequence ───
        $approvalRow = db()->prepare(
            'SELECT * FROM approvals WHERE form_id = ? AND sequence = ? AND status = \'pending\' LIMIT 1'
        );
        $approvalRow->execute([$id, $step['sequence']]);
        $approval = $approvalRow->fetch();

        // Non-admins must have an approval row assigned to them at this sequence
        if (!$isAdmin && $action !== 'submit') {
            if (!$approval || (int)$approval['approver_id'] !== $userId) {
                $_SESSION['error'] = 'No pending approval step found for you at this stage.';
                header("Location: /processing-system/public/forms/view/{$id}");
                exit;
            }
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            // Mark this step as approved
            if ($approval) {
                $pdo->prepare(
                    "UPDATE approvals
                     SET status = 'approved', remarks = ?, approved_at = NOW()
                     WHERE id = ?"
                )->execute([
                    $remarks ?: ($isAdmin ? '(Admin override)' : $step['label']),
                    $approval['id'],
                ]);
            }

            // Advance form status
            $pdo->prepare('UPDATE forms SET status = ? WHERE id = ?')
                ->execute([$step['to'], $id]);

            $this->audit(
                'form_' . str_replace('-', '_', $action),
                'form',
                $id,
                ['status' => $form['status']],
                ['status' => $step['to'], 'remarks' => $remarks]
            );

            $pdo->commit();
            $_SESSION['success'] = $step['label'] . ' recorded successfully.';

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Action failed. Please try again.';
        }

        header("Location: /processing-system/public/forms/view/{$id}");
        exit;
    }

    // ----------------------------------------------------------------
    // Save a new form as 'draft'; approvals per pipeline stage
    // ----------------------------------------------------------------
    private function store(string $type, string $slug): void {
        \App\Helpers\Csrf::verify();

        $required = $this->fields[$type];
        $data     = [];

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
            // Insert as 'draft' — employee must hit 'submit' to start the pipeline
            $stmt = $pdo->prepare(
                "INSERT INTO forms (form_type, status, submitted_by, data)
                 VALUES (?, 'draft', ?, ?)"
            );
            $stmt->execute([$type, $_SESSION['user_id'], json_encode($data)]);
            $formId = (int) $pdo->lastInsertId();

            // Seed one approval row per pipeline stage (skipping 'submit' — that's the owner)
            $this->seedApprovalRows($pdo, $formId, $type, $data);

            $this->audit('form_created', 'form', $formId, null, ['type' => $type, 'status' => 'draft']);

            $pdo->commit();

            $_SESSION['success'] = 'Form saved as draft. Review and submit it for approval.';
            header("Location: /processing-system/public/forms/view/{$formId}");
            exit;

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Submission failed. Please try again.';
            header("Location: /processing-system/public/forms/{$slug}/create");
            exit;
        }
    }

    /**
     * Insert one approvals row for every pipeline stage that needs a
     * designated approver (sequences 2-6). The approver is resolved by
     * role_id so any active employee in that role can be found.
     *
     * Extend this method with department-aware or form-type-aware
     * lookup logic as your business rules require.
     */
    private function seedApprovalRows(\PDO $pdo, int $formId, string $type, array $data): void {
        // Stages that need a real approver row (sequence ≥ 2)
        $stagesNeedingApprover = array_filter(
            self::PIPELINE,
            fn($step) => $step['sequence'] >= 2
        );

        $insert = $pdo->prepare(
            "INSERT INTO approvals (form_id, approver_id, sequence, status) VALUES (?, ?, ?, 'pending')"
        );

        foreach ($stagesNeedingApprover as $action => $step) {
            $approver = $this->resolveApproverByRole($pdo, $step['role_id'], $data);

            if ($approver) {
                $insert->execute([$formId, $approver, $step['sequence']]);
            }
            // If no approver found for a role you may log a warning or throw;
            // for now we skip silently so the form can still be created.
        }
    }

    /**
     * Find the first active employee with the given role_id.
     * Swap in department-filtering or a lookup table as needed.
     */
    private function resolveApproverByRole(\PDO $pdo, int $roleId, array $data): ?int {
        $stmt = $pdo->prepare(
            'SELECT id FROM employees WHERE role_id = ? AND is_active = 1 ORDER BY id LIMIT 1'
        );
        $stmt->execute([$roleId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['id'] : null;
    }

    // ----------------------------------------------------------------
    // Determine whether the current user can act on this form
    // ----------------------------------------------------------------
    private function canActOnForm(array $form, array $steps): bool {
        // Finalised forms cannot be acted upon
        if (in_array($form['status'], ['completed', 'rejected'], true)) {
            return false;
        }

        // Admin can act whenever the form is active
        if ($_SESSION['role_id'] == 1) {
            return true;
        }

        $userId = (int) $_SESSION['user_id'];

        // Owner can submit their own draft
        if ($form['status'] === 'draft' && (int)$form['submitted_by'] === $userId) {
            return true;
        }

        // Any approver with a pending step at the current sequence
        foreach ($steps as $step) {
            if ((int)$step['approver_id'] === $userId && $step['status'] === 'pending') {
                return true;
            }
        }

        return false;
    }

    // ----------------------------------------------------------------
    // Fetch form, enforce basic access control
    // ----------------------------------------------------------------
    private function findForm(int $id): array {
        $stmt = db()->prepare('SELECT * FROM forms WHERE id = ?');
        $stmt->execute([$id]);
        $form = $stmt->fetch();

        if (!$form) {
            http_response_code(404);
            echo '<h3>Form not found.</h3>';
            exit;
        }

        // Regular employees (role 3) may only see their own forms
        if ($_SESSION['role_id'] == 3 && $form['submitted_by'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo '<h3>Access denied.</h3>';
            exit;
        }

        return $form;
    }

    // ----------------------------------------------------------------
    // Audit log helper (unchanged from original)
    // ----------------------------------------------------------------
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

    // ----------------------------------------------------------------
    // Slug → internal type name
    // ----------------------------------------------------------------
    private function resolveType(string $slug): string {
        if (!isset($this->typeMap[$slug])) {
            http_response_code(404);
            echo '<h3>Unknown form type.</h3>';
            exit;
        }
        return $this->typeMap[$slug];
    }

    // ----------------------------------------------------------------
    // Safe view renderer with path-traversal protection (unchanged)
    // ----------------------------------------------------------------
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
