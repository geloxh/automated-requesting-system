<?php

class Approval {
    private $pdo;

    /**
     * Pipeline levels — mirrors the PIPELINE constant in FormController.
     *
     * sequence → the approvals.sequence value
     * role   → role_id of the employee allowed to approve at this level
     * label  → human-readable stage name
     * status → the value written to forms.status after this level passes
     */
    private const LEVELS = [
        1 => ['role' => 3, 'label' => 'Submitted', 'status' => 'submitted'],
        2 => ['role' => 2, 'label' => 'Checker Approval', 'status' => 'checker_approved'],
        3 => ['role' => 5, 'label' => 'Process Approval', 'status' => 'process_approved'],
        4 => ['role' => 4, 'label' => 'Evaluation Approval', 'status' => 'finance_reviewed'],
        5 => ['role' => 6, 'label' => 'Final Approval', 'status' => 'final_approved'],
        6 => ['role' => 1, 'label' => 'Completed', 'status' => 'completed'],
    ];

    public const MAX_LEVEL = 6;

    public function __construct() {
        $this->pdo = new PDO(
            "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4",
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    // ----------------------------------------------------------------
    // CREATE
    // ----------------------------------------------------------------

    /**
     * Insert a new approval record at level 1 (draft).
     * Returns the new row ID.
     */
    public function create(array $data): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO approvals (form_id, approver_id, sequence, status, assigned_at)
            VALUES (:form_id, :approver_id, :sequence, 'pending', NOW())
        ");
        $stmt->execute([
            ':form_id' => $data['form_id'],
            ':approver_id' => $data['approver_id'],
            ':sequence' => $data['sequence'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    // ----------------------------------------------------------------
    // READ
    // ----------------------------------------------------------------

    public function find(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT a.*, e.full_name AS approver_name
            FROM approvals a
            LEFT JOIN employees e ON e.id = a.approver_id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByForm(int $formId): array {
        $stmt = $this->pdo->prepare("
            SELECT a.*, e.full_name AS approver_name
            FROM approvals a
            LEFT JOIN employees e ON e.id = a.approver_id
            WHERE a.form_id = ?
            ORDER BY a.sequence ASC
        ");
        $stmt->execute([$formId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Return the single pending approval row for a given form. */
    public function currentPending(int $formId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM approvals
            WHERE form_id = ? AND status = 'pending'
            ORDER BY sequence ASC
            LIMIT 1
        ");
        $stmt->execute([$formId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Full audit trail for a form, newest last. */
    public function logs(int $formId): array {
        $stmt = $this->pdo->prepare("
            SELECT al.*, e.full_name
            FROM audit_logs al
            JOIN employees e ON e.id = al.performed_by
            WHERE al.form_id = ?
            ORDER BY al.created_at ASC
        ");
        $stmt->execute([$formId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ----------------------------------------------------------------
    // PIPELINE ACTIONS
    // ----------------------------------------------------------------

    /**
     * Advance the approval to the next level.
     *
     * Validates:
     *  - the approval row exists
     *  - it is still 'pending'
     *  - the acting user's role matches the current level's required role
     *    (admin role_id = 1 bypasses role check)
     *
     * On success:
     *  - marks current row approved
     *  - inserts a new pending row at level + 1
     *    OR marks the form fully completed if already at MAX_LEVEL
     *
     * Returns ['ok' => true] or ['ok' => false, 'error' => '...']
     */
    public function advance(int $id, int $actorId, int $actorRole, string $remarks = ''): array {
        $row = $this->find($id);

        if (!$row) {
            return ['ok' => false, 'error' => 'Approval record not found.'];
        }

        if ($row['status'] !== 'pending') {
            return ['ok' => false, 'error' => 'This approval step is no longer pending.'];
        }

        $sequence = (int) $row['sequence'];
        $levelCfg = self::LEVELS[$sequence] ?? null;

        if (!$levelCfg) {
            return ['ok' => false, 'error' => "Unknown approval sequence: {$sequence}."];
        }

        // Role check — admin (role 1) always passes
        if ($actorRole !== 1 && $actorRole !== $levelCfg['role']) {
            return ['ok' => false, 'error' => 'You are not authorised to approve at this level.'];
        }

        $this->pdo->beginTransaction();

        try {
            // 1. Mark current step as approved
            $this->updateStatus($id, 'approved', $actorId, $remarks);

            // 2. Log the action
            $this->log($row['form_id'], $sequence, 'approved', $actorId, $levelCfg['status'], $remarks);

            // 3. Move to next level or complete
            if ($sequence >= self::MAX_LEVEL) {
                // Final level passed — mark the form completed
                $this->setFormStatus($row['form_id'], 'completed');
            } else {
                $nextLevel = $sequence + 1;
                // Note: In the current architecture, seedApprovalRows handles initial creation. 
                // If using dynamic insertion, ensure logic for finding next approver is here.
                // For schema compliance, we fix the signature:
                // $this->insertLevel($row['form_id'], $nextLevel); 
                
                $this->setFormStatus($row['form_id'], self::LEVELS[$nextLevel]['status'] ?? 'in_approval');
            }

            $this->pdo->commit();
            return ['ok' => true, 'next_level' => $sequence < self::MAX_LEVEL ? $sequence + 1 : null];

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Reject the approval at any active level.
     * Remarks are required for rejection.
     */
    public function reject(int $id, int $actorId, int $actorRole, string $remarks): array {
        if (trim($remarks) === '') {
            return ['ok' => false, 'error' => 'A rejection reason is required.'];
        }

        $row = $this->find($id);

        if (!$row) {
            return ['ok' => false, 'error' => 'Approval record not found.'];
        }

        if ($row['status'] !== 'pending') {
            return ['ok' => false, 'error' => 'This approval step is no longer pending.'];
        }

        // Only roles that can approve at this level (or admin) may reject
        $levelCfg = self::LEVELS[$row['sequence']] ?? null;
        if ($actorRole !== 1 && $levelCfg && $actorRole !== $levelCfg['role']) {
            return ['ok' => false, 'error' => 'You are not authorised to reject at this level.'];
        }

        $this->pdo->beginTransaction();

        try {
            $this->updateStatus($id, 'rejected', $actorId, $remarks);
            $this->log($row['form_id'], $row['sequence'], 'rejected', $actorId, 'rejected', $remarks);
            $this->setFormStatus($row['form_id'], 'rejected');

            $this->pdo->commit();
            return ['ok' => true];

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    // ----------------------------------------------------------------
    // HELPERS
    // ----------------------------------------------------------------

    /**
     * Check whether a given actor (by role) can act on the current
     * pending step of a form. Used by the controller / view.
     */
    public function canAct(int $formId, int $actorId, int $actorRole): bool {
        if ($actorRole === 1) return true; // admin always can

        $pending = $this->currentPending($formId);
        if (!$pending) return false;

        $required = self::LEVELS[$pending['sequence']]['role'] ?? null;
        return $required !== null && $actorRole === $required;
    }

    /** Return the LEVELS config so views can render a progress stepper. */
    public static function pipeline(): array {
        return self::LEVELS;
    }

    // ----------------------------------------------------------------
    // PRIVATE
    // ----------------------------------------------------------------

    private function updateStatus(int $id, string $status, int $actorId, string $remarks): void {
        $stmt = $this->pdo->prepare("
            UPDATE approvals
            SET status = :status,
                remarks = :remarks,
                approved_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':status' => $status,
            ':remarks' => $remarks,
            ':id' => $id,
        ]);
    }

    /** Insert a fresh pending row for the next pipeline level. */
    private function insertLevel(int $formId, int $level): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO approvals (form_id, status, sequence, assigned_at, approver_id)
            SELECT form_id, 'pending', :level, NOW(), approver_id
            FROM approvals
            WHERE form_id = :form_id
            LIMIT 1
        ");
        $stmt->execute([':level' => $level, ':form_id' => $formId]);
    }

    private function setFormStatus(int $formId, string $status): void {
        $this->pdo->prepare("UPDATE forms SET status = ? WHERE id = ?")
             ->execute([$status, $formId]);
    }

    private function log(int $formId, int $sequence, string $action, int $actorId, string $resultStatus, string $remarks): void {
        $newVals = json_encode([
            'status' => $resultStatus,
            'remarks' => $remarks,
            'sequence' => $sequence
        ]);

        $this->pdo->prepare("
            INSERT INTO audit_logs (performed_by, action, entity_type, entity_id, old_values, new_values, ip_address)
            VALUES (
                :actor,
                :action,
                'form',
                :form_id,
                NULL,
                :new_vals,
                :ip
            )
        ")->execute([
            ':actor'   => $actorId,
            ':action'  => $action,
            ':form_id' => $formId,
            ':new_vals'=> $newVals,
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }
}
