<?php
class FormController {
    private array $typeMap = [
        'advance-payment'        => 'advance_payment',
        'overtime-authorization' => 'overtime_authorization',
        'request-for-payment'    => 'request_for_payment',
        'leave-application'      => 'leave_application',
        'reimbursement'          => 'reimbursement',
        'liquidation'            => 'liquidation',
        'vehicle-request'        => 'vehicle_request',
        'overtime'               => 'overtime_authorization',
        'request-payment'        => 'request_for_payment',
        'leave'                  => 'leave_application',
    ];

    private array $fields = [
        'advance_payment'        => ['purpose', 'payment_type', 'payee', 'date'],
        'overtime_authorization' => ['employee_name', 'department', 'request_date'],
        'request_for_payment'    => ['payee', 'payment_type', 'purpose', 'date'],
        'leave_application'      => ['leave_type', 'from_date', 'to_date', 'payment_term'],
        'reimbursement'          => ['employee_name', 'department', 'request_date'],
        'liquidation'            => ['employee_name', 'department', 'request_date'],
        'vehicle_request'        => ['car_available', 'employee_name', 'date', 'trip_type'],
    ];

    private const ADMIN_APPROVER_COVERS = [2, 4, 6];

    private const ADMIN_APPROVER_STANDIN_COVERAGE = [
        'vehicle_request'        => [2, 4, 6],
        'leave_application'      => [4],
        'overtime_authorization' => [4],
    ];

    private const FINANCE_HEAD_ROLE   = 8;
    private const HR_VERIFIER_ROLE    = 9;
    private const ADMIN_APPROVER_ROLE = 7;
    private const REIMB_LIQUID_TYPES  = ['reimbursement', 'liquidation'];

    private const FORM_CATEGORIES = [
        'admin'        => ['overtime_authorization', 'leave_application', 'vehicle_request'],
        'finance'      => ['advance_payment', 'request_for_payment'],
        'reimb_liquid' => ['reimbursement', 'liquidation'],
    ];

    private const PIPELINE_ADMIN = [
        'submit'          => ['sequence' => 1, 'from' => 'draft',                  'to' => 'submitted',              'role_id' => 3, 'label' => 'Submitted'],
        'checker-approval'=> ['sequence' => 2, 'from' => 'submitted',              'to' => 'immediatehead_approved', 'role_id' => 2, 'label' => 'Immediate Head Approval'],
        'review-approval' => ['sequence' => 3, 'from' => 'immediatehead_approved', 'to' => 'department_reviewed',    'role_id' => 4, 'label' => 'Review Approval'],
        'grant-approval'  => ['sequence' => 4, 'from' => 'department_reviewed',    'to' => 'completed',              'role_id' => 6, 'label' => 'Grant Approval Request'],
    ];

    private const PIPELINE_FINANCE = [
        'submit'              => ['sequence' => 1, 'from' => 'draft',                  'to' => 'submitted',              'role_id' => 3, 'label' => 'Submitted'],
        'checker-approval'    => ['sequence' => 2, 'from' => 'submitted',              'to' => 'immediatehead_approved', 'role_id' => 2, 'label' => 'Immediate Head Approval'],
        'process-approval'    => ['sequence' => 3, 'from' => 'immediatehead_approved', 'to' => 'process_approved',       'role_id' => 5, 'label' => 'Process Approval'],
        'evaluation-approval' => ['sequence' => 4, 'from' => 'process_approved',       'to' => 'finance_reviewed',       'role_id' => 8, 'label' => 'Evaluation Approval'],
        'grant-approval'      => ['sequence' => 5, 'from' => 'finance_reviewed',       'to' => 'completed',              'role_id' => 6, 'label' => 'Grant Approval Request'],
    ];

    private const PIPELINE_REIMB_LIQUID = [
        'submit'              => ['sequence' => 1, 'from' => 'draft',                  'to' => 'submitted',              'role_id' => 3, 'label' => 'Submitted'],
        'checker-approval'    => ['sequence' => 2, 'from' => 'submitted',              'to' => 'immediatehead_approved', 'role_id' => 2, 'label' => 'Immediate Head Approval'],
        'process-approval'    => ['sequence' => 3, 'from' => 'immediatehead_approved', 'to' => 'process_approved',       'role_id' => 9, 'label' => 'Process (Accounting Checking)'],
        'evaluation-approval' => ['sequence' => 4, 'from' => 'process_approved',       'to' => 'finance_reviewed',       'role_id' => 8, 'label' => 'Evaluation Approval'],
        'grant-approval'      => ['sequence' => 5, 'from' => 'finance_reviewed',       'to' => 'completed',              'role_id' => 6, 'label' => 'Grant Approval Request'],
    ];

    private function getPipeline(string $formType): array {
        if (in_array($formType, self::REIMB_LIQUID_TYPES, true)) return self::PIPELINE_REIMB_LIQUID;
        if (in_array($formType, self::FORM_CATEGORIES['finance'], true)) return self::PIPELINE_FINANCE;
        return self::PIPELINE_ADMIN;
    }

    private function adminApproverStandsInFor(string $formType, int $stageRoleId): bool {
        $covered = self::ADMIN_APPROVER_STANDIN_COVERAGE[$formType] ?? [];
        return in_array($stageRoleId, $covered, true);
    }

    private function masterApproverStandsInFor(string $formType, int $stageRoleId): bool {
        return $stageRoleId === 2;
    }

    private function roleSatisfiesStage(int $actorRole, int $requiredRole, string $formType = ''): bool {
        if ($actorRole === $requiredRole) return true;
        if ($actorRole === 7 && in_array($requiredRole, self::ADMIN_APPROVER_COVERS, true)) return true;
        if ($actorRole === 4 && $this->masterApproverStandsInFor($formType, $requiredRole)) return true;
        return false;
    }

    // ─── PUBLIC ROUTES ────────────────────────────────────────────────────────

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
        } elseif (in_array($roleId, [2, 4, 5, 6, 7, 8, 9])) {
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
        $departments = db()->query('SELECT name FROM departments ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
        $currentUser = $_SESSION['user_name'] ?? '';
        $currentDept = $_SESSION['department'] ?? '';
        $breadcrumbs = [['label' => 'New Request'], ['label' => $pageTitle]];

        $this->render("forms/{$viewName}", compact(
            'fields', 'formType', 'slug', 'pageTitle',
            'departments', 'currentUser', 'currentDept', 'breadcrumbs'
        ));
    }

    public function show(int $id): void {
        $form = $this->findForm($id);

        $approvals = db()->prepare(
            'SELECT a.*, e.full_name, e.role_id AS approver_role_id FROM approvals a
             JOIN employees e ON e.id = a.approver_id
             WHERE a.form_id = ? ORDER BY a.sequence'
        );
        $approvals->execute([$id]);
        $approvalSteps = $approvals->fetchAll();

        $pipeline       = $this->getPipeline($form['form_type']);
        $statusToAction = [];
        foreach ($pipeline as $action => $step) {
            if ($step['from'] !== '*') $statusToAction[$step['from']] = $action;
        }
        $nextAction = $statusToAction[$form['status']] ?? null;
        $canAct     = $this->canActOnForm($form, $approvalSteps);

        $userId           = (int) $_SESSION['user_id'];
        $roleId           = (int) $_SESSION['role_id'];
        $isOwner          = $roleId === 1 || (int) $form['submitted_by'] === $userId;
        $editableStatuses = ['draft', 'submitted', 'rejected'];
        $canEdit          = ($isOwner && in_array($form['status'], $editableStatuses, true))
            || $this->canEditAsProcessApprover($form);

        $data        = json_decode($form['data'], true) ?? [];
        $formLabel   = \App\Helpers\FormLabels::all();
        $typeLabel   = \App\Helpers\FormLabels::get($form['form_type']);
        $pageTitle   = $typeLabel . ' #' . $id;
        $breadcrumbs = [
            ['label' => $typeLabel, 'url' => url('forms/' . (array_search($form['form_type'], $this->typeMap) ?: 'list'))],
            ['label' => '#' . $id],
        ];

        $this->render('forms/show', compact(
            'form', 'approvalSteps', 'canAct', 'canEdit', 'data',
            'pageTitle', 'nextAction', 'breadcrumbs'
        ));
    }

    public function search(): void {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { echo json_encode([]); exit; }
        $like = '%' . $q . '%';
        $stmt = db()->prepare(
            'SELECT f.id, f.form_type, f.status, f.created_at, e.full_name AS submitted_by
             FROM forms f JOIN employees e ON e.id = f.submitted_by
             WHERE f.form_type LIKE ? OR e.full_name LIKE ? OR CAST(f.id AS CHAR) LIKE ?
             ORDER BY f.created_at DESC LIMIT 8'
        );
        $stmt->execute([$like, $like, $like]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    public function approve(int $id, string $action): void {
        $this->processApproval($id, $action);
    }

    public function reject(int $id): void {
        \App\Helpers\Csrf::verify();

        $form    = $this->findForm($id);
        $remarks = trim($_POST['remarks'] ?? '');
        $userId  = (int) $_SESSION['user_id'];
        $roleId  = (int) $_SESSION['role_id'];

        $allowedRoles = [1, 2, 4, 5, 6, 7, 8, 9];
        if (!in_array($roleId, $allowedRoles, true)) {
            $_SESSION['error'] = 'You are not authorised to reject this form.';
            header("Location: " . url("forms/view/{$id}")); exit;
        }

        $isAdminApproverStandIn  = $roleId === 7;
        $isMasterApproverStandIn = $roleId === 4;
        $isFinalApproverStandIn  = $roleId === 6;
        $isHrVerifierStandIn     = $roleId === 9;
        $isFinanceHeadStandIn    = $roleId === 8;

        if ($roleId !== 1) {
            $activeSeqStmt = db()->prepare(
                'SELECT MIN(sequence) FROM approvals WHERE form_id = ? AND status = \'pending\''
            );
            $activeSeqStmt->execute([$id]);
            $activeSequence = $activeSeqStmt->fetchColumn();

            if ($activeSequence === false) {
                $_SESSION['error'] = 'No pending approval step found for this form.';
                header("Location: " . url("forms/view/{$id}")); exit;
            }

            $pipeline       = $this->getPipeline($form['form_type']);
            $activeStepRole = null;
            foreach ($pipeline as $pStep) {
                if ((int) $pStep['sequence'] === (int) $activeSequence) {
                    $activeStepRole = (int) $pStep['role_id'];
                    break;
                }
            }

            if ($activeStepRole === null || !$this->adminApproverStandsInFor($form['form_type'], $activeStepRole)) {
                $isAdminApproverStandIn = false;
            }
            if ($activeStepRole === null || !$this->masterApproverStandsInFor($form['form_type'], $activeStepRole)) {
                $isMasterApproverStandIn = false;
            }
            if ($activeStepRole !== 6) $isFinalApproverStandIn = false;
            if ($activeStepRole !== 9) $isHrVerifierStandIn    = false;
            if ($activeStepRole !== 8) $isFinanceHeadStandIn   = false;

            if ($isAdminApproverStandIn || $isMasterApproverStandIn || $isFinalApproverStandIn || $isHrVerifierStandIn || $isFinanceHeadStandIn) {
                $myStep = db()->prepare(
                    'SELECT id FROM approvals WHERE form_id = ? AND sequence = ? AND status = \'pending\' LIMIT 1'
                );
                $myStep->execute([$id, $activeSequence]);
            } else {
                // role 5 and all direct-assigned roles: must own the pending row
                $myStep = db()->prepare(
                    'SELECT id FROM approvals WHERE form_id = ? AND sequence = ? AND approver_id = ? AND status = \'pending\' LIMIT 1'
                );
                $myStep->execute([$id, $activeSequence, $userId]);
            }

            $step = $myStep->fetch(PDO::FETCH_ASSOC);
            if (!$step) {
                $_SESSION['error'] = 'You are not the assigned approver for the current stage.';
                header("Location: " . url("forms/view/{$id}")); exit;
            }
        }

        if (in_array($form['status'], ['completed', 'rejected'], true)) {
            $_SESSION['error'] = 'This form is already finalised.';
            header("Location: " . url("forms/view/{$id}")); exit;
        }

        if ($remarks === '') {
            $_SESSION['error'] = 'A rejection reason is required.';
            header("Location: " . url("forms/view/{$id}")); exit;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            if ($roleId !== 1) {
                $pdo->prepare(
                    "UPDATE approvals SET status = 'rejected', approver_id = ?, remarks = ?, approved_at = NOW()
                     WHERE id = ? AND status = 'pending'"
                )->execute([$userId, $remarks, $step['id']]);
            }

            $pdo->prepare(
                "UPDATE approvals SET status = 'rejected', remarks = ?, approved_at = NOW()
                 WHERE form_id = ? AND status = 'pending'"
            )->execute([$remarks, $id]);

            $pdo->prepare("UPDATE forms SET status = 'rejected' WHERE id = ?")->execute([$id]);

            $this->audit('form_rejected', 'form', $id,
                ['status' => $form['status']],
                ['status' => 'rejected', 'remarks' => $remarks]
            );

            $pdo->commit();
            $_SESSION['success'] = 'Form rejected successfully.';
            $this->sendStatusNotification($id, 'rejected');

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Rejection failed. Please try again.';
        }

        header("Location: " . url("forms/view/{$id}")); exit;
    }

    public function submit(int $id): void {
        \App\Helpers\Csrf::verify();

        $form   = $this->findForm($id);
        $userId = (int) $_SESSION['user_id'];

        if ((int) $form['submitted_by'] !== $userId) {
            $_SESSION['error'] = 'Access denied.';
            header("Location: " . url("forms/view/{$id}")); exit;
        }

        if ($form['status'] !== 'draft') {
            $_SESSION['error'] = 'Only draft forms can be submitted.';
            header("Location: " . url("forms/view/{$id}")); exit;
        }

        db()->prepare("UPDATE forms SET status = 'submitted' WHERE id = ?")->execute([$id]);

        $this->audit('form_submitted', 'form', $id, ['status' => 'draft'], ['status' => 'submitted']);

        $_SESSION['success'] = 'Form submitted for approval.';
        $this->sendStatusNotification($id, 'submitted');
        header("Location: " . url("forms/view/{$id}")); exit;
    }

    public function update(int $id): void {
        \App\Helpers\Csrf::verify();

        $form   = $this->findForm($id);
        $userId = (int) $_SESSION['user_id'];
        $roleId = (int) $_SESSION['role_id'];

        $isOwner          = $roleId === 1 || (int) $form['submitted_by'] === $userId;
        $editableStatuses = ['draft', 'submitted', 'rejected'];

        if (!($isOwner && in_array($form['status'], $editableStatuses, true))
            && !$this->canEditAsProcessApprover($form)) {
            $_SESSION['error'] = 'You cannot edit this form at its current stage.';
            header("Location: " . url("forms/view/{$id}")); exit;
        }

        $data = $_POST;
        unset($data['csrf_token'], $data['_method']);

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $existing = $pdo->prepare('SELECT id FROM form_data WHERE form_id = ?');
            $existing->execute([$id]);

            if ($existing->fetch()) {
                $pdo->prepare('UPDATE form_data SET data = ? WHERE form_id = ?')
                    ->execute([json_encode($data), $id]);
            } else {
                $pdo->prepare('INSERT INTO form_data (form_id, data) VALUES (?, ?)')
                    ->execute([$id, json_encode($data)]);
            }

            $this->audit('form_updated', 'form', $id,
                ['data' => $form['data']],
                ['data' => json_encode($data)]
            );

            $pdo->commit();
            $_SESSION['success'] = 'Form updated successfully.';

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Update failed. Please try again.';
        }

        header("Location: " . url("forms/view/{$id}")); exit;
    }

    public function mySubmissions(): void {
        $userId = $_SESSION['user_id'];
        $stmt   = db()->prepare(
            "SELECT f.id, f.form_type, f.status, f.created_at, e.full_name,
                    (SELECT MIN(sequence) FROM approvals WHERE form_id = f.id AND status = 'pending') AS current_step
             FROM forms f JOIN employees e ON e.id = f.submitted_by
             WHERE f.submitted_by = ?
             ORDER BY f.created_at DESC LIMIT 50"
        );
        $stmt->execute([$userId]);
        $forms       = $stmt->fetchAll();
        $formLabel   = \App\Helpers\FormLabels::all();
        $pageTitle   = 'My Submissions';
        $breadcrumbs = [['label' => 'My Submissions']];

        define('BASE_LOADED', true);
        ob_start();
        require __DIR__ . '/../../views/forms/my_submissions.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/base.php';
    }

    public function allRequests(): void {
        if ((int) ($_SESSION['role_id'] ?? 0) !== 1) {
            $_SESSION['error'] = 'Access denied.';
            header('Location: ' . url('dashboard')); exit;
        }
        $stmt = db()->prepare(
            'SELECT f.id, f.form_type, f.status, f.created_at, e.full_name, e.department
             FROM forms f JOIN employees e ON e.id = f.submitted_by
             WHERE f.status NOT IN ("draft","cancelled")
             ORDER BY f.created_at DESC LIMIT 100'
        );
        $stmt->execute();
        $forms       = $stmt->fetchAll();
        $formLabel   = \App\Helpers\FormLabels::all();
        $pageTitle   = 'All Requests';
        $breadcrumbs = [['label' => 'All Requests']];

        define('BASE_LOADED', true);
        ob_start();
        require __DIR__ . '/../../views/forms/all_requests.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/base.php';
    }

    public function exportCompletedRequests(): void {
        if ((int) ($_SESSION['role_id'] ?? 0) !== 1) {
            $_SESSION['error'] = 'Access denied.';
            header('Location: ' . url('dashboard')); exit;
        }

        $format = strtolower($_GET['format'] ?? 'csv');
        if (!in_array($format, ['csv', 'xlsx', 'docx', 'pdf'], true)) $format = 'csv';

        $stmt = db()->prepare(
            'SELECT f.id, f.form_type, f.data, f.created_at, f.updated_at,
                    e.employee_code, e.full_name, e.department, e.company
             FROM forms f
             JOIN employees e ON e.id = f.submitted_by
             WHERE f.status = "completed"
             ORDER BY f.updated_at DESC'
        );
        $stmt->execute();
        $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="completed_requests_' . date('Ymd_His') . '.csv"');
            $out = fopen('php://output', 'w');
            if (!empty($forms)) {
                $firstData = json_decode($forms[0]['data'], true) ?? [];
                $headers   = array_merge(
                    ['ID', 'Form Type', 'Employee Code', 'Full Name', 'Department', 'Company', 'Submitted At', 'Completed At'],
                    array_keys($firstData)
                );
                fputcsv($out, $headers);
            }
            foreach ($forms as $row) {
                $data = json_decode($row['data'], true) ?? [];
                $line = [
                    $row['id'], $row['form_type'], $row['employee_code'],
                    $row['full_name'], $row['department'], $row['company'],
                    $row['created_at'], $row['updated_at'],
                ];
                foreach ($data as $v) $line[] = $v;
                fputcsv($out, $line);
            }
            fclose($out);
            exit;
        }

        $_SESSION['error'] = 'Only CSV export is currently supported.';
        header('Location: ' . url('dashboard')); exit;
    }

    // ─── CORE APPROVAL LOGIC ──────────────────────────────────────────────────

    private function processApproval(int $id, string $action): void {
        \App\Helpers\Csrf::verify();

        $form    = $this->findForm($id);
        $userId  = (int) $_SESSION['user_id'];
        $roleId  = (int) $_SESSION['role_id'];
        $remarks = trim($_POST['remarks'] ?? '');

        $pipeline = $this->getPipeline($form['form_type']);

        if (!isset($pipeline[$action])) {
            $_SESSION['error'] = 'Invalid action.';
            header("Location: " . url("forms/view/{$id}")); exit;
        }

        $step = $pipeline[$action];

        if ($form['status'] !== $step['from']) {
            $_SESSION['error'] = 'This form is not at the correct stage for this action.';
            header("Location: " . url("forms/view/{$id}")); exit;
        }

        if (!$this->roleSatisfiesStage($roleId, $step['role_id'], $form['form_type'])) {
            $_SESSION['error'] = 'You do not have the required role for this action.';
            header("Location: " . url("forms/view/{$id}")); exit;
        }

        $isAdminApproverStandIn  = ($roleId === 7 && $this->adminApproverStandsInFor($form['form_type'], $step['role_id']));
        $isMasterApproverStandIn = ($roleId === 4 && $this->masterApproverStandsInFor($form['form_type'], $step['role_id']));
        $isFinalApproverStandIn  = ($roleId === 6 && $step['role_id'] === 6);
        $isHrVerifierStandIn     = ($roleId === 9 && $step['role_id'] === 9);
        $isFinanceHeadStandIn    = ($roleId === 8 && $step['role_id'] === 8);

        if ($isAdminApproverStandIn || $isMasterApproverStandIn || $isFinalApproverStandIn || $isHrVerifierStandIn || $isFinanceHeadStandIn) {
            $approvalRow = db()->prepare(
                'SELECT id FROM approvals WHERE form_id = ? AND sequence = ? AND status = \'pending\' LIMIT 1'
            );
            $approvalRow->execute([$id, $step['sequence']]);
        } else {
            // role 5 and all direct-assigned roles: must own the row
            $approvalRow = db()->prepare(
                'SELECT id FROM approvals WHERE form_id = ? AND sequence = ? AND approver_id = ? AND status = \'pending\' LIMIT 1'
            );
            $approvalRow->execute([$id, $step['sequence'], $userId]);
        }

        $approvalRecord = $approvalRow->fetch(PDO::FETCH_ASSOC);

        if (!$approvalRecord && $roleId !== 1) {
            $_SESSION['error'] = 'You are not the assigned approver for this stage.';
            header("Location: " . url("forms/view/{$id}")); exit;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            if ($roleId !== 1 && $approvalRecord) {
                $pdo->prepare(
                    "UPDATE approvals SET status = 'approved', approver_id = ?, remarks = ?, approved_at = NOW()
                     WHERE id = ? AND status = 'pending'"
                )->execute([$userId, $remarks, $approvalRecord['id']]);
            }

            // Reimb/Liquid: after HR (role 9) approves, dynamically insert the AcquisitionChecker row
            if (
                in_array($form['form_type'], self::REIMB_LIQUID_TYPES, true) &&
                $step['role_id'] === 9 &&
                $step['to'] === 'process_approved'
            ) {
                $this->seedAcquisitionCheckerRow($id, $form, $step['sequence'] + 1);
            }

            $pdo->prepare("UPDATE forms SET status = ? WHERE id = ?")->execute([$step['to'], $id]);

            $this->audit('form_approved', 'form', $id,
                ['status' => $form['status']],
                ['status' => $step['to'], 'action' => $action]
            );

            $pdo->commit();
            $_SESSION['success'] = 'Form approved successfully.';
            $this->sendStatusNotification($id, $step['to']);

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Approval failed. Please try again.';
        }

        header("Location: " . url("forms/view/{$id}")); exit;
    }

    private function canActOnForm(array $form, array $approvalSteps): bool {
        $userId = (int) $_SESSION['user_id'];
        $roleId = (int) $_SESSION['role_id'];

        if ($roleId === 1) return true;
        if (in_array($form['status'], ['completed', 'rejected', 'cancelled'], true)) return false;

        $pendingStep = null;
        foreach ($approvalSteps as $s) {
            if ($s['status'] === 'pending') {
                $pendingStep = $s;
                break;
            }
        }
        if (!$pendingStep) return false;

        $pendingSeq = (int) $pendingStep['sequence'];

        // Direct assignment check — covers role 5 and any directly assigned approver
        foreach ($approvalSteps as $s) {
            if ($s['status'] === 'pending' && (int) $s['approver_id'] === $userId) {
                return true;
            }
        }

        // Stand-in checks for shared-queue roles
        $pipeline       = $this->getPipeline($form['form_type']);
        $activeStepRole = null;
        foreach ($pipeline as $pStep) {
            if ((int) $pStep['sequence'] === $pendingSeq) {
                $activeStepRole = (int) $pStep['role_id'];
                break;
            }
        }
        if ($activeStepRole === null) return false;

        // Role 7 (AdminApprover) stand-in
        if ($roleId === 7 && $this->adminApproverStandsInFor($form['form_type'], $activeStepRole)) return true;

        // Role 4 (MasterApprover) stand-in
        if ($roleId === 4 && $this->masterApproverStandsInFor($form['form_type'], $activeStepRole)) return true;

        // Shared-queue roles: 6, 8, 9
        if (in_array($roleId, [6, 8, 9], true) && $roleId === $activeStepRole) return true;

        return false;
    }

    private function findForm(int $id): array {
        $userId = (int) $_SESSION['user_id'];
        $roleId = (int) $_SESSION['role_id'];

        if ($roleId === 1) {
            $stmt = db()->prepare(
                'SELECT f.*, fd.data FROM forms f
                 LEFT JOIN form_data fd ON fd.form_id = f.id
                 WHERE f.id = ?'
            );
            $stmt->execute([$id]);
        } elseif (in_array($roleId, [2, 4, 6, 7, 8, 9], true)) {
            $stmt = db()->prepare(
                'SELECT f.*, fd.data FROM forms f
                 LEFT JOIN form_data fd ON fd.form_id = f.id
                 WHERE f.id = ?
                   AND (
                     f.submitted_by = ?
                     OR EXISTS (
                         SELECT 1 FROM approvals a
                         WHERE a.form_id = f.id
                           AND (a.approver_id = ? OR a.role_id IN (6,8,9))
                     )
                   )
                 LIMIT 1'
            );
            $stmt->execute([$id, $userId, $userId]);
        } elseif ($roleId === 5) {
            // Role 5: only forms where they are the assigned approver_id
            $stmt = db()->prepare(
                'SELECT f.*, fd.data FROM forms f
                 LEFT JOIN form_data fd ON fd.form_id = f.id
                 WHERE f.id = ?
                   AND (
                     f.submitted_by = ?
                     OR EXISTS (
                         SELECT 1 FROM approvals a
                         WHERE a.form_id = f.id AND a.approver_id = ?
                     )
                   )
                 LIMIT 1'
            );
            $stmt->execute([$id, $userId, $userId]);
        } else {
            $stmt = db()->prepare(
                'SELECT f.*, fd.data FROM forms f
                 LEFT JOIN form_data fd ON fd.form_id = f.id
                 WHERE f.id = ? AND f.submitted_by = ? LIMIT 1'
            );
            $stmt->execute([$id, $userId]);
        }

        $form = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$form) {
            $_SESSION['error'] = 'Form not found or access denied.';
            header('Location: ' . url('dashboard')); exit;
        }
        return $form;
    }

    private function canEditAsProcessApprover(array $form): bool {
        $userId = (int) $_SESSION['user_id'];
        $roleId = (int) $_SESSION['role_id'];

        $editableStatuses = ['immediatehead_approved', 'process_approved'];
        if (!in_array($form['status'], $editableStatuses, true)) return false;

        // Reimb/Liquid: only the specifically assigned approver_id can edit
        if (in_array($form['form_type'], self::REIMB_LIQUID_TYPES, true)) {
            $stmt = db()->prepare(
                'SELECT 1 FROM approvals
                 WHERE form_id = ? AND approver_id = ? AND status = \'pending\' LIMIT 1'
            );
            $stmt->execute([$form['id'], $userId]);
            return (bool) $stmt->fetchColumn();
        }

        // Finance pipeline: role 5 assigned to this form
        if (in_array($form['form_type'], self::FORM_CATEGORIES['finance'], true) && $roleId === 5) {
            $stmt = db()->prepare(
                'SELECT 1 FROM approvals
                 WHERE form_id = ? AND approver_id = ? AND status = \'pending\' LIMIT 1'
            );
            $stmt->execute([$form['id'], $userId]);
            return (bool) $stmt->fetchColumn();
        }

        return false;
    }

    private function resolveApproversByRole(int $roleId, int $submittedBy, string $formType): array {
        // Role 5 (AcquisitionChecker): use the one assigned to the submitter
        if ($roleId === 5) {
            $stmt = db()->prepare(
                'SELECT acquisition_checker_id FROM employees WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$submittedBy]);
            $assignedId = $stmt->fetchColumn();

            if ($assignedId) return [(int) $assignedId];

            // Fallback: workload-balanced among active role-5 employees
            $stmt = db()->prepare(
                'SELECT e.id FROM employees e
                 WHERE e.role_id = 5 AND e.is_active = 1
                 ORDER BY (
                     SELECT COUNT(*) FROM approvals a
                     JOIN forms f ON f.id = a.form_id
                     WHERE a.approver_id = e.id
                       AND a.status = \'pending\'
                       AND f.status NOT IN (\'completed\',\'rejected\',\'cancelled\')
                 ) ASC
                 LIMIT 1'
            );
            $stmt->execute();
            $id = $stmt->fetchColumn();
            return $id ? [(int) $id] : [];
        }

        // Shared-queue roles (6, 8, 9): one row per active member
        if (in_array($roleId, [6, 8, 9], true)) {
            $stmt = db()->prepare(
                'SELECT id FROM employees WHERE role_id = ? AND is_active = 1'
            );
            $stmt->execute([$roleId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // Role 2 (ImmediateHead): the submitter's assigned immediate head
        if ($roleId === 2) {
            $stmt = db()->prepare(
                'SELECT immediate_head_id FROM employees WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$submittedBy]);
            $headId = $stmt->fetchColumn();
            return $headId ? [(int) $headId] : [];
        }

        // Default: all active employees with this role
        $stmt = db()->prepare('SELECT id FROM employees WHERE role_id = ? AND is_active = 1');
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function seedApprovalRows(int $formId, string $formType, int $submittedBy): void {
        $pipeline = $this->getPipeline($formType);
        $pdo      = db();

        foreach ($pipeline as $step) {
            if ($step['role_id'] === 3) continue; // submitter step, skip
            // Role 5 on Reimb/Liquid is dynamic — seeded after HR approves
            if ($step['role_id'] === 5 && in_array($formType, self::REIMB_LIQUID_TYPES, true)) continue;

            $approverIds = $this->resolveApproversByRole($step['role_id'], $submittedBy, $formType);

            foreach ($approverIds as $approverId) {
                $pdo->prepare(
                    'INSERT INTO approvals (form_id, sequence, role_id, approver_id, status)
                     VALUES (?, ?, ?, ?, \'pending\')'
                )->execute([$formId, $step['sequence'], $step['role_id'], $approverId]);
            }
        }
    }

    private function seedAcquisitionCheckerRow(int $formId, array $form, int $sequence): void {
        $approverIds = $this->resolveApproversByRole(5, (int) $form['submitted_by'], $form['form_type']);
        if (empty($approverIds)) return;

        $pdo = db();
        // Shift rows at or after this sequence up by 1 to make room
        $pdo->prepare(
            'UPDATE approvals SET sequence = sequence + 1 WHERE form_id = ? AND sequence >= ?'
        )->execute([$formId, $sequence]);

        $pdo->prepare(
            'INSERT INTO approvals (form_id, sequence, role_id, approver_id, status)
             VALUES (?, ?, 5, ?, \'pending\')'
        )->execute([$formId, $sequence, $approverIds[0]]);
    }

    // ─── STORE ────────────────────────────────────────────────────────────────

    private function store(string $type, string $slug): void {
        \App\Helpers\Csrf::verify();

        $userId = (int) $_SESSION['user_id'];
        $data   = $_POST;
        unset($data['csrf_token'], $data['_method']);

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $pdo->prepare(
                'INSERT INTO forms (form_type, status, submitted_by) VALUES (?, \'draft\', ?)'
            )->execute([$type, $userId]);
            $formId = (int) $pdo->lastInsertId();

            $pdo->prepare(
                'INSERT INTO form_data (form_id, data) VALUES (?, ?)'
            )->execute([$formId, json_encode($data)]);

            $this->seedApprovalRows($formId, $type, $userId);

            $this->audit('form_created', 'form', $formId, [], ['form_type' => $type, 'status' => 'draft']);

            $pdo->commit();
            $_SESSION['success'] = 'Form submitted successfully.';
            header("Location: " . url("forms/view/{$formId}")); exit;

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed to submit form. Please try again.';
            header("Location: " . url("forms/{$slug}")); exit;
        }
    }

    // ─── NOTIFICATIONS ────────────────────────────────────────────────────────

    private function sendStatusNotification(int $formId, string $newStatus): void {
        try {
            $stmt = db()->prepare(
                'SELECT f.submitted_by, f.form_type, e.full_name
                 FROM forms f JOIN employees e ON e.id = f.submitted_by
                 WHERE f.id = ? LIMIT 1'
            );
            $stmt->execute([$formId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return;

            $label   = \App\Helpers\FormLabels::get($row['form_type']);
            $sLabel  = \App\Helpers\FormLabels::statusLabel($newStatus);
            $message = "{$label} #{$formId} is now {$sLabel}.";
            $type    = in_array($newStatus, ['rejected', 'cancelled'], true) ? 'danger'
                     : ($newStatus === 'completed' ? 'success' : 'info');

            \App\Controllers\NotificationController::create(
                (int) $row['submitted_by'], $message, $type, $formId
            );

            // Also notify the next pending approver if any
            $next = db()->prepare(
                'SELECT approver_id FROM approvals
                 WHERE form_id = ? AND status = \'pending\'
                 ORDER BY sequence ASC LIMIT 1'
            );
            $next->execute([$formId]);
            $nextApproverId = $next->fetchColumn();

            if ($nextApproverId && (int) $nextApproverId !== (int) $row['submitted_by']) {
                \App\Controllers\NotificationController::create(
                    (int) $nextApproverId,
                    "{$label} #{$formId} is awaiting your approval.",
                    'info',
                    $formId
                );
            }
        } catch (\Throwable $e) {
            error_log('[FormController] sendStatusNotification failed: ' . $e->getMessage());
        }
    }

    // ─── HELPERS ─────────────────────────────────────────────────────────────

    private function resolveType(string $slug): string {
        $type = $this->typeMap[$slug] ?? $slug;
        if (!isset($this->fields[$type])) {
            $_SESSION['error'] = 'Unknown form type.';
            header('Location: ' . url('dashboard')); exit;
        }
        return $type;
    }

    private function render(string $view, array $vars = []): void {
        extract($vars);
        define('BASE_LOADED', true);
        ob_start();
        require __DIR__ . "/../../views/{$view}.php";
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/base.php';
    }

    private function audit(string $action, string $entityType, int $entityId, array $before, array $after): void {
        try {
            db()->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, before_data, after_data, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            )->execute([
                $_SESSION['user_id'] ?? null,
                $action, $entityType, $entityId,
                json_encode($before), json_encode($after),
            ]);
        } catch (\Throwable $e) { /* non-fatal */ }
    }
}
