<?php
define('BASE_LOADED', true);
use App\Middleware\AuthMiddleware;
AuthMiddleware::require();

$roleId = $_SESSION['role_id'];
$userId = $_SESSION['user_id'];

// Staff: their own submitted forms
// Approver: forms pending their approval
// Admin: all active forms
if ($roleId == 1) { // admin
    $stmt = db()->prepare(
        'SELECT f.id, f.form_type, f.status, e.full_name, f.created_at
         FROM forms f
         JOIN employees e ON e.id = f.submitted_by
         WHERE f.status NOT IN ("draft","cancelled")
         ORDER BY f.created_at DESC LIMIT 50'
    );
    $stmt->execute();
} elseif ($roleId == 2) { // approver
    $stmt = db()->prepare(
        'SELECT f.id, f.form_type, f.status, e.full_name, f.created_at
         FROM forms f
         JOIN employees e ON e.id = f.submitted_by
         JOIN approvals a ON a.form_id = f.id
         WHERE a.approver_id = ? AND a.status = "pending"
         ORDER BY a.sequence ASC, f.created_at ASC'
    );
    $stmt->execute([$userId]);
} else { // staff
    $stmt = db()->prepare(
        'SELECT id, form_type, status, created_at
         FROM forms
         WHERE submitted_by = ?
         ORDER BY created_at DESC LIMIT 30'
    );
    $stmt->execute([$userId]);
}

$forms = $stmt->fetchAll();

$statusBadge = [
    'draft'       => 'secondary',
    'submitted'   => 'primary',
    'in_approval' => 'warning',
    'approved'    => 'success',
    'rejected'    => 'danger',
    'cancelled'   => 'dark',
];

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

ob_start(); ?>

<h5 class="mb-4">Dashboard</h5>

<?php if (empty($forms)): ?>
    <p class="text-muted">No pending forms.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover bg-white rounded shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Form Type</th>
                <?php if ($roleId != 3): ?><th>Submitted By</th><?php endif; ?>
                <th>Status</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($forms as $form): ?>
            <tr>
                <td><?= $form['id'] ?></td>
                <td><?= $formLabel[$form['form_type']] ?? $form['form_type'] ?></td>
                <?php if ($roleId != 3): ?><td><?= htmlspecialchars($form['full_name']) ?></td><?php endif; ?>
                <td>
                    <span class="badge bg-<?= $statusBadge[$form['status']] ?? 'secondary' ?>">
                        <?= ucfirst(str_replace('_', ' ', $form['status'])) ?>
                    </span>
                </td>
                <td><?= date('M d, Y', strtotime($form['created_at'])) ?></td>
                <td>
                    <a href="/processing-system/public/forms/view/<?= $form['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php
$content   = ob_get_clean();
$pageTitle = 'Dashboard';
require __DIR__ . '/base.php';