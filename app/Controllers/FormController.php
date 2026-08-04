<?php
class FormController {
    private array $typeMap = [
        'advance-payment' => 'advance_payment',
        'overtime-authorization' => 'overtime_authorization',
        'request-for-payment' => 'request_for_payment',
        'leave-application' => 'leave_application',
        'reimbursement' => 'reimbursement',
        'liquidation' => 'liquidation',
        'vehicle-request' => 'vehicle_request',
        'overtime' => 'overtime_authorization',
        'request-payment' => 'request_for_payment',
        'leave' => 'leave_application',
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

    private const ADMIN_APPROVER_COVERS = [2, 4, 6];

    private const ADMIN_APPROVER_STANDIN_COVERAGE = [
        'vehicle_request' => [2, 4, 6],
        'leave_application' => [4],
        'overtime_authorization' => [4],
    ];

    private const FINANCE_HEAD_ROLE = 8;
    private const HR_VERIFIER_ROLE = 9;
    private const REIMB_LIQUID_TYPES = ['reimbursement', 'liquidation'];

    private function adminApproverStandsInFor(string $formType, int $stageRoleId): bool {
        $covered = self::ADMIN_APPROVER_STANDIN_COVERAGE[$formType] ?? [];
        return in_array($stageRoleId, $covered, true);
    }

    // MasterApprover (role 4) may also act as Checker (role 2) on any form type.
    private function masterApproverStandsInFor(string $formType, int $stageRoleId): bool {
        return $stageRoleId === 2;
    }

    private function roleSatisfiesStage(int $actorRole, int $requiredRole, string $formType = ''): bool {
        if ($actorRole === $requiredRole) return true;
        if ($actorRole === 7 && in_array($requiredRole, self::ADMIN_APPROVER_COVERS, true)) return true;
        if ($actorRole === 5 && $requiredRole === 9) return true; // Accounting co-signs after HR
        if ($actorRole === 4 && $this->masterApproverStandsInFor($formType, $requiredRole)) return true;
        return false;
    }

    private const FORM_CATEGORIES = [
        'admin' => ['overtime_authorization', 'leave_application', 'vehicle_request'],
        'finance' => ['advance_payment', 'request_for_payment'],
        'reimb_liquid' => ['reimbursement', 'liquidation'],
    ];

    private const PIPELINE_ADMIN = [
        'submit' => ['sequence' => 1, 'from' => 'draft', 'to' => 'submitted', 'role_id' => 3, 'label' => 'Submitted'],
        'checker-approval'=> ['sequence' => 2, 'from' => 'submitted', 'to' => 'immediatehead_approved','role_id' => 2, 'label' => 'Immediate Head Approval'],
        'review-approval' => ['sequence' => 3, 'from' => 'immediatehead_approved','to' => 'department_reviewed',  'role_id' => 4, 'label' => 'Review Approval'],
        'grant-approval' => ['sequence' => 4, 'from' => 'department_reviewed',  'to' => 'completed',            'role_id' => 6, 'label' => 'Grant Approval Request'],
    ];

    private const PIPELINE_FINANCE = [
        'submit' => ['sequence' => 1, 'from' => 'draft', 'to' => 'submitted', 'role_id' => 3, 'label' => 'Submitted'],
        'checker-approval' => ['sequence' => 2, 'from' => 'submitted', 'to' => 'immediatehead_approved', 'role_id' => 2, 'label' => 'Immediate Head Approval'],
        'process-approval' => ['sequence' => 3, 'from' => 'immediatehead_approved','to' => 'process_approved', 'role_id' => 5, 'label' => 'Process Approval'],
        'evaluation-approval' => ['sequence' => 4, 'from' => 'process_approved', 'to' => 'finance_reviewed', 'role_id' => 8, 'label' => 'Evaluation Approval'],
        'grant-approval' => ['sequence' => 5, 'from' => 'finance_reviewed', 'to' => 'completed', 'role_id' => 6, 'label' => 'Grant Approval Request'],
    ];

    private const PIPELINE_REIMB_LIQUID = [
        'submit' => ['sequence' => 1, 'from' => 'draft', 'to' => 'submitted', 'role_id' => 3, 'label' => 'Submitted'],
        'checker-approval' => ['sequence' => 2, 'from' => 'submitted', 'to' => 'immediatehead_approved','role_id' => 2, 'label' => 'Immediate Head Approval'],
        'process-approval' => ['sequence' => 3, 'from' => 'immediatehead_approved','to' => 'process_approved', 'role_id' => 9, 'label' => 'Process Approval'],
        'evaluation-approval' => ['sequence' => 4, 'from' => 'process_approved', 'to' => 'finance_reviewed', 'role_id' => 8, 'label' => 'Evaluation Approval'],
        'grant-approval' => ['sequence' => 5, 'from' => 'finance_reviewed', 'to' => 'completed', 'role_id' => 6, 'label' => 'Grant Approval Request'],
    ];

    private function getPipeline(string $formType): array {
        if (in_array($formType, self::REIMB_LIQUID_TYPES, true)) return self::PIPELINE_REIMB_LIQUID;
        if (in_array($formType, self::FORM_CATEGORIES['finance'], true)) return self::PIPELINE_FINANCE;
        return self::PIPELINE_ADMIN;
    }

    public function index(string $slug): void {
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
        // FIX 2: Added role 9 so HR Verifier sees assigned forms
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

        $forms = $stmt->fetchAll();
        $formType = $type;
        $pageTitle = \App\Helpers\FormLabels::get($type);
        $this->render('forms/list', compact('forms', 'formType', 'slug', 'pageTitle'));
    }

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
        $pageTitle = \App\Helpers\FormLabels::get($type);
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

        $pipeline = $this->getPipeline($form['form_type']);
        $statusToAction = [];
        foreach ($pipeline as $action => $step) {
            if ($step['from'] !== '*') $statusToAction[$step['from']] = $action;
        }
        $nextAction = $statusToAction[$form['status']] ?? null;

        $canAct = $this->canActOnForm($form, $approvalSteps);
        $data = json_decode($form['data'], true) ?? [];
        $formLabel = \App\Helpers\FormLabels::all();
        $typeLabel = \App\Helpers\FormLabels::get($form['form_type']);
        $pageTitle = $typeLabel . ' #' . $id;
        $breadcrumbs = [
            ['label' => $typeLabel, 'url' => url('forms/' . (array_search($form['form_type'], $this->typeMap) ?: 'list'))],
            ['label' => '#' . $id],
        ];

        $this->render('forms/show', compact(
            'form', 'approvalSteps', 'canAct', 'data',
            'pageTitle', 'nextAction', 'breadcrumbs'
        ));
    }

    public function search(): void {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { echo json_encode([]); exit; }
        $like = '%' . $q . '%';
        $ars = db()->prepare(
            "SELECT f.id, f.form_type, f.status, f.created_at, e.full_name AS submitted_by
            FROM forms f JOIN employees e ON e.id = f.submitted_by
            WHERE f.form_type LIKE ? OR e.full_name LIKE ? OR CAST(f.id AS CHAR) LIKE ?
            ORDER BY f.created_at DESC LIMIT 8"
        );
        $ars->execute([$like, $like, $like]);
        echo json_encode($ars->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    public function approve(int $id, string $action): void {
        $this->processApproval($id, $action);
    }

    public function reject(int $id): void {
        \App\Helpers\Csrf::verify();

        $form = $this->findForm($id);
        $remarks = trim($_POST['remarks'] ?? '');
        $userId = (int) $_SESSION['user_id'];
        $roleId = (int) $_SESSION['role_id'];

        $allowedRoles = [1, 2, 4, 5, 6, 7, 8, 9];
        if (!in_array($roleId, $allowedRoles, true)) {
            $_SESSION['error'] = 'You are not authorised to reject this form.';
            header("Location: " . url("forms/view/{$id}"));
            exit;
        }

        $isAdminApproverStandIn = $roleId === 7;
        $isMasterApproverStandIn = $roleId === 4;
        $isFinalApproverStandIn = $roleId === 6;
        $isHrVerifierStandIn = $roleId === 9;
        $isFinanceHeadStandIn = $roleId === 8;

        if ($roleId !== 1) {
            $activeSeqStmt = db()->prepare(
                'SELECT MIN(sequence) FROM approvals WHERE form_id = ? AND status = \'pending\''
            );
            $activeSeqStmt->execute([$id]);
            $activeSequence = $activeSeqStmt->fetchColumn();

            if ($activeSequence === false) {
                $_SESSION['error'] = 'No pending approval step found for this form.';
                header("Location: " . url("forms/view/{$id}"));
                exit;
            }

            if ($isAdminApproverStandIn || $isMasterApproverStandIn || $isFinalApproverStandIn || $isHrVerifierStandIn || $isFinanceHeadStandIn) {
                $pipeline = $this->getPipeline($form['form_type']);
                $activeStepRole = null;
                foreach ($pipeline as $pStep) {
                    if ((int)$pStep['sequence'] === (int)$activeSequence) {
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
                if ($activeStepRole !== 6) {
                    $isFinalApproverStandIn = false;
                }
                if ($activeStepRole !== 9) {
                    $isHrVerifierStandIn = false;
                }
                if ($activeStepRole !== 8) {
                    $isFinanceHeadStandIn = false;
                }
            }

            if ($isAdminApproverStandIn || $isMasterApproverStandIn || $isFinalApproverStandIn || $isHrVerifierStandIn || $isFinanceHeadStandIn) {
                $myStep = db()->prepare(
                    'SELECT id FROM approvals WHERE form_id = ? AND sequence = ? AND status = \'pending\' LIMIT 1'
                );
                $myStep->execute([$id, $activeSequence]);
            } else {
                $myStep = db()->prepare(
                    'SELECT id FROM approvals WHERE form_id = ? AND sequence = ? AND approver_id = ? AND status = \'pending\' LIMIT 1'
                );
                $myStep->execute([$id, $activeSequence, $userId]);
            }
            $step = $myStep->fetch(PDO::FETCH_ASSOC);

            if (!$step) {
                $_SESSION['error'] = 'You are not the assigned approver for the current stage.';
                header("Location: " . url("forms/view/{$id}"));
                exit;
            }
        }

        if (in_array($form['status'], ['completed', 'rejected'], true)) {
            $_SESSION['error'] = 'This form is already finalised.';
            header("Location: " . url("forms/view/{$id}"));
            exit;
        }

        if ($remarks === '') {
            $_SESSION['error'] = 'A rejection reason is required.';
            header("Location: " . url("forms/view/{$id}"));
            exit;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            if ($roleId !== 1) {
                // Record who actually rejected it on their own row, so a
                // stand-in (AdminApprover/MasterApprover/FinalApprover) keeps
                // view access afterwards — findForm() checks approver_id = them
                // regardless of status. Other pending co-signer rows on the
                // same form (e.g. HR + Accounting co-sign) are still closed
                // out below without reassigning their attribution.
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
            $this->sendRejectionNotification($id, $remarks);

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Rejection failed. Please try again.';
        }

        header("Location: " . url("forms/view/{$id}"));
        exit;
    }

    public function mySubmissions(): void {
        $userId = $_SESSION['user_id'];
        $stmt = db()->prepare(
            "SELECT f.id, f.form_type, f.status, f.created_at, e.full_name,
                    (SELECT MIN(sequence) FROM approvals WHERE form_id = f.id AND status = 'pending') AS current_step
            FROM forms f JOIN employees e ON e.id = f.submitted_by
            WHERE f.submitted_by = ?
            ORDER BY f.created_at DESC LIMIT 50"
        );
        $stmt->execute([$userId]);
        $forms = $stmt->fetchAll();
        $formLabel = \App\Helpers\FormLabels::all();
        $pageTitle = 'My Submissions';
        $breadcrumbs = [['label' => 'My Submissions']];

        define('BASE_LOADED', true);
        ob_start();
        require __DIR__ . '/../../views/forms/my_submissions.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/base.php';
    }

    public function allRequests(): void {
        if ((int)($_SESSION['role_id'] ?? 0) !== 1) {
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
        $forms = $stmt->fetchAll();
        $formLabel = \App\Helpers\FormLabels::all();
        $pageTitle = 'All Requests';
        $breadcrumbs = [['label' => 'All Requests']];

        define('BASE_LOADED', true);
        ob_start();
        require __DIR__ . '/../../views/forms/all_requests.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/base.php';
    }

    /**
     * GET /requests/export?format=csv|xlsx|docx|pdf — SysAdmin-only download
     * of every completed form request, in the chosen format. Each row/section
     * includes the full submitted form data (not just summary columns) plus
     * the approval trail, not just metadata.
     *
     * xlsx and docx are the well-established "HTML dressed as Office file"
     * trick — an HTML table/document served with the Excel/Word MIME type
     * and file extension. Both Excel and Word open these natively; no
     * PhpSpreadsheet/PhpWord dependency needed for what's essentially a
     * formatted data dump.
     *
     * pdf is different: there is no dependency-free way to produce a real
     * PDF binary from PHP. This renders the same content as a print-ready
     * HTML page and auto-opens the browser's print dialog so the admin can
     * "Save as PDF" — it is NOT a server-generated .pdf file. If a true
     * server-side PDF is needed later, add a library such as dompdf/dompdf
     * via Composer and swap this branch for it.
     */
    public function exportCompletedRequests(): void {
        if ((int)($_SESSION['role_id'] ?? 0) !== 1) {
            $_SESSION['error'] = 'Access denied.';
            header('Location: ' . url('dashboard')); exit;
        }

        $format = strtolower($_GET['format'] ?? 'csv');
        if (!in_array($format, ['csv', 'xlsx', 'docx', 'pdf'], true)) {
            $format = 'csv';
        }

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

        if (empty($forms)) {
            $_SESSION['error'] = 'No completed requests to export yet.';
            header('Location: ' . url('requests'));
            exit;
        }

        // Approval trail for every exported form, fetched in one query and
        // grouped by form_id, so each export includes who acted at every
        // stage — not just the submission itself.
        $formIds = array_column($forms, 'id');
        $placeholders = implode(',', array_fill(0, count($formIds), '?'));
        $apprStmt = db()->prepare(
            "SELECT a.form_id, a.sequence, a.status, a.remarks, a.approved_at,
                    ea.full_name AS approver_name
             FROM approvals a
             JOIN employees ea ON ea.id = a.approver_id
             WHERE a.form_id IN ({$placeholders})
             ORDER BY a.form_id, a.sequence"
        );
        $apprStmt->execute($formIds);
        $approvalsByForm = [];
        foreach ($apprStmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $approvalsByForm[(int) $a['form_id']][] = $a;
        }

        $formLabel = \App\Helpers\FormLabels::all();

        // Bulk export, not tied to one record — entity_id 0 is the
        // convention this app uses for actions with no single target.
        $this->audit('requests_exported', 'form', 0, null, [
            'exported_by' => $_SESSION['user_id'],
            'format' => $format,
            'count' => count($forms),
        ]);

        if ($format === 'xlsx') {
            $this->streamExcelExport($forms, $approvalsByForm, $formLabel);
        } elseif ($format === 'docx') {
            $this->streamWordExport($forms, $approvalsByForm, $formLabel);
        } elseif ($format === 'pdf') {
            $this->streamPrintableExport($forms, $approvalsByForm, $formLabel);
        } else {
            $this->streamCsvExport($forms, $approvalsByForm, $formLabel);
        }
        exit;
    }

    // ================================================================
    // PRIVATE
    // ================================================================

    private function processApproval(int $id, string $action): void {
        \App\Helpers\Csrf::verify();

        $form = $this->findForm($id);
        $remarks = trim($_POST['remarks'] ?? '');
        $userId = (int) $_SESSION['user_id'];
        $roleId = (int) $_SESSION['role_id'];
        $isAdmin = $roleId === 1;
        $isAdminApproverStandIn = $roleId === 7;

        $pipeline = $this->getPipeline($form['form_type']);
        if (!isset($pipeline[$action])) {
            $_SESSION['error'] = "Unknown approval action: '{$action}'.";
            header("Location: " . url("forms/view/{$id}"));
            exit;
        }

        $step = $pipeline[$action];
        $isAdminApproverStandIn = $isAdminApproverStandIn
            && $this->adminApproverStandsInFor($form['form_type'], (int) $step['role_id']);
        $isMasterApproverStandIn = $roleId === 4
            && $this->masterApproverStandsInFor($form['form_type'], (int) $step['role_id']);
        // FinalApprover (role 6) shared queue: any Final Approver may act on a
        // pending Grant Approval row, not just the one auto-assigned to them.
        $isFinalApproverStandIn = $roleId === 6 && (int) $step['role_id'] === 6;
        // HRVerifier (role 9) shared queue: any HR Verifier may act on a
        // pending Process Approval (HR Verification) row on Reimbursement /
        // Liquidation forms, not just the one auto-assigned to them.
        $isHrVerifierStandIn = $roleId === 9 && (int) $step['role_id'] === 9;
        // FinanceHead (role 8) shared queue: any Finance Head may act on a
        // pending Evaluation Approval row, not just the one auto-assigned to them.
        $isFinanceHeadStandIn = $roleId === 8 && (int) $step['role_id'] === 8;

        if ($step['from'] !== '*' && $form['status'] !== $step['from']) {
            $_SESSION['error'] = sprintf(
                "Cannot perform '%s': form is currently '%s', expected '%s'.",
                $action, $form['status'], $step['from']
            );
            header("Location: " . url("forms/view/{$id}"));
            exit;
        }

        if (in_array($form['status'], ['completed', 'rejected'], true)) {
            $_SESSION['error'] = 'This form is already finalized.';
            header("Location: " . url("forms/view/{$id}"));
            exit;
        }

        $approvalRow = db()->prepare(
            'SELECT * FROM approvals WHERE form_id = ? AND sequence = ? AND approver_id = ? AND status = \'pending\' LIMIT 1'
        );
        $approvalRow->execute([$id, $step['sequence'], $userId]);
        $approval = $approvalRow->fetch();

        if (!$approval && ($isAdmin || $isAdminApproverStandIn || $isMasterApproverStandIn || $isFinalApproverStandIn || $isHrVerifierStandIn || $isFinanceHeadStandIn)) {
            $fallback = db()->prepare(
                'SELECT * FROM approvals WHERE form_id = ? AND sequence = ? AND status = \'pending\' LIMIT 1'
            );
            $fallback->execute([$id, $step['sequence']]);
            $approval = $fallback->fetch();
        }

        // FIX 3a: Removed stale `|| ($action === 'process-approval' && $roleId === 9)` hack
        if (!$isAdmin && !$isAdminApproverStandIn && !$isMasterApproverStandIn && !$isFinalApproverStandIn && !$isHrVerifierStandIn && !$isFinanceHeadStandIn && $action !== 'submit') {
            if (!$approval || (int)$approval['approver_id'] !== $userId) {
                $_SESSION['error'] = 'No pending approval step found for you at this stage.';
                header("Location: " . url("forms/view/{$id}"));
                exit;
            }
        }

        if (($isAdminApproverStandIn || $isMasterApproverStandIn || $isFinalApproverStandIn || $isHrVerifierStandIn || $isFinanceHeadStandIn) && !$isAdmin && !$approval) {
            $_SESSION['error'] = 'No pending approval step found at this stage.';
            header("Location: " . url("forms/view/{$id}"));
            exit;
        }

        // FIX 3b: roleSatisfiesStage now handles role 9 → role 5, so no extra hack needed
        $actorAllowed = $isAdmin || $isAdminApproverStandIn || $isMasterApproverStandIn || $isFinalApproverStandIn || $isHrVerifierStandIn || $isFinanceHeadStandIn || $action === 'submit'
            || $this->roleSatisfiesStage($roleId, $step['role_id'], $form['form_type']);
        if (!$actorAllowed) {
            $_SESSION['error'] = 'You are not authorized to perform this action.';
            header("Location: " . url("forms/view/{$id}"));
            exit;
        }

        if ($action === 'submit' && !$isAdmin && (int)$form['submitted_by'] !== $userId) {
            $_SESSION['error'] = 'Only the form owner can submit this form.';
            header("Location: " . url("forms/view/{$id}"));
            exit;
        }

        $uploadedFilePath = null;
        if (!empty($_FILES['approval_file']['tmp_name'])) {
            $file = $_FILES['approval_file'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $maxBytes = 5 * 1024 * 1024;
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, $allowed, true)) {
                $_SESSION['error'] = 'Only images and PDF files are allowed.';
                header("Location: " . url("forms/view/{$id}"));
                exit;
            }
            if ($file['size'] > $maxBytes) {
                $_SESSION['error'] = 'File must be under 5 MB.';
                header("Location: " . url("forms/view/{$id}"));
                exit;
            }
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $destDir = __DIR__ . '/../../public/uploads/approvals/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            $fileName = sprintf('%d_%d_%s.%s', $id, time(), bin2hex(random_bytes(4)), $ext);
            if (!move_uploaded_file($file['tmp_name'], $destDir . $fileName)) {
                $_SESSION['error'] = 'File upload failed. Please try again.';
                header("Location: " . url("forms/view/{$id}"));
                exit;
            }
            $uploadedFilePath = 'uploads/approvals/' . $fileName;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            if ($approval) {
                // Record the person who actually clicked Approve. For a normal
                // approver this is a no-op (approver_id already equals $userId).
                // For a stand-in (AdminApprover/MasterApprover/FinalApprover
                // shared queue) this reassigns the row to them — this is both
                // the correct audit trail and what keeps findForm() letting
                // them view the form afterwards (it checks approver_id = them,
                // regardless of status). Admin (role 1) overrides are left
                // attributed to the originally assigned approver.
                $actingApproverId = $isAdmin ? (int) $approval['approver_id'] : $userId;

                $updated = $pdo->prepare(
                    "UPDATE approvals
                    SET status = 'approved', approver_id = ?, remarks = ?, file_path = ?, approved_at = NOW()
                    WHERE id = ? AND status = 'pending'"
                );
                $updated->execute([
                    $actingApproverId,
                    $remarks ?: ($isAdmin ? '(Admin override)' : ($isAdminApproverStandIn ? '(AdminApprover stand-in)' : ($isFinalApproverStandIn ? '(FinalApprover — first to act)' : ($isHrVerifierStandIn ? '(HRVerifier — first to act)' : ($isFinanceHeadStandIn ? '(FinanceHead — first to act)' : $step['label']))))),
                    $uploadedFilePath,
                    $approval['id'],
                ]);

                if ($updated->rowCount() === 0) {
                    // Someone else (another stand-in approver) already actioned
                    // this exact row between our SELECT and this UPDATE.
                    $pdo->rollBack();
                    $_SESSION['error'] = 'This request was already actioned by another approver.';
                    header("Location: " . url("forms/view/{$id}"));
                    exit;
                }
            }

            $isFinanceProcessStage = $action === 'process-approval'
                && in_array($form['form_type'], self::REIMB_LIQUID_TYPES, true)
                && $roleId === 9;

            if ($isFinanceProcessStage) {
                $accAlreadySeeded = $pdo->prepare(
                    "SELECT COUNT(*) FROM approvals a
                    JOIN employees e ON e.id = a.approver_id
                    WHERE a.form_id = ? AND a.sequence = 3 AND e.role_id = 5"
                );
                $accAlreadySeeded->execute([$id]);
                if ((int) $accAlreadySeeded->fetchColumn() === 0) {
                    $formData = json_decode($form['data'], true) ?? [];
                    $accIds = $this->resolveApproversByRole($pdo, 5, $formData, (int) $form['submitted_by'], $form['form_type']);
                    if (empty($accIds)) {
                        throw new \RuntimeException('No active Accounting approver found for co-sign.');
                    }
                    $accInsert = $pdo->prepare(
                        "INSERT INTO approvals (form_id, approver_id, sequence, status) VALUES (?, ?, 3, 'pending')"
                    );
                    foreach ($accIds as $accId) {
                        $accInsert->execute([$id, $accId]);
                    }
                }
            }

            $remaining = $pdo->prepare(
                "SELECT COUNT(*) FROM approvals WHERE form_id = ? AND sequence = ? AND status = 'pending'"
            );
            $remaining->execute([$id, $step['sequence']]);
            $stillPending = (int) $remaining->fetchColumn();
            $advanced = $isAdmin || $stillPending === 0;

            if ($advanced) {
                $pdo->prepare("UPDATE forms SET status = ?, updated_at = NOW() WHERE id = ?")
                    ->execute([$step['to'], $id]);
            }

            $this->audit('form_approved', 'form', $id,
                ['status' => $form['status']],
                ['status' => $advanced ? $step['to'] : $form['status'], 'action' => $action]
            );

            $pdo->commit();

            $_SESSION['success'] = $advanced
                ? "Form {$step['label']} approved successfully."
                : 'Your approval has been recorded. Waiting for co-approver.';

            if ($advanced) {
                $this->sendPipelineNotifications($id, $action, $step, $remarks);
            }

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Action failed. Please try again.';
        }

        header("Location: " . url("forms/view/{$id}"));
        exit;
    }

    // FIX 4: Removed incorrectly placed HR seed block that referenced $action/$form/$id
    private function store(string $type, string $slug): void {
        \App\Helpers\Csrf::verify();

        $uploadedPaths = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
            $maxBytes = 5 * 1024 * 1024;
            $destDir = __DIR__ . '/../../public/uploads/forms/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            $finfo = new \finfo(FILEINFO_MIME_TYPE);

            foreach ($_FILES['attachments']['tmp_name'] as $i => $tmp) {
                if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $mime = $finfo->file($tmp);
                if (!in_array($mime, $allowed, true) || $_FILES['attachments']['size'][$i] > $maxBytes) {
                    $_SESSION['error'] = 'Invalid file or file exceeds 5 MB.';
                    header("Location: " . url("forms/{$slug}/create"));
                    exit;
                }
                $ext = pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION);
                $fileName = sprintf('%s_%s.%s', time(), bin2hex(random_bytes(4)), $ext);
                move_uploaded_file($tmp, $destDir . $fileName);
                $uploadedPaths[] = 'uploads/forms/' . $fileName;
            }
        }

        $isSavingDraft = isset($_POST['save_draft']);
        $required = $this->fields[$type];
        $data = [];

        if (!$isSavingDraft) {
            foreach ($required as $field) {
                $val = $_POST[$field] ?? '';
                if (is_string($val)) $val = trim($val);
                if ($val === '' || (is_array($val) && empty(array_filter($val)))) {
                    $_SESSION['error'] = "Field '{$field}' is required.";
                    header("Location: " . url("forms/{$slug}/create"));
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

        if (!empty($uploadedPaths)) {
            $data['attachments'] = $uploadedPaths;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $initialStatus = $isSavingDraft ? 'draft' : 'submitted';

            $stmt = $pdo->prepare(
                "INSERT INTO forms (form_type, status, submitted_by, data) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$type, $initialStatus, $_SESSION['user_id'], json_encode($data)]);
            $formId = (int) $pdo->lastInsertId();

            $this->seedApprovalRows($pdo, $formId, $type, $data, (int)$_SESSION['user_id']);

            if (!$isSavingDraft) {
                $pdo->prepare(
                    "UPDATE approvals SET status = 'approved', approved_at = NOW(), remarks = 'Submitted'
                    WHERE form_id = ? AND sequence = 1"
                )->execute([$formId]);
            }

            $this->audit('form_created', 'form', $formId, null, ['type' => $type, 'status' => $initialStatus]);

            $pdo->commit();

            $_SESSION['success'] = $isSavingDraft
                ? 'Draft saved. You can continue editing or submit when ready.'
                : 'Form submitted successfully and is now pending approval.';

            header('Location: ' . url('forms/view/' . $formId));
            exit;

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Submission failed: ' . $e->getMessage();
            header("Location: " . url("forms/{$slug}/create"));
            exit;
        }
    }

    private function seedApprovalRows(\PDO $pdo, int $formId, string $type, array $data, int $submitterId): void {
        $pipeline = $this->getPipeline($type);
        $isHrFirstCoSign = in_array($type, self::REIMB_LIQUID_TYPES, true);

        $insert = $pdo->prepare(
            "INSERT INTO approvals (form_id, approver_id, sequence, status) VALUES (?, ?, ?, 'pending')"
        );
        $insert->execute([$formId, $submitterId, 1]);

        $stagesNeedingApprover = array_filter($pipeline, fn($step) => $step['sequence'] >= 2);
        foreach ($stagesNeedingApprover as $step) {
            if ($isHrFirstCoSign && $step['sequence'] === 3) {
                $hrIds = $this->resolveApproversByRole($pdo, 9, $data, $submitterId, $type);
                if (empty($hrIds)) {
                    throw new \RuntimeException("No active HR Verifier found for Process Approval stage.");
                }
                foreach ($hrIds as $approverId) {
                    $insert->execute([$formId, $approverId, 3]);
                }
                continue;
            }

            $approvers = $this->resolveApproversByRole($pdo, $step['role_id'], $data, $submitterId, $type);
            if (empty($approvers)) {
                throw new \RuntimeException("No active approver found for stage '{$step['label']}'.");
            }
            foreach ($approvers as $approverId) {
                $insert->execute([$formId, $approverId, $step['sequence']]);
            }
        }
    }

    private function resolveApproversByRole(\PDO $pdo, int $roleId, array $data, int $submitterId, string $formType = ''): array {
        if ($roleId === 2) {
            $stmt = $pdo->prepare(
                'SELECT supervisor_id, supervisor_id_2 FROM employees WHERE id = ? AND is_active = 1'
            );
            $stmt->execute([$submitterId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return [];

            $isDualSignoffForm = $formType === 'vehicle_request';
            $supervisorIds = array_unique(array_filter([
                $row['supervisor_id'] ? (int) $row['supervisor_id'] : null,
                ($isDualSignoffForm && $row['supervisor_id_2']) ? (int) $row['supervisor_id_2'] : null,
            ]));
            if (empty($supervisorIds)) return [];

            $placeholders = implode(',', array_fill(0, count($supervisorIds), '?'));
            $check = $pdo->prepare("SELECT id FROM employees WHERE id IN ($placeholders) AND is_active = 1");
            $check->execute(array_values($supervisorIds));
            return array_map('intval', $check->fetchAll(\PDO::FETCH_COLUMN));
        }

        if ($roleId === 4) {
            $stmt = $pdo->prepare(
                'SELECT master_approver_id FROM employees WHERE id = ? AND is_active = 1'
            );
            $stmt->execute([$submitterId]);
            $assignedId = $stmt->fetchColumn();

            if ($assignedId) {
                $check = $pdo->prepare(
                    'SELECT id FROM employees WHERE id = ? AND role_id IN (4, 7) AND is_active = 1'
                );
                $check->execute([(int) $assignedId]);
                if ($check->fetchColumn()) return [(int) $assignedId];
            }
        }

        if ($roleId === 6) {
            $row = $pdo->query(
                "SELECT e.id FROM employees e
                LEFT JOIN approvals a ON a.approver_id = e.id AND a.status = 'pending'
                WHERE e.role_id = 6 AND e.is_active = 1
                GROUP BY e.id ORDER BY COUNT(a.id) ASC, e.id ASC LIMIT 1"
            )->fetch(\PDO::FETCH_ASSOC);
            if ($row) return [(int) $row['id']];

            $fallback = $pdo->query(
                "SELECT e.id FROM employees e
                LEFT JOIN approvals a ON a.approver_id = e.id AND a.status = 'pending'
                WHERE e.role_id = 7 AND e.is_active = 1
                GROUP BY e.id ORDER BY COUNT(a.id) ASC, e.id ASC LIMIT 1"
            )->fetch(\PDO::FETCH_ASSOC);
            return $fallback ? [(int) $fallback['id']] : [];
        }

        if ($roleId === self::FINANCE_HEAD_ROLE) {
            $stmt = $pdo->prepare(
                'SELECT finance_head_id FROM employees WHERE id = ? AND is_active = 1'
            );
            $stmt->execute([$submitterId]);
            $assignedId = $stmt->fetchColumn();

            if ($assignedId) {
                $check = $pdo->prepare(
                    'SELECT id FROM employees WHERE id = ? AND role_id = ? AND is_active = 1'
                );
                $check->execute([(int) $assignedId, self::FINANCE_HEAD_ROLE]);
                if ($check->fetchColumn()) return [(int) $assignedId];
            }
        }

        if ($roleId === self::HR_VERIFIER_ROLE) {
            $stmt = $pdo->prepare(
                'SELECT hr_verifier_id FROM employees WHERE id = ? AND is_active = 1'
            );
            $stmt->execute([$submitterId]);
            $assignedId = $stmt->fetchColumn();

            if ($assignedId) {
                $check = $pdo->prepare(
                    'SELECT id FROM employees WHERE id = ? AND role_id = ? AND is_active = 1'
                );
                $check->execute([(int) $assignedId, self::HR_VERIFIER_ROLE]);
                if ($check->fetchColumn()) return [(int) $assignedId];
            }

            $row = $pdo->query(
                "SELECT e.id FROM employees e
                LEFT JOIN approvals a ON a.approver_id = e.id AND a.status = 'pending'
                WHERE e.role_id = 9 AND e.is_active = 1
                GROUP BY e.id ORDER BY COUNT(a.id) ASC, e.id ASC LIMIT 1"
            )->fetch(\PDO::FETCH_ASSOC);
            return $row ? [(int) $row['id']] : [];
        }

        // Generic workload-balanced fallback (roles 3, 5, and any unhandled role)
        $dept = $data['department'] ?? null;
        $sql = "SELECT e.id FROM employees e
                LEFT JOIN approvals a ON a.approver_id = e.id AND a.status = 'pending'
                WHERE e.role_id = ? AND e.is_active = 1";
        $params = [$roleId];

        if ($dept && !in_array($roleId, [1, 4, 5, 6, 7, 8, 9], true)) {
            $sql .= ' AND e.department = ?';
            $params[] = $dept;
        }
        $sql .= ' GROUP BY e.id ORDER BY COUNT(a.id) ASC, e.id ASC LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) return [(int) $row['id']];

        if ($roleId === 4) {
            $fallback = $pdo->prepare(
                "SELECT e.id FROM employees e
                LEFT JOIN approvals a ON a.approver_id = e.id AND a.status = 'pending'
                WHERE e.role_id = 7 AND e.is_active = 1
                GROUP BY e.id ORDER BY COUNT(a.id) ASC, e.id ASC LIMIT 1"
            );
            $fallback->execute();
            $fallbackRow = $fallback->fetch(\PDO::FETCH_ASSOC);
            if ($fallbackRow) return [(int) $fallbackRow['id']];
        }

        return [];
    }

    private function canActOnForm(array $form, array $steps): bool {
        if (in_array($form['status'], ['completed', 'rejected'], true)) return false;
        if ($_SESSION['role_id'] == 1) return true;

        $userId = (int) $_SESSION['user_id'];
        $roleId = (int) $_SESSION['role_id'];

        if ($form['status'] === 'draft' && (int)$form['submitted_by'] === $userId) return true;

        foreach ($steps as $step) {
            if ((int)$step['approver_id'] !== $userId || $step['status'] !== 'pending') continue;
            $mySeq   = (int)$step['sequence'];
            $blocked = false;
            foreach ($steps as $other) {
                if ((int)$other['approver_id'] !== $userId
                    && $other['status'] === 'pending'
                    && (int)$other['sequence'] < $mySeq
                ) { $blocked = true; break; }
            }
            if (!$blocked) return true;
        }

        // AdminApprover (role 7) stand-in
        if ($roleId === 7) {
            foreach ($steps as $step) {
                if ($step['status'] !== 'pending') continue;
                $mySeq = (int)$step['sequence'];
                $blocked = false;
                foreach ($steps as $other) {
                    if ($other['status'] === 'pending' && (int)$other['sequence'] < $mySeq) {
                        $blocked = true; break;
                    }
                }
                if (!$blocked) {
                    $pipeline = $this->getPipeline($form['form_type']);
                    foreach ($pipeline as $pStep) {
                        if ((int)$pStep['sequence'] === $mySeq
                            && $this->adminApproverStandsInFor($form['form_type'], (int)$pStep['role_id'])
                        ) return true;
                    }
                }
            }
        }

        // MasterApprover (role 4) stand-in for Checker (Immediate Head) stage
        // on admin-category forms (Overtime, Leave, Vehicle Request)
        if ($roleId === 4) {
            foreach ($steps as $step) {
                if ($step['status'] !== 'pending') continue;
                $mySeq   = (int)$step['sequence'];
                $blocked = false;
                foreach ($steps as $other) {
                    if ($other['status'] === 'pending' && (int)$other['sequence'] < $mySeq) {
                        $blocked = true; break;
                    }
                }
                if (!$blocked) {
                    $pipeline = $this->getPipeline($form['form_type']);
                    foreach ($pipeline as $pStep) {
                        if ((int)$pStep['sequence'] === $mySeq
                            && $this->masterApproverStandsInFor($form['form_type'], (int)$pStep['role_id'])
                        ) return true;
                    }
                }
            }
        }

        // FinalApprover (role 6) shared queue: any Final Approver may act on any
        // pending Grant Approval row, regardless of who it was auto-assigned to.
        if ($roleId === 6) {
            foreach ($steps as $step) {
                if ($step['status'] !== 'pending') continue;
                if ((int)$step['approver_role_id'] !== 6) continue;
                $mySeq = (int)$step['sequence'];
                $blocked = false;
                foreach ($steps as $other) {
                    if ($other['status'] === 'pending' && (int)$other['sequence'] < $mySeq) {
                        $blocked = true; break;
                    }
                }
                if (!$blocked) return true;
            }
        }

        // HRVerifier (role 9) shared queue: any HR Verifier may act on any
        // pending Process Approval (HR Verification) row, regardless of who
        // it was auto-assigned to.
        if ($roleId === 9) {
            foreach ($steps as $step) {
                if ($step['status'] !== 'pending') continue;
                if ((int)$step['approver_role_id'] !== 9) continue;
                $mySeq = (int)$step['sequence'];
                $blocked = false;
                foreach ($steps as $other) {
                    if ($other['status'] === 'pending' && (int)$other['sequence'] < $mySeq) {
                        $blocked = true; break;
                    }
                }
                if (!$blocked) return true;
            }
        }

        // FinanceHead (role 8) shared queue: any Finance Head may act on any
        // pending Evaluation Approval row, regardless of who it was
        // auto-assigned to.
        if ($roleId === 8) {
            foreach ($steps as $step) {
                if ($step['status'] !== 'pending') continue;
                if ((int)$step['approver_role_id'] !== 8) continue;
                $mySeq = (int)$step['sequence'];
                $blocked = false;
                foreach ($steps as $other) {
                    if ($other['status'] === 'pending' && (int)$other['sequence'] < $mySeq) {
                        $blocked = true; break;
                    }
                }
                if (!$blocked) return true;
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

        if (!$form) return $this->renderError(404, 'Not Found', 'The form you are looking for does not exist.');

        $roleId = (int) $_SESSION['role_id'];
        $userId = (int) $_SESSION['user_id'];

        if ($roleId === 1) return $form;
        if ((int)$form['submitted_by'] === $userId) return $form;

        $assigned = db()->prepare('SELECT id FROM approvals WHERE form_id = ? AND approver_id = ? LIMIT 1');
        $assigned->execute([$id, $userId]);
        if ($assigned->fetch()) return $form;

        // MasterApprover (role 4) stand-in: allow viewing when a Checker-stage
        // approval is pending on the form, regardless of form type.
        if ($roleId === 4) {
            $checkerPending = db()->prepare(
                "SELECT id FROM approvals WHERE form_id = ? AND sequence = 2 AND status = 'pending' LIMIT 1"
            );
            $checkerPending->execute([$id]);
            if ($checkerPending->fetch()) return $form;
        }

        // AdminApprover (role 7) stand-in: unlike other stand-in roles, an
        // AdminApprover is NEVER the literal approver_id on a row — they only
        // ever act through the fallback in processApproval(). So visibility
        // has to be derived from the pipeline definition (which sequences
        // this form type lets them cover — see ADMIN_APPROVER_STANDIN_COVERAGE)
        // rather than from approver_id, and covers any status (pending,
        // approved, or completed) so they can also see what they already acted on.
        if ($roleId === 7) {
            $pipeline = $this->getPipeline($form['form_type']);
            $coveredSequences = [];
            foreach ($pipeline as $pStep) {
                if ($this->adminApproverStandsInFor($form['form_type'], (int) $pStep['role_id'])) {
                    $coveredSequences[] = (int) $pStep['sequence'];
                }
            }
            if (!empty($coveredSequences)) {
                $placeholders = implode(',', array_fill(0, count($coveredSequences), '?'));
                $coveredRow = db()->prepare(
                    "SELECT id FROM approvals WHERE form_id = ? AND sequence IN ({$placeholders}) LIMIT 1"
                );
                $coveredRow->execute(array_merge([$id], $coveredSequences));
                if ($coveredRow->fetch()) return $form;
            }
        }

        // FinalApprover (role 6) shared queue: allow viewing any form that has
        // ever had a Final Approval row (pending, approved, or completed) —
        // not just the ones still pending — so any Final Approver can see
        // requests another Final Approver already acted on.
        if ($roleId === 6) {
            $finalRow = db()->prepare(
                "SELECT a.id FROM approvals a JOIN employees e ON e.id = a.approver_id
                 WHERE a.form_id = ? AND e.role_id = 6 LIMIT 1"
            );
            $finalRow->execute([$id]);
            if ($finalRow->fetch()) return $form;
        }

        // HRVerifier (role 9) shared queue: allow viewing any form that has
        // ever had an HR Verification row (pending, approved, or completed) —
        // not just the ones still pending — so any HR Verifier can see
        // requests another HR Verifier already acted on.
        if ($roleId === 9) {
            $hrRow = db()->prepare(
                "SELECT a.id FROM approvals a JOIN employees e ON e.id = a.approver_id
                 WHERE a.form_id = ? AND e.role_id = 9 LIMIT 1"
            );
            $hrRow->execute([$id]);
            if ($hrRow->fetch()) return $form;
        }

        // FinanceHead (role 8) shared queue: allow viewing any form that has
        // ever had an Evaluation Approval row (pending, approved, or
        // completed) — not just the ones still pending — so any Finance Head
        // can see requests another Finance Head already acted on.
        if ($roleId === 8) {
            $financeRow = db()->prepare(
                "SELECT a.id FROM approvals a JOIN employees e ON e.id = a.approver_id
                 WHERE a.form_id = ? AND e.role_id = 8 LIMIT 1"
            );
            $financeRow->execute([$id]);
            if ($financeRow->fetch()) return $form;
        }

        return $this->renderError(403, 'Access Denied', 'You do not have permission to view this form.');
    }

    private function sendPipelineNotifications(int $formId, string $action, array $step, string $remarks): void {
        try {
            $pdo = db();
            $formRow = $pdo->prepare(
                'SELECT f.form_type, f.status, f.submitted_by,
                        e.full_name AS submitter_name, e.email AS submitter_email
                 FROM forms f JOIN employees e ON e.id = f.submitted_by WHERE f.id = ?'
            );
            $formRow->execute([$formId]);
            $form = $formRow->fetch(\PDO::FETCH_ASSOC);
            if (!$form) return;

            $submittedBy = (int) $form['submitted_by'];
            $formLabel = \App\Helpers\FormLabels::get($form['form_type']);
            $stageName = $step['label'];
            $newStatus = $step['to'];

            $outcome = match($newStatus) {
                'completed' => 'completed',
                'final_approved' => 'final_approved',
                'rejected' => 'rejected',
                default => 'approved_step',
            };

            $submitterMsg = match($outcome) {
                'completed' => "Your {$formLabel} #{$formId} has been fully completed.",
                'final_approved' => "Your {$formLabel} #{$formId} reached final approval.",
                'rejected' => "Your {$formLabel} #{$formId} was rejected at {$stageName}.",
                default => "Your {$formLabel} #{$formId} passed {$stageName}.",
            };
            $submitterType = match($outcome) {
                'completed', 'final_approved' => 'success',
                'rejected' => 'danger',
                default => 'info',
            };

            \App\Controllers\NotificationController::create($submittedBy, $submitterMsg, $submitterType, $formId);
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
                 FROM forms f JOIN employees e ON e.id = f.submitted_by WHERE f.id = ?'
            );
            $row->execute([$formId]);
            $form = $row->fetch(\PDO::FETCH_ASSOC);
            if (!$form) return;

            $stageRow = $pdo->prepare(
                'SELECT sequence FROM approvals WHERE form_id = ? AND status = \'rejected\'
                 ORDER BY sequence DESC LIMIT 1'
            );
            $stageRow->execute([$formId]);
            $stageData = $stageRow->fetch(\PDO::FETCH_ASSOC);
            $stageName = \App\Helpers\FormLabels::stepLabel((int)($stageData['sequence'] ?? 0));
            $typeLabel = \App\Helpers\FormLabels::get($form['form_type']);

            $rejectMsg = "Your {$typeLabel} #{$formId} was rejected at {$stageName}"
                       . ($remarks ? ": \"{$remarks}\"" : '.');
            \App\Controllers\NotificationController::create((int) $form['submitted_by'], $rejectMsg, 'danger', $formId);
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

    /**
     * Turns a form's submitted `data` JSON into a flat list of readable
     * "Label: value" lines, e.g. ['Leave Type: Sick Leave', 'Start Date: ...'].
     * Recurses into nested arrays (like line-item tables) so every field
     * a form type collects shows up, without needing a per-form-type mapping.
     */
    private function flattenFormData($data, string $prefix = ''): array {
        $lines = [];
        if (!is_array($data)) return $lines;

        foreach ($data as $key => $value) {
            $label = is_string($key)
                ? ucwords(str_replace('_', ' ', $key))
                : 'Item ' . ((int) $key + 1);
            $label = $prefix !== '' ? "{$prefix} — {$label}" : $label;

            if (is_array($value)) {
                $lines = array_merge($lines, $this->flattenFormData($value, $label));
            } else {
                $value = is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value;
                $lines[] = "{$label}: {$value}";
            }
        }
        return $lines;
    }

    /** Human-readable "Name — Status (date)" line for one approval step. */
    private function formatApprovalLine(array $a): string {
        $line = $a['approver_name'] . ' — ' . ucfirst($a['status']);
        if ($a['approved_at']) $line .= ' on ' . date('M j, Y g:i A', strtotime($a['approved_at']));
        if (!empty($a['remarks'])) $line .= ' ("' . $a['remarks'] . '")';
        return $line;
    }

    private function streamCsvExport(array $forms, array $approvalsByForm, array $formLabel): void {
        $filename = 'completed-requests-' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM so Excel opens accented/special characters correctly.
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'Request ID', 'Form Type', 'Employee Code', 'Employee Name',
            'Department', 'Company', 'Date Filed', 'Date Completed',
            'Form Details', 'Approval Trail',
        ]);

        foreach ($forms as $row) {
            $data = json_decode($row['data'] ?? '', true) ?: [];
            $details = implode(' | ', $this->flattenFormData($data));
            $trail = [];
            foreach ($approvalsByForm[(int) $row['id']] ?? [] as $a) {
                $trail[] = $this->formatApprovalLine($a);
            }
            fputcsv($out, [
                $row['id'],
                $formLabel[$row['form_type']] ?? $row['form_type'],
                $row['employee_code'],
                $row['full_name'],
                $row['department'] ?? '',
                $row['company'] ?? '',
                date('Y-m-d H:i', strtotime($row['created_at'])),
                date('Y-m-d H:i', strtotime($row['updated_at'])),
                $details,
                implode(' -> ', $trail),
            ]);
        }
        fclose($out);
    }

    /**
     * Excel does not require a real .xlsx (ZIP+XML) binary — it natively
     * opens an HTML table served with the Excel MIME type / .xls extension.
     * This avoids pulling in PhpSpreadsheet for what's a straightforward
     * tabular export.
     */
    private function streamExcelExport(array $forms, array $approvalsByForm, array $formLabel): void {
        $filename = 'completed-requests-' . date('Y-m-d_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8">';
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        echo '<x:Name>Completed Requests</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
        echo '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        echo '</head><body>';
        echo '<table border="1" cellspacing="0" cellpadding="4">';
        echo '<tr>';
        foreach (['Request ID', 'Form Type', 'Employee Code', 'Employee Name', 'Department',
                  'Company', 'Date Filed', 'Date Completed', 'Form Details', 'Approval Trail'] as $h) {
            echo '<th>' . htmlspecialchars($h) . '</th>';
        }
        echo '</tr>';

        foreach ($forms as $row) {
            $data = json_decode($row['data'] ?? '', true) ?: [];
            $details = implode("\n", $this->flattenFormData($data));
            $trail = [];
            foreach ($approvalsByForm[(int) $row['id']] ?? [] as $a) {
                $trail[] = $this->formatApprovalLine($a);
            }
            echo '<tr>';
            echo '<td>' . (int) $row['id'] . '</td>';
            echo '<td>' . htmlspecialchars($formLabel[$row['form_type']] ?? $row['form_type']) . '</td>';
            echo '<td>' . htmlspecialchars($row['employee_code']) . '</td>';
            echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['department'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['company'] ?? '') . '</td>';
            echo '<td>' . date('Y-m-d H:i', strtotime($row['created_at'])) . '</td>';
            echo '<td>' . date('Y-m-d H:i', strtotime($row['updated_at'])) . '</td>';
            echo '<td style="mso-number-format:\'\@\';white-space:pre-wrap;">' . nl2br(htmlspecialchars($details)) . '</td>';
            echo '<td>' . htmlspecialchars(implode(' -> ', $trail)) . '</td>';
            echo '</tr>';
        }
        echo '</table></body></html>';
    }

    /**
     * Word does not require a real .docx (ZIP+XML) binary either — it opens
     * an HTML document served with the Word MIME type / .doc extension.
     * Unlike the CSV/Excel table, this lays out one full section per form
     * (all submitted fields + approval trail), since that reads far better
     * as a document than as spreadsheet rows.
     */
    private function streamWordExport(array $forms, array $approvalsByForm, array $formLabel): void {
        $filename = 'completed-requests-' . date('Y-m-d_His') . '.doc';
        header('Content-Type: application/msword; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo $this->buildExportHtml($forms, $approvalsByForm, $formLabel);
    }

    /**
     * Word/Excel can be tricked with an HTML file, but a real PDF can't —
     * PHP has no built-in way to rasterize/paginate to PDF without a library
     * (e.g. dompdf/dompdf via Composer). Rather than mislabel an HTML file
     * as .pdf, this opens a print-ready page and auto-triggers the browser's
     * print dialog so the admin can choose "Save as PDF" there.
     */
    private function streamPrintableExport(array $forms, array $approvalsByForm, array $formLabel): void {
        header('Content-Type: text/html; charset=utf-8');
        echo $this->buildExportHtml($forms, $approvalsByForm, $formLabel, true);
    }

    private function buildExportHtml(array $forms, array $approvalsByForm, array $formLabel, bool $autoPrint = false): string {
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Completed Requests Export</title>
<style>
    body { font-family: Calibri, Arial, sans-serif; color: #1a1a1a; max-width: 900px; margin: 2rem auto; }
    h1 { font-size: 20px; margin-bottom: 4px; }
    .meta { color: #666; font-size: 13px; margin-bottom: 1.5rem; }
    .form-section { page-break-inside: avoid; border-top: 2px solid #1a1a1a; padding: 1rem 0; }
    .form-section h2 { font-size: 16px; margin: 0 0 4px; }
    .form-meta { font-size: 13px; color: #444; margin-bottom: 0.75rem; }
    h3 { font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: #555; margin: .75rem 0 .25rem; }
    ul { margin: 0; padding-left: 1.25rem; font-size: 13px; }
    li { margin-bottom: 2px; }
    @media print { .form-section { break-inside: avoid; } }
</style>
</head>
<body<?= $autoPrint ? ' onload="window.print()"' : '' ?>>
    <h1>Completed Requests Export</h1>
    <div class="meta">Generated <?= date('F j, Y g:i A') ?> — <?= count($forms) ?> request(s)</div>

    <?php foreach ($forms as $row):
        $data = json_decode($row['data'] ?? '', true) ?: [];
    ?>
    <div class="form-section">
        <h2>#<?= (int) $row['id'] ?> — <?= htmlspecialchars($formLabel[$row['form_type']] ?? $row['form_type']) ?></h2>
        <div class="form-meta">
            <strong><?= htmlspecialchars($row['full_name']) ?></strong> (<?= htmlspecialchars($row['employee_code']) ?>)
            &nbsp;·&nbsp; <?= htmlspecialchars($row['department'] ?? '—') ?>
            &nbsp;·&nbsp; <?= htmlspecialchars($row['company'] ?? '—') ?><br>
            Filed <?= date('M j, Y g:i A', strtotime($row['created_at'])) ?>
            &nbsp;·&nbsp; Completed <?= date('M j, Y g:i A', strtotime($row['updated_at'])) ?>
        </div>

        <h3>Form Details</h3>
        <ul>
            <?php foreach ($this->flattenFormData($data) as $line): ?>
                <li><?= htmlspecialchars($line) ?></li>
            <?php endforeach; ?>
        </ul>

        <h3>Approval Trail</h3>
        <ul>
            <?php foreach ($approvalsByForm[(int) $row['id']] ?? [] as $a): ?>
                <li><?= htmlspecialchars($this->formatApprovalLine($a)) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endforeach; ?>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    private function audit(string $action, string $entity, int $entityId, ?array $old, ?array $new): void {
        db()->prepare(
            'INSERT INTO audit_logs (performed_by, action, entity_type, entity_id, old_values, new_values, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $_SESSION['user_id'], $action, $entity, $entityId,
            $old ? json_encode($old) : null,
            $new ? json_encode($new) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    private function resolveType(string $slug): string {
        if (!isset($this->typeMap[$slug])) return $this->renderError(404, 'Not Found', 'Unknown form type.');
        return $this->typeMap[$slug];
    }

    public function edit(int $id): void {
        $form = $this->findForm($id);

        if ($_SESSION['role_id'] != 1 && $form['submitted_by'] != $_SESSION['user_id']) {
            $this->renderError(403, 'Access Denied', 'You can only edit your own submissions.');
        }

        $editableStatuses = ['draft', 'submitted', 'rejected'];
        if (!in_array($form['status'], $editableStatuses, true)) {
            $_SESSION['error'] = 'This form cannot be edited while it is in approval or completed.';
            header('Location: ' . url('forms/view/' . $id));
            exit;
        }

        $data = json_decode($form['data'], true) ?? [];
        $type = $form['form_type'];
        $slug = array_search($type, $this->typeMap) ?: $type;
        $formLabel = \App\Helpers\FormLabels::all();
        $typeLabel = \App\Helpers\FormLabels::get($type);
        $pageTitle = 'Edit ' . $typeLabel . ' #' . $id;
        $departments = db()->query(
            'SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department'
        )->fetchAll(PDO::FETCH_COLUMN);

        $breadcrumbs = [
            ['label' => $typeLabel, 'url' => url('forms/' . $slug)],
            ['label' => '#' . $id,  'url' => url('forms/view/' . $id)],
            ['label' => 'Edit'],
        ];

        $noSuffix = ['list', 'show', 'request_for_payment'];
        $viewName = in_array($type, $noSuffix) ? $type : "{$type}_form";

        $this->render("forms/{$viewName}", compact(
            'form', 'data', 'type', 'slug', 'formLabel', 'typeLabel',
            'pageTitle', 'breadcrumbs', 'departments'
        ));
    }

    public function update(int $id): void {
        \App\Helpers\Csrf::verify();

        $form = $this->findForm($id);

        if ($_SESSION['role_id'] != 1 && $form['submitted_by'] != $_SESSION['user_id']) {
            $this->renderError(403, 'Access Denied', 'You can only edit your own submissions.');
        }

        $editableStatuses = ['draft', 'submitted', 'rejected'];
        if (!in_array($form['status'], $editableStatuses, true)) {
            $_SESSION['error'] = 'This form cannot be edited in its current status.';
            header('Location: ' . url('forms/view/' . $id));
            exit;
        }

        $type = $form['form_type'];
        $fields = $this->fields[$type] ?? [];
        $data = [];

        foreach ($fields as $field) {
            $val = $_POST[$field] ?? '';
            if (is_string($val)) $val = trim($val);
            if ($val === '' || (is_array($val) && empty(array_filter($val)))) {
                $_SESSION['error'] = "Field '{$field}' is required.";
                header('Location: ' . url('forms/' . $id . '/edit'));
                exit;
            }
        }

        foreach ($_POST as $key => $val) {
            if (in_array($key, ['csrf_token', 'save_draft', 'existing_attachments'], true)) continue;
            if (is_array($val)) {
                $data[$key] = array_map(fn($v) => htmlspecialchars(trim($v), ENT_QUOTES), $val);
            } else {
                $data[$key] = htmlspecialchars(trim($val), ENT_QUOTES);
            }
        }

        $existing = array_values(array_filter($_POST['existing_attachments'] ?? []));

        if (!empty($_FILES['attachments']['name'][0])) {
            $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
            $maxBytes = 5 * 1024 * 1024;
            $destDir = __DIR__ . '/../../public/uploads/forms/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            $finfo = new \finfo(FILEINFO_MIME_TYPE);

            foreach ($_FILES['attachments']['tmp_name'] as $i => $tmp) {
                if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $mime = $finfo->file($tmp);
                if (!in_array($mime, $allowed, true) || $_FILES['attachments']['size'][$i] > $maxBytes) {
                    $_SESSION['error'] = 'Invalid file or file exceeds 5 MB.';
                    header('Location: ' . url('forms/' . $id . '/edit'));
                    exit;
                }
                $ext = pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION);
                $fileName = sprintf('%s_%s.%s', time(), bin2hex(random_bytes(4)), $ext);
                move_uploaded_file($tmp, $destDir . $fileName);
                $existing[] = 'uploads/forms/' . $fileName;
            }
        }

        if (!empty($existing)) $data['attachments'] = $existing;

        $old = json_decode($form['data'], true) ?? [];

        try {
            db()->prepare('UPDATE forms SET data = ?, updated_at = NOW() WHERE id = ?')
                ->execute([json_encode($data), $id]);
            $this->audit('form_edited', 'form', $id, $old, $data);
            $_SESSION['success'] = 'Form updated successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Update failed. Please try again.';
        }

        header('Location: ' . url('forms/view/' . $id));
        exit;
    }

    public function delete(int $id): void {
        \App\Helpers\Csrf::verify();

        $form = $this->findForm($id);

        if ($_SESSION['role_id'] != 1 && $form['submitted_by'] != $_SESSION['user_id']) {
            $this->renderError(403, 'Access Denied', 'You can only delete your own submissions.');
        }

        $deletableStatuses = ['draft', 'submitted', 'rejected'];
        if (!in_array($form['status'], $deletableStatuses, true)) {
            $_SESSION['error'] = 'This form cannot be deleted while it is in approval or completed.';
            header('Location: ' . url('forms/view/' . $id));
            exit;
        }

        try {
            $pdo = db();
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM approvals WHERE form_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM forms WHERE id = ?')->execute([$id]);
            $this->audit('form_deleted', 'form', $id, ['type' => $form['form_type'], 'status' => $form['status']], null);
            $pdo->commit();
            $_SESSION['success'] = 'Form #' . $id . ' has been deleted.';
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Delete failed. Please try again.';
            header('Location: ' . url('forms/view/' . $id));
            exit;
        }

        header('Location: ' . url('my-submissions'));
        exit;
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
        if (!isset($pageTitle)) $pageTitle = 'Automated Requesting System';
        ob_start();
        require $fullPath;
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/base.php';
    }
}