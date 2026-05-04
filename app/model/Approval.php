<?php

class Approval
{
    private $pdo;

    /**
     * Pipeline levels — mirrors the PIPELINE constant in FormController.
     *
     * level  → the approvals.level value
     * role   → role_id of the employee allowed to approve at this level
     * label  → human-readable stage name
     * status → the value written to forms.status after this level passes
     */
    private const LEVELS = [
        1 => ['role' => 3, 'label' => 'Submitted',           'status' => 'submitted'],
        2 => ['role' => 2, 'label' => 'Supervisor Review',   'status' => 'supervisor_reviewed'],
        3 => ['role' => 4, 'label' => 'Department Check',    'status' => 'department_checked'],
        4 => ['role' => 5, 'label' => 'Checker Supervisor',  'status' => 'checker_approved'],
        5 => ['role' => 6, 'label' => 'Final Approval',      'status' => 'final_approved'],
        6 => ['role' => 6, 'label' => 'Completed',           'status' => 'completed'],
    ];

    public const MAX_LEVEL = 6;

    public function __construct()
    {
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
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO approvals (form_id, title, status, level, created_at)
            VALUES (:form_id, :title, 'pending', 1, NOW())
        ");
        $stmt->execute([
            ':form_id' => $data['form_id'],
            ':title'   => $data['title'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    // ----------------------------------------------------------------
    // READ
    // ----------------------------------------------------------------

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, e.full_name AS approver_name
            FROM   approvals a
            LEFT JOIN employees e ON e.id = a.approved_by
            WHERE  a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByForm(int $formId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, e.full_name AS approver_name
            FROM   approvals a
            LEFT JOIN employees e ON e.id = a.approved_by
            WHERE  a.form_id = ?
            ORDER  BY a.level ASC
        ");
        $stmt->execute([$formId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Return the single pending approval row for a given form. */
    public function currentPending(int $formId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM approvals
            WHERE  form_id = ? AND status = 'pending'
            ORDER  BY level ASC
            LIMIT  1
        ");
        $stmt->execute([$formId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Full audit trail for a form, newest last. */
    public function logs(int $formId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT al.*, e.full_name
            FROM   approval_logs al
            JOIN   employees e ON e.id = al.acted_by
            WHERE  al.form_id = ?
            ORDER  BY al.created_at ASC
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
    public function advance(int $id, int $actorId, int $actorRole, string $remarks = ''): array
    {
        $row = $this->find($id);

        if (!$row) {
            return ['ok' => false, 'error' => 'Approval record not found.'];
        }

        if ($row['status'] !== 'pending') {
            return ['ok' => false, 'error' => 'This approval step is no longer pending.'];
        }

        $level    = (int) $row['level'];
        $levelCfg = self::LEVELS[$level] ?? null;

        if (!$levelCfg) {
            return ['ok' => false, 'error' => "Unknown approval level: {$level}."];
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
            $this->log($row['form_id'], $level, 'approved', $actorId, $levelCfg['status'], $remarks);

            // 3. Move to next level or complete
            if ($level >= self::MAX_LEVEL) {
                // Final level passed — mark the form completed
                $this->setFormStatus($row['form_id'], 'completed');
            } else {
                $nextLevel = $level + 1;
                $this->insertLevel($row['form_id'], $nextLevel);
                $this->setFormStatus($row['form_id'], self::LEVELS[$nextLevel]['status'] ?? 'in_approval');
            }

            $this->pdo->commit();
            return ['ok' => true, 'next_level' => $level < self::MAX_LEVEL ? $level + 1 : null];

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Reject the approval at any active level.
     * Remarks are required for rejection.
     */
    public function reject(int $id, int $actorId, int $actorRole, string $remarks): array
    {
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
        $levelCfg = self::LEVELS[$row['level']] ?? null;
        if ($actorRole !== 1 && $levelCfg && $actorRole !== $levelCfg['role']) {
            return ['ok' => false, 'error' => 'You are not authorised to reject at this level.'];
        }

        $this->pdo->beginTransaction();

        try {
            $this->updateStatus($id, 'rejected', $actorId, $remarks);
            $this->log($row['form_id'], $row['level'], 'rejected', $actorId, 'rejected', $remarks);
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
    public function canAct(int $formId, int $actorId, int $actorRole): bool
    {
        if ($actorRole === 1) return true; // admin always can

        $pending = $this->currentPending($formId);
        if (!$pending) return false;

        $required = self::LEVELS[$pending['level']]['role'] ?? null;
        return $required !== null && $actorRole === $required;
    }

    /** Return the LEVELS config so views can render a progress stepper. */
    public static function pipeline(): array
    {
        return self::LEVELS;
    }

    // ----------------------------------------------------------------
    // PRIVATE
    // ----------------------------------------------------------------

    private function updateStatus(int $id, string $status, int $actorId, string $remarks): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE approvals
            SET    status      = :status,
                   approved_by = :actor,
                   remarks     = :remarks,
                   approved_at = NOW()
            WHERE  id = :id
        ");
        $stmt->execute([
            ':status'  => $status,
            ':actor'   => $actorId,
            ':remarks' => $remarks,
            ':id'      => $id,
        ]);
    }

    /** Insert a fresh pending row for the next pipeline level. */
    private function insertLevel(int $formId, int $level): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO approvals (form_id, title, status, level, created_at)
            SELECT form_id, title, 'pending', :level, NOW()
            FROM   approvals
            WHERE  form_id = :form_id
            LIMIT  1
        ");
        $stmt->execute([':level' => $level, ':form_id' => $formId]);
    }

    private function setFormStatus(int $formId, string $status): void
    {
        $this->pdo->prepare("UPDATE forms SET status = ? WHERE id = ?")
             ->execute([$status, $formId]);
    }

    private function log(int $formId, int $level, string $action, int $actorId, string $resultStatus, string $remarks): void
    {
        $this->pdo->prepare("
            INSERT INTO approval_logs
                (form_id, level, action, acted_by, result_status, remarks, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ")->execute([$formId, $level, $action, $actorId, $resultStatus, $remarks]);
    }
}
