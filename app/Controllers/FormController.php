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

    private const HR_VERIFIER_ROLE = 8;

    private const HR_COSIGN_FORM_TYPES = ['reimbursement', 'liquidation'];

    private function adminApproverStandsInFor(string $formType, int $stageRoleId): bool {
        $covered = self::ADMIN_APPROVER_STANDIN_COVERAGE[$formType] ?? [];
        return in_array($stageRoleId, $covered, true);
    }

    private function roleSatisfiesStage(int $actorRole, int $requiredRole, string $formType = ''): bool {
        if ($actorRole === $requiredRole) return true;
        if ($actorRole === 7 && in_array($requiredRole, self::ADMIN_APPROVER_COVERS, true)) return true;
        if ($actorRole === self::HR_VERIFIER_ROLE && $requiredRole === 5
            && in_array($formType, self::HR_COSIGN_FORM_TYPES, true)) return true;
        return false;
    }

    private const FORM_CATEGORIES = [
        'admin' => ['overtime_authorization', 'leave_application', 'vehicle_request'],
        'finance' => ['advance_payment', 'request_for_payment', 'reimbursement', 'liquidation'],
    ];

    private const PIPELINE_ADMIN = [
        'submit' => ['sequence' => 1, 'from' => 'draft', 'to' => 'submitted', 'role_id' => 3, 'label' => 'Submitted'],
        'checker-approval' => ['sequence' => 2, 'from' => 'submitted', 'to' => 'immediatehead_approved', 'role_id' => 2, 'label' => 'Immediate Head Approval'],
        'review-approval' => ['sequence' => 3, 'from' => 'immediatehead_approved', 'to' => 'department_reviewed', 'role_id' => 4, 'label' => 'Review Approval'],
        'grant-approval' => ['sequence' => 4, 'from' => 'department_reviewed', 'to' => 'completed', 'role_id' => 6, 'label' => 'Grant Approval Request'],
    ];

    private const PIPELINE_FINANCE = [
        'submit' => ['sequence' => 1, 'from' => 'draft', 'to' => 'submitted', 'role_id' => 3, 'label' => 'Submitted'],
        'checker-approval' => ['sequence' => 2, 'from' => 'submitted', 'to' => 'immediatehead_approved', 'role_id' => 2, 'label' => 'Immediate Head Approval'],
        'process-approval' => ['sequence' => 3, 'from' => 'immediatehead_approved', 'to' => 'process_approved', 'role_id' => 5, 'label' => 'Process Approval'],
        'evaluation-approval' => ['sequence' => 4, 'from' => 'process_approved', 'to' => 'finance_reviewed', 'role_id' => 4, 'label' => 'Evaluation Approval'],
        'grant-approval' => ['sequence' => 5, 'from' => 'finance_reviewed', 'to' => 'final_approved', 'role_id' => 6, 'label' => 'Grant Approval Request'],
        'complete' => ['sequence' => 6, 'from' => 'final_approved', 'to' => 'completed', 'role_id' => 1, 'label' => 'Completed'],
    ];

    private function getPipeline(string $formType): array {
        return in_array($formType, self::FORM_CATEGORIES['finance'], true)
            ? self::PIPELINE_FINANCE
            : self::PIPELINE_ADMIN;
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
        } elseif (in_array($roleId, [2, 4, 5, 6, 7, 8])) {
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

        $allowedRoles = [1, 2, 4, 5, 6, 7];
        if (!in_array($roleId, $allowedRoles, true)) {
            $_SESSION['error'] = 'You are not authorised to reject this form.';
            header("Location: " . url("forms/view/{$id}"));
            exit;
        }

        $isAdminApproverStandIn = $roleId === 7;

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

            if ($isAdminApproverStandIn) {
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
            }

            if ($isAdminApproverStandIn) {
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

        if (!$approval && ($isAdmin || $isAdminApproverStandIn)) {
            $fallback = db()->prepare(
                'SELECT * FROM approvals WHERE form_id = ? AND sequence = ? AND status = \'pending\' LIMIT 1'
            );
            $fallback->execute([$id, $step['sequence']]);
            $approval = $fallback->fetch();
        }

        if (!$isAdmin && !$isAdminApproverStandIn && $action !== 'submit') {
            if (!$approval || (int)$approval['approver_id'] !== $userId) {
                $_SESSION['error'] = 'No pending approval step found for you at this stage.';
                header("Location: " . url("forms/view/{$id}"));
                exit;
            }
        }

        if ($isAdminApproverStandIn && !$isAdmin && !$approval) {
            $_SESSION['error'] = 'No pending approval step found at this stage.';
            header("Location: " . url("forms/view/{$id}"));
            exit;
        }

        $actorAllowed = $isAdmin || $isAdminApproverStandIn || $action === 'submit'
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
                $pdo->prepare(
                    "UPDATE approvals
                     SET status = 'approved', remarks = ?, file_path = ?, approved_at = NOW()
                     WHERE id = ?"
                )->execute([
                    $remarks ?: ($isAdmin ? '(Admin override)' : ($isAdminApproverStandIn ? '(AdminApprover stand-in)' : $step['label'])),
                    $uploadedFilePath,
                    $approval['id'],
                ]);
            }

            $isRaceStage = (int) $step['role_id'] === 6;

            if ($isRaceStage) {
                $pdo->prepare(
                    "UPDATE approvals SET status = 'skipped',
                            remarks = 'Auto-skipped — already approved by another qualified approver',
                            updated_at = NOW()
                     WHERE form_id = ? AND sequence = ? AND status = 'pending'"
                )->execute([$id, $step['sequence']]);
                $advanced = true;
            } else {
                $remaining = $pdo->prepare(
                    "SELECT COUNT(*) FROM approvals WHERE form_id = ? AND sequence = ? AND status = 'pending'"
                );
                $remaining->execute([$id, $step['sequence']]);
                $stillPending = (int) $remaining->fetchColumn();
                $advanced = $isAdmin || $stillPending === 0;
            }

            if ($advanced) {
                $pdo->prepare('UPDATE forms SET status = ? WHERE id = ?')->execute([$step['to'], $id]);
            }

            $this->audit(
                'form_' . str_replace('-', '_', $action), 'form', $id,
                ['status' => $form['status']],
                ['status' => $advanced ? $step['to'] : $form['status'], 'remarks' => $remarks]
            );

            $pdo->commit();
            $_SESSION['success'] = $advanced
                ? $step['label'] . ' recorded successfully.'
                : $step['label'] . ' recorded. Waiting on the other checker before this moves forward.';

            unset($_SESSION["pending_count_{$userId}"], $_SESSION["pending_count_ts_{$userId}"]);

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

    private function store(string $type, string $slug): void {
        \App\Helpers\Csrf::verify();

        $uploadedPaths = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            $allowed  = ['image/jpeg', 'image/png', 'application/pdf'];
            $maxBytes = 5 * 1024 * 1024;
            $destDir  = __DIR__ . '/../../public/uploads/forms/';
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
                $ext      = pathinfo($_FILES['attachments']['name'][$i],
                PATHINFO_EXTENSION);
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

        $insert = $pdo->prepare(
            "INSERT INTO approvals (form_id, approver_id, sequence, status) VALUES (?, ?, ?, 'pending')"
        );
        $insert->execute([$formId, $submitterId, 1]);

        $stagesNeedingApprover = array_filter($pipeline, fn($step) => $step['sequence'] >= 2);
        foreach ($stagesNeedingApprover as $action => $step) {
            $approvers = $this->resolveApproversByRole($pdo, $step['role_id'], $data, $submitterId, $type);
            if (empty($approvers)) {
                throw new \RuntimeException("No active approver found for stage '{$step['label']}'. Please ensure your supervisor and department approvers are correctly configured.");
            }
            foreach ($approvers as $approverId) {
                $insert->execute([$formId, $approverId, $step['sequence']]);
            }

            if ($action === 'process-approval' && in_array($type, self::HR_COSIGN_FORM_TYPES, true)) {
                $hrApprovers = $this->resolveApproversByRole($pdo, self::HR_VERIFIER_ROLE, $data, $submitterId, $type);
                if (empty($hrApprovers)) {
                    throw new \RuntimeException("No active HR Attendance Verifier found for stage '{$step['label']}'. Please ensure an HR Verifier is correctly configured.");
                }
                foreach ($hrApprovers as $hrApproverId) {
                    $insert->execute([$formId, $hrApproverId, $step['sequence']]);
                }
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
            $ids = [];
            $realApprover = $pdo->query(
                "SELECT e.id, COUNT(a.id) AS workload
                 FROM employees e
                 LEFT JOIN approvals a ON a.approver_id = e.id AND a.status = 'pending'
                 WHERE e.role_id = 6 AND e.is_active = 1
                 GROUP BY e.id ORDER BY workload ASC, e.id ASC LIMIT 1"
            )->fetch(\PDO::FETCH_ASSOC);
            if ($realApprover) $ids[] = (int) $realApprover['id'];

            $adminApprover = $pdo->query(
                "SELECT e.id, COUNT(a.id) AS workload
                 FROM employees e
                 LEFT JOIN approvals a ON a.approver_id = e.id AND a.status = 'pending'
                 WHERE e.role_id = 7 AND e.is_active = 1
                 GROUP BY e.id ORDER BY workload ASC, e.id ASC LIMIT 1"
            )->fetch(\PDO::FETCH_ASSOC);
            if ($adminApprover) $ids[] = (int) $adminApprover['id'];

            return array_values(array_unique($ids));
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
        }

        // Workload-balanced fallback (department filter skipped for global roles)
        $dept = $data['department'] ?? null;
        $sql = "SELECT e.id, COUNT(a.id) AS workload
                FROM employees e
                LEFT JOIN approvals a ON a.approver_id = e.id AND a.status = 'pending'
                WHERE e.role_id = ? AND e.is_active = 1";
        $params = [$roleId];

        if ($dept && !in_array($roleId, [1, 4, 5, 6, 7, 8], true)) {
            $sql .= ' AND e.department = ?';
            $params[] = $dept;
        }
        $sql .= ' GROUP BY e.id ORDER BY workload ASC, e.id ASC LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) return [(int) $row['id']];

        if ($roleId === 4) {
            $fallback = $pdo->prepare(
                "SELECT e.id, COUNT(a.id) AS workload
                 FROM employees e
                 LEFT JOIN approvals a ON a.approver_id = e.id AND a.status = 'pending'
                 WHERE e.role_id = 7 AND e.is_active = 1
                 GROUP BY e.id ORDER BY workload ASC, e.id ASC LIMIT 1"
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

        // A pending row is "active" for user X when no pending row at a strictly
        // lower sequence belongs to a different approver — mirrors NOT EXISTS
        // logic in ApprovalController::inbox().
        foreach ($steps as $step) {
            if ((int)$step['approver_id'] !== $userId || $step['status'] !== 'pending') continue;
            $mySeq = (int)$step['sequence'];
            $blocked = false;
            foreach ($steps as $other) {
                if ((int)$other['approver_id'] !== $userId
                    && $other['status'] === 'pending'
                    && (int)$other['sequence'] < $mySeq
                    && (int)($other['approver_role_id'] ?? 0) !== self::HR_VERIFIER_ROLE
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

        // HR Verifier (role 8) co-sign
        if ($roleId === self::HR_VERIFIER_ROLE
            && in_array($form['form_type'], self::HR_COSIGN_FORM_TYPES, true)) {
            foreach ($steps as $step) {
                if ((int)$step['approver_id'] === $userId && $step['status'] === 'pending') return true;
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
            $formLabel   = \App\Helpers\FormLabels::get($form['form_type']);
            $stageName   = $step['label'];
            $newStatus   = $step['to'];

            $outcome = match($newStatus) {
                'completed'     => 'completed',
                'final_approved'=> 'final_approved',
                'rejected'      => 'rejected',
                default         => 'approved_step',
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
        $errorCode    = $code;
        $errorTitle   = $title;
        $errorMessage = $message;
        $pageTitle    = "{$code} — {$title}";
        define('BASE_LOADED', true);
        ob_start();
        require __DIR__ . '/../../views/error/error.php';
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

        $data      = json_decode($form['data'], true) ?? [];
        $type      = $form['form_type'];
        $slug      = array_search($type, $this->typeMap) ?: $type;
        $formLabel = \App\Helpers\FormLabels::all();
        $typeLabel = \App\Helpers\FormLabels::get($type);
        $pageTitle = 'Edit ' . $typeLabel . ' #' . $id;

        $departments = db()->query(
            'SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department'
        )->fetchAll(PDO::FETCH_COLUMN);

        $breadcrumbs = [
            ['label' => $typeLabel, 'url' => url('forms/' . $slug)],
            ['label' => '#' . $id, 'url' => url('forms/view/' . $id)],
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

        $type   = $form['form_type'];
        $fields = $this->fields[$type] ?? [];
        $data   = [];

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
            $allowed  = ['image/jpeg', 'image/png', 'application/pdf'];
            $maxBytes = 5 * 1024 * 1024;
            $destDir  = __DIR__ . '/../../public/uploads/forms/';
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
                $ext      = pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION);
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
        if (!isset($pageTitle)) $pageTitle = 'Processing System';
        ob_start();
        require $fullPath;
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layouts/base.php';
    }
}
