<?php
class FormController {
    private array $typeMap = [
        'advance-payment' => 'advance_payment',
        'overtime' => 'overtime_authorization',
        'request-payment' => 'request_for_payment',
        'leave' => 'leave_application',
        'reimbursement' => 'reimbursement',
        'liquidation' => 'liquidation',
        'vehicle-request' => 'vehicle_request',
    ];

    private array $fields = [
        'advance_payment' => ['purpose', 'payment_type', 'payee', 'date'],
        'overtime_authorization' => ['employee_name', 'department', 'request_date'],
        'request_for_payment' => ['payee', 'payment_type', 'purpose', 'date'],
        'leave_application' => ['leave_type', 'from_date', 'to_date', 'payment_term'],
        'reimbursement' => ['employee_name', 'department', 'request_date'],
        'liquidation' => ['employee_name', 'department', 'request_date'],
        'vehicle_request' => ['car_available', 'employee_name', 'date', 'trip_type'],
    ];

    /**
     * Role map (align with the employees table):
     *   1 = Admin
     *   2 = Approver / Manager (Immediate Supervisor)
     *   3 = Regular Employee
     *   4 = Department Head / Finance Head
     *   5 = Checker / Approval Acquisition
     *   6 = Final Approver
     */

    private const FORM_CATEGORIES = [
        'admin'   => ['overtime_authorization', 'leave_application', 'vehicle_request'],
        'finance' => ['advance_payment', 'request_for_payment', 'reimbursement', 'liquidation'],
    ];

    private const PIPELINE_ADMIN = [
        'submit' => [
            'sequence' => 1, 'from' => 'draft', 'to' => 'submitted',
            'role_id' => 3, 'label' => 'Submitted',
        ],
        'checker-approval' => [
            'sequence' => 2, 'from' => 'submitted', 'to' => 'checker_approved',
            'role_id' => 2, 'label' => 'Checker Approval',
        ],
        'review-approval' => [
            'sequence' => 3, 'from' => 'checker_approved', 'to' => 'department_reviewed',
            'role_id' => 4, 'label' => 'Review Approval',
        ],
        'grant-approval' => [
            'sequence' => 4, 'from' => 'department_reviewed', 'to' => 'final_approved',
            'role_id' => 6, 'label' => 'Grant Approval Request',
        ],
        'complete' => [
            'sequence' => 5, 'from' => 'final_approved', 'to' => 'completed',
            'role_id' => 6, 'label' => 'Completed',
        ],
    ];

    private const PIPELINE_FINANCE = [
        'submit' => [
            'sequence' => 1, 'from' => 'draft', 'to' => 'submitted',
            'role_id' => 3, 'label' => 'Submitted',
        ],
        'checker-approval' => [
            'sequence' => 2, 'from' => 'submitted', 'to' => 'checker_approved',
            'role_id' => 2, 'label' => 'Checker Approval',
        ],
        'process-approval' => [
            'sequence' => 3, 'from' => 'checker_approved', 'to' => 'process_approved',
            'role_id' => 5, 'label' => 'Process Approval',
        ],
        'evaluation-approval' => [
            'sequence' => 4, 'from' => 'process_approved', 'to' => 'finance_reviewed',
            'role_id' => 4, 'label' => 'Evaluation Approval',
        ],
        'grant-approval' => [
            'sequence' => 5, 'from' => 'finance_reviewed', 'to' => 'final_approved',
            'role_id' => 6, 'label' => 'Grant Approval Request',
        ],
        'complete' => [
            'sequence' => 6, 'from' => 'final_approved', 'to' => 'completed',
            'role_id' => 6, 'label' => 'Completed',
        ],
    ];

    private function getPipeline(string $formType): array {
        return in_array($formType, self::FORM_CATEGORIES['finance'], true)
            ? self::PIPELINE_FINANCE
            : self::PIPELINE_ADMIN;
    }

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
                WHERE f.form_type = ?
                  AND (
                    f.submitted_by = ?
                    OR EXISTS (
                        SELECT 1 FROM approvals a
                        WHERE a.form_id = f.id AND a.approver_id = ?
                    )
                  )
                ORDER BY f.created_at DESC LIMIT 50'
            );
            $stmt->execute([$type, $userId, $userId]);
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
        $pageTitle = \App\Helpers\FormLabels::get($type);

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

        $fields      = $this->fields[$type];
        $formType    = $type;
        $noSuffix    = ['list', 'show', 'request_for_payment'];
        $viewName    = in_array($type, $noSuffix) ? $type : "{$type}_form";
        $pageTitle   = \App\Helpers\FormLabels::get($type);

        $departments = db()->query(
            'SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department'
        )->fetchAll(PDO::FETCH_COLUMN);

        $currentUser = $_SESSION['user_name'] ?? '';
        $currentDept = $_SESSION['department'] ?? '';

        // Breadcrumb for form creation pages
        $breadcrumbs = [
            ['label' => 'New Request'],
            ['label' => $pageTitle],
        ];

        $this->render("forms/{$viewName}", compact(
            'fields', 'formType', 'slug', 'pageTitle',
            'departments', 'currentUser', 'currentDept', 'breadcrumbs'
        ));
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

        $pipeline = $this->getPipeline($form['form_type']);
        $statusToAction = [];
        foreach ($pipeline as $action => $step) {
            if ($step['from'] !== '*') {
                $statusToAction[$step['from']] = $action;
            }
        }
        $nextAction = $statusToAction[$form['status']] ?? null;

        $canAct    = $this->canActOnForm($form, $approvalSteps);
        $data      = json_decode($form['data'], true) ?? [];
        $formLabel = \App\Helpers\FormLabels::all();
        $typeLabel = \App\Helpers\FormLabels::get($form['form_type']);
        $pageTitle = $typeLabel . ' #' . $id;

        // Breadcrumb
        $breadcrumbs = [
            ['label' => $typeLabel, 'url' => '/processing-system/public/forms/' . array_search($form['form_type'], $this->typeMap)],
            ['label' => '#' . $id],
        ];

        $this->render('forms/show', compact(
            'form', 'approvalSteps', 'canAct', 'data',
            'pageTitle', 'nextAction', 'breadcrumbs'
        ));
    }

    // ----------------------------------------------------------------
    // POST /forms/{id}/approve/{action}
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
        $userId  = (int) $_SESSION['user_id'];
        $roleId  = (int) $_SESSION['role_id'];

        $allowedRoles = [1, 2, 4, 5, 6];
        if (!in_array($roleId, $allowedRoles, true)) {
            $_SESSION['error'] = 'You are not authorised to reject this form.';
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        // Non-admins must own the current pending step
        if ($roleId !== 1) {
            $pendingStep = db()->prepare(
                'SELECT id, approver_id FROM approvals
                 WHERE form_id = ? AND status = \'pending\' ORDER BY sequence LIMIT 1'
            );
            $pendingStep->execute([$id]);
            $step = $pendingStep->fetch(PDO::FETCH_ASSOC);

            if (!$step) {
                $_SESSION['error'] = 'No pending approval step found for this form.';
                header("Location: /processing-system/public/forms/view/{$id}");
                exit;
            }

            // FIX: verify both role AND assigned approver_id
            if ((int)$step['approver_id'] !== $userId) {
                $_SESSION['error'] = 'You are not the assigned approver for the current stage.';
                header("Location: /processing-system/public/forms/view/{$id}");
                exit;
            }
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
            $this->sendRejectionNotification($id, $remarks);

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Rejection failed. Please try again.';
        }

        header("Location: /processing-system/public/forms/view/{$id}");
        exit;
    }

    // GET /my-submissions
    public function mySubmissions(): void {
        $userId     = $_SESSION['user_id'];
        $statusFilter = $_GET['status'] ?? '';

        $stmt = db()->prepare(
            "SELECT f.id, f.form_type, f.status, f.created_at, e.full_name,
                    (SELECT MIN(sequence) FROM approvals WHERE form_id = f.id AND status = 'pending') AS current_step
            FROM forms f JOIN employees e ON e.id = f.submitted_by
            WHERE f.submitted_by = ?
            ORDER BY f.created_at DESC LIMIT 50"
        );
        $stmt->execute([$userId]);
        $forms     = $stmt->fetchAll();
        $formLabel = \App\Helpers\FormLabels::all();
        $pageTitle = 'My Submissions';
        $breadcrumbs = [['label' => 'My Submissions']];

        define('BASE_LOADED', true);
        ob_start();
        require __DIR__ . '/../../views/forms/my_submissions.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/base.php';
    }

    // GET /requests — admin: all forms
    public function allRequests(): void {
        $stmt = db()->prepare(
            'SELECT f.id, f.form_type, f.status, f.created_at, e.full_name, e.department
            FROM forms f JOIN employees e ON e.id = f.submitted_by
            WHERE f.status NOT IN ("draft","cancelled")
            ORDER BY f.created_at DESC LIMIT 100'
        );
        $stmt->execute();
        $forms     = $stmt->fetchAll();
        $formLabel = \App\Helpers\FormLabels::all();
        $pageTitle = 'All Requests';
        $breadcrumbs = [['label' => 'All Requests']];

        define('BASE_LOADED', true);
        ob_start();
        require __DIR__ . '/../../views/forms/all_requests.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/base.php';
    }

    // ================================================================
    // PRIVATE
    // ================================================================

    private function processApproval(int $id, string $action): void {
        \App\Helpers\Csrf::verify();

        $form    = $this->findForm($id);
        $remarks = trim($_POST['remarks'] ?? '');
        $userId  = (int) $_SESSION['user_id'];
        $roleId  = (int) $_SESSION['role_id'];
        $isAdmin = $roleId === 1;

        $pipeline = $this->getPipeline($form['form_type']);
        if (!isset($pipeline[$action])) {
            $_SESSION['error'] = "Unknown approval action: '{$action}'.";
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        $step = $pipeline[$action];

        if ($step['from'] !== '*' && $form['status'] !== $step['from']) {
            $_SESSION['error'] = sprintf(
                "Cannot perform '%s': form is currently '%s', expected '%s'.",
                $action, $form['status'], $step['from']
            );
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        if (in_array($form['status'], ['completed', 'rejected'], true)) {
            $_SESSION['error'] = 'This form is already finalized.';
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        // ── Find the matching pending approval row first ──────────────
        // FIX: check the assigned approver_id BEFORE the role check, so
        //      two users with the same role cannot act on each other's steps.
        $approvalRow = db()->prepare(
            'SELECT * FROM approvals WHERE form_id = ? AND sequence = ? AND status = \'pending\' LIMIT 1'
        );
        $approvalRow->execute([$id, $step['sequence']]);
        $approval = $approvalRow->fetch();

        if (!$isAdmin && $action !== 'submit') {
            // Must have a row AND it must be assigned to this user
            if (!$approval || (int)$approval['approver_id'] !== $userId) {
                $_SESSION['error'] = 'No pending approval step found for you at this stage.';
                header("Location: /processing-system/public/forms/view/{$id}");
                exit;
            }
        }

        // Role check (after approver check so errors are specific)
        $actorAllowed = $isAdmin || $action === 'submit' || $roleId === $step['role_id'];
        if (!$actorAllowed) {
            $_SESSION['error'] = 'You are not authorized to perform this action.';
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        if ($action === 'submit' && !$isAdmin && (int)$form['submitted_by'] !== $userId) {
            $_SESSION['error'] = 'Only the form owner can submit this form.';
            header("Location: /processing-system/public/forms/view/{$id}");
            exit;
        }

        // ── Optional file upload ──────────────────────────────────────
        $uploadedFilePath = null;
        if (!empty($_FILES['approval_file']['tmp_name'])) {
            $file     = $_FILES['approval_file'];
            $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $maxBytes = 5 * 1024 * 1024;
            $finfo    = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, $allowed, true)) {
                $_SESSION['error'] = 'Only images and PDF files are allowed.';
                header("Location: /processing-system/public/forms/view/{$id}");
                exit;
            }
            if ($file['size'] > $maxBytes) {
                $_SESSION['error'] = 'File must be under 5 MB.';
                header("Location: /processing-system/public/forms/view/{$id}");
                exit;
            }
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $destDir  = __DIR__ . '/../../storage/approvals/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            $fileName = sprintf('%d_%d_%s.%s', $id, time(), bin2hex(random_bytes(4)), $ext);
            if (!move_uploaded_file($file['tmp_name'], $destDir . $fileName)) {
                $_SESSION['error'] = 'File upload failed. Please try again.';
                header("Location: /processing-system/public/forms/view/{$id}");
                exit;
            }
            $uploadedFilePath = 'storage/approvals/' . $fileName;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            if ($approval) {
                $pdo->prepare(
                    "UPDATE approvals
                     SET status = 'approved', remarks = ?, file_path = ?, approved_at = NOW()
                     WHERE id = ?"
                )->execute([
                    $remarks ?: ($isAdmin ? '(Admin override)' : $step['label']),
                    $uploadedFilePath,
                    $approval['id'],
                ]);
            }

            $pdo->prepare('UPDATE forms SET status = ? WHERE id = ?')
                ->execute([$step['to'], $id]);

            $this->audit(
                'form_' . str_replace('-', '_', $action), 'form', $id,
                ['status' => $form['status']],
                ['status' => $step['to'], 'remarks' => $remarks]
            );

            $pdo->commit();
            $_SESSION['success'] = $step['label'] . ' recorded successfully.';

            // Invalidate session pending-count cache so sidebar updates immediately
            unset($_SESSION["pending_count_{$userId}"], $_SESSION["pending_count_ts_{$userId}"]);

            $this->sendPipelineNotifications($id, $action, $step, $remarks);

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Action failed. Please try again.';
        }

        header("Location: /processing-system/public/forms/view/{$id}");
        exit;
    }

    private function store(string $type, string $slug): void {
        \App\Helpers\Csrf::verify();

        $isSavingDraft = isset($_POST['save_draft']);
        $required = $this->fields[$type];
        $data = [];

        if (!$isSavingDraft) {
            foreach ($required as $field) {
                $val = $_POST[$field] ?? '';
                if (is_string($val)) $val = trim($val);
                if ($val === '' || (is_array($val) && empty(array_filter($val)))) {
                    $_SESSION['error'] = "Field '{$field}' is required.";
                    header("Location: /processing-system/public/forms/{$slug}/create");
                    exit;
                }
            }
        }

        foreach ($_POST as $key => $val) {
            if (in_array($key, ['csrf_token', 'save_draft'], true)) continue;
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
                "INSERT INTO forms (form_type, status, submitted_by, data)
                VALUES (?, 'draft', ?, ?)"
            );
            $stmt->execute([$type, $_SESSION['user_id'], json_encode($data)]);
            $formId = (int) $pdo->lastInsertId();

            $this->seedApprovalRows($pdo, $formId, $type, $data, (int)$_SESSION['user_id']);
            $this->audit('form_created', 'form', $formId, null, ['type' => $type, 'status' => 'draft']);

            $pdo->commit();

            $_SESSION['success'] = $isSavingDraft
                ? 'Draft saved. You can continue editing or submit when ready.'
                : 'Form saved as draft. Review and submit it for approval.';
            header("Location: /processing-system/public/forms/view/{$formId}");
            exit;

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Submission failed: ' . $e->getMessage();
            header("Location: /processing-system/public/forms/{$slug}/create");
            exit;
        }
    }

    private function seedApprovalRows(\PDO $pdo, int $formId, string $type, array $data, int $submitterId): void {
        $pipeline              = $this->getPipeline($type);
        $stagesNeedingApprover = array_filter($pipeline, fn($step) => $step['sequence'] >= 2);
        $insert = $pdo->prepare(
            "INSERT INTO approvals (form_id, approver_id, sequence, status) VALUES (?, ?, ?, 'pending')"
        );
        foreach ($stagesNeedingApprover as $action => $step) {
            $approver = $this->resolveApproverByRole($pdo, $step['role_id'], $data, $submitterId);
            if (!$approver) {
                throw new \RuntimeException("No active approver found for stage '{$step['label']}'. Please ensure your supervisor and department approvers are correctly configured.");
            }
            $insert->execute([$formId, $approver, $step['sequence']]);
        }
    }

    private function resolveApproverByRole(\PDO $pdo, int $roleId, array $data, int $submitterId): ?int {
        // Role 2 = Immediate Supervisor: use direct link
        if ($roleId === 2) {
            $stmt = $pdo->prepare(
                'SELECT supervisor_id FROM employees WHERE id = ? AND is_active = 1'
            );
            $stmt->execute([$submitterId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['supervisor_id'] ? (int) $row['supervisor_id'] : null;
        }

        // All other roles: workload-balanced, filtered by department
        $dept = $data['department'] ?? null;
        $sql  = 'SELECT e.id, COUNT(a.id) AS workload
                 FROM employees e
                 LEFT JOIN approvals a ON a.approver_id = e.id AND a.status = \'pending\'
                 WHERE e.role_id = :role AND e.is_active = 1';
        $params = [':role' => $roleId];

        if ($dept) {
            $sql .= ' AND e.department = :dept';
            $params[':dept'] = $dept;
        }
        $sql .= ' GROUP BY e.id ORDER BY workload ASC, e.id ASC LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int) $row['id'] : null;
    }

    /**
     * Determine whether the current user may act on this form right now.
     *
     * FIX: The previous implementation returned true for ANY pending row
     * belonging to the user, regardless of sequence position.  Because all
     * future approval rows are seeded as 'pending' at form creation, a Role 6
     * user always had a pending row — so canActOnForm returned true from the
     * moment the form was submitted, causing the "Your Action" card to render
     * on the form detail page even when it was not their turn.
     *
     * The fix: first find the MINIMUM pending sequence for this form (the
     * active stage).  A user only qualifies if their pending row sits exactly
     * at that minimum sequence.  Every other pending row is a future stage and
     * must remain invisible to the user until it becomes the active one.
     *
     * The server-side status gate in processApproval() already blocks
     * out-of-order submissions, but this fix removes the misleading UI state
     * so approvers never see an action card they cannot legitimately use.
     */
    private function canActOnForm(array $form, array $steps): bool {
        if (in_array($form['status'], ['completed', 'rejected'], true)) return false;
        if ($_SESSION['role_id'] == 1) return true;

        $userId = (int) $_SESSION['user_id'];

        // Form owner may submit their own draft.
        if ($form['status'] === 'draft' && (int)$form['submitted_by'] === $userId) return true;

        // Determine the lowest pending sequence — that is the only active stage.
        $pendingSequences = array_column(
            array_filter($steps, fn($s) => $s['status'] === 'pending'),
            'sequence'
        );
        if (empty($pendingSequences)) return false;
        $activeSequence = min($pendingSequences);

        // The user may act only if they own the row AT the active sequence.
        foreach ($steps as $step) {
            if (
                (int)$step['approver_id'] === $userId
                && $step['status']         === 'pending'
                && (int)$step['sequence']  === (int)$activeSequence
            ) {
                return true;
            }
        }

        return false;
    }

    private function findForm(int $id): array {
        $stmt = db()->prepare(
            'SELECT id, form_type, status, data, submitted_by, created_at, updated_at FROM forms WHERE id = ?'
        );
        $stmt->execute([$id]);
        $form = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$form) {
            return $this->renderError(404, 'Not Found', 'The form you are looking for does not exist.');
        }
        if ($_SESSION['role_id'] == 3 && $form['submitted_by'] != $_SESSION['user_id']) {
            return $this->renderError(403, 'Access Denied', 'You do not have permission to view this form.');
        }
        return $form;
    }

    private function sendPipelineNotifications(int $formId, string $action, array $step, string $remarks): void {
        try {
            $pdo     = db();
            $formRow = $pdo->prepare(
                'SELECT f.form_type, f.status, f.submitted_by,
                        e.full_name AS submitter_name, e.email AS submitter_email
                 FROM forms f JOIN employees e ON e.id = f.submitted_by
                 WHERE f.id = ?'
            );
            $formRow->execute([$formId]);
            $form = $formRow->fetch(\PDO::FETCH_ASSOC);
            if (!$form) return;

            $submittedBy = (int) $form['submitted_by'];
            $formLabel   = \App\Helpers\FormLabels::get($form['form_type']);
            $stageName   = $step['label'];
            $newStatus   = $step['to'];

            $outcome = match($newStatus) {
                'completed'      => 'completed',
                'final_approved' => 'final_approved',
                'rejected'       => 'rejected',
                default          => 'approved_step',
            };

            $submitterMsg = match($outcome) {
                'completed'      => "Your {$formLabel} #{$formId} has been fully completed.",
                'final_approved' => "Your {$formLabel} #{$formId} reached final approval.",
                'rejected'       => "Your {$formLabel} #{$formId} was rejected at {$stageName}.",
                default          => "Your {$formLabel} #{$formId} passed {$stageName}.",
            };
            $submitterType = match($outcome) {
                'completed', 'final_approved' => 'success',
                'rejected'                    => 'danger',
                default                       => 'info',
            };

            \App\Controllers\NotificationController::create(
                $submittedBy, $submitterMsg, $submitterType, $formId
            );
            \App\Services\NotificationService::notifySubmitter(
                $form['submitter_email'], $form['submitter_name'],
                $formLabel, $formId, $outcome, $stageName, $remarks
            );

            if (!in_array($newStatus, ['completed', 'rejected'], true)) {
                $nextRow = $pdo->prepare(
                    'SELECT a.approver_id, e.email, e.full_name, a.sequence
                     FROM approvals a JOIN employees e ON e.id = a.approver_id
                     WHERE a.form_id = ? AND a.status = \'pending\'
                     ORDER BY a.sequence ASC LIMIT 1'
                );
                $nextRow->execute([$formId]);
                $nextApprover = $nextRow->fetch(\PDO::FETCH_ASSOC);
                if ($nextApprover) {
                    $nextStageName = \App\Helpers\FormLabels::stepLabel((int)$nextApprover['sequence']);
                    \App\Controllers\NotificationController::create(
                        (int) $nextApprover['approver_id'],
                        "{$formLabel} #{$formId} from {$form['submitter_name']} requires your approval at {$nextStageName}.",
                        'warning', $formId
                    );
                    \App\Services\NotificationService::notifyNextApprover(
                        $formId, $nextApprover['email'], $nextApprover['full_name'],
                        $formLabel, $form['submitter_name'], $nextStageName
                    );
                }
            }
        } catch (\Throwable $e) {
            error_log('[FormController] Notification error: ' . $e->getMessage());
        }
    }

    private function sendRejectionNotification(int $formId, string $remarks): void {
        try {
            $pdo = db();
            $row = $pdo->prepare(
                'SELECT f.form_type, f.submitted_by,
                        e.full_name AS submitter_name, e.email AS submitter_email
                 FROM forms f JOIN employees e ON e.id = f.submitted_by
                 WHERE f.id = ?'
            );
            $row->execute([$formId]);
            $form = $row->fetch(\PDO::FETCH_ASSOC);
            if (!$form) return;

            $stageRow = $pdo->prepare(
                'SELECT sequence FROM approvals
                 WHERE form_id = ? AND status = \'rejected\'
                 ORDER BY sequence DESC LIMIT 1'
            );
            $stageRow->execute([$formId]);
            $stageData = $stageRow->fetch(\PDO::FETCH_ASSOC);
            $stageName = \App\Helpers\FormLabels::stepLabel((int)($stageData['sequence'] ?? 0));
            $typeLabel = \App\Helpers\FormLabels::get($form['form_type']);

            $rejectMsg = "Your {$typeLabel} #{$formId} was rejected at {$stageName}"
                       . ($remarks ? ": \"{$remarks}\"" : '.');
            \App\Controllers\NotificationController::create(
                (int) $form['submitted_by'], $rejectMsg, 'danger', $formId
            );
            \App\Services\NotificationService::notifySubmitter(
                $form['submitter_email'], $form['submitter_name'],
                $typeLabel, $formId, 'rejected', $stageName, $remarks
            );
        } catch (\Throwable $e) {
            error_log('[FormController] Rejection notification error: ' . $e->getMessage());
        }
    }

    private function renderError(int $code, string $title, string $message): never {
        http_response_code($code);
        $errorCode    = $code;
        $errorTitle   = $title;
        $errorMessage = $message;
        $pageTitle    = "{$code} — {$title}";
        define('BASE_LOADED', true);
        ob_start();
        require __DIR__ . '/../../views/errors/error.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/base.php';
        exit;
    }

    private function audit(string $action, string $entity, int $entityId, ?array $old, ?array $new): void {
        db()->prepare(
            'INSERT INTO audit_logs (performed_by, action, entity_type, entity_id, old_values, new_values, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $_SESSION['user_id'], $action, $entity, $entityId,
            $old ? json_encode($old) : null,
            $new ? json_encode($new)  : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    private function resolveType(string $slug): string {
        if (!isset($this->typeMap[$slug])) {
            return $this->renderError(404, 'Not Found', 'Unknown form type.');
        }
        return $this->typeMap[$slug];
    }

    private function render(string $view, array $vars = []): void {
        $allowed = [
            'forms/list', 'forms/show',
            'forms/advance_payment_form', 'forms/overtime_authorization_form',
            'forms/request_for_payment', 'forms/leave_application_form',
            'forms/reimbursement_form', 'forms/liquidation_form',
            'forms/vehicle_request_form',
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
        if (!isset($pageTitle)) $pageTitle = 'Processing System';
        ob_start();
        require $fullPath;
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/base.php';
    }
}