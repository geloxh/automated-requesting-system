<?php
namespace App\Controllers;

use PDO;

class ApprovalController {
    /**
     * GET /approvals
     * Displays a list of forms awaiting action from the current user.
     */
    public function inbox(): void {
        $userId = (int) $_SESSION['user_id'];
        $roleId = (int) $_SESSION['role_id'];

        // We only show "pending" steps that match the current form status.
        // Sequence mapping (matches FormController::PIPELINE):
        // Status 'submitted'          -> next is sequence 2 (Supervisor)
        // Status 'supervisor_reviewed' -> next is sequence 3 (Dept Head)
        // Status 'department_checked'  -> next is sequence 4 (Checker)
        // Status 'checker_approved'    -> next is sequence 5 (Final Approver)
        // Status 'final_approved'      -> next is sequence 6 (Final Approver completion)

        $sql = "SELECT f.id, f.form_type, f.created_at, e.full_name as owner_name, e.department,
                       a.sequence, a.status as step_status
                FROM approvals a
                JOIN forms f ON f.id = a.form_id
                JOIN employees e ON e.id = f.submitted_by
                WHERE a.status = 'pending' ";

        // Admins (Role 1) see everything pending. 
        // Others only see steps specifically assigned to them that are active.
        if ($roleId !== 1) {
            $sql .= " AND a.approver_id = :userId 
                      AND (
                        (f.status = 'submitted' AND a.sequence = 2) OR
                        (f.status = 'supervisor_reviewed' AND a.sequence = 3) OR
                        (f.status = 'department_checked' AND a.sequence = 4) OR
                        (f.status = 'checker_approved' AND a.sequence = 5) OR
                        (f.status = 'final_approved' AND a.sequence = 6)
                      )";
        }

        $sql .= " ORDER BY f.created_at ASC";

        $stmt = db()->prepare($sql);
        if ($roleId !== 1) {
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formLabel = [
            'advance_payment' => 'Advance Payment',
            'overtime_authorization' => 'Overtime Authorization',
            'request_for_payment' => 'Request for Payment',
            'work_permit' => 'Work Permit',
            'leave_application' => 'Leave Application',
            'reimbursement' => 'Reimbursement',
            'liquidation' => 'Liquidation',
            'vehicle_request' => 'Vehicle Request',
        ];

        $pageTitle = 'Approval Inbox';
        $this->render('approvals/inbox', compact('approvals', 'formLabel', 'pageTitle'));
    }

    private function render(string $view, array $vars = []): void {
        define('BASE_LOADED', true);
        extract($vars);
        $uri = $_SERVER['REQUEST_URI'];
        
        ob_start();
        require __DIR__ . "/../../views/{$view}.php";
        $content = ob_get_clean();
        
        require __DIR__ . '/../../views/layouts/base.php';
    }
}