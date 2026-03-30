<?php
    define('BASE_LOADED', true);
    use App\Middleware\AuthMiddleware;
    AuthMiddleware::require();

    $roleId = $_SESSION['role_id'];
    $userId = $_SESSION['user_id'];

    if ($roleId == 1) {
        $stmt = db()->prepare(
            'SELECT f.id, f.form_type, f.status, e.full_name, f.created_at
            FROM forms f JOIN employees e ON e.id = f.submitted_by
            WHERE f.status NOT IN ("draft","cancelled")
            ORDER BY f.created_at DESC LIMIT 50'
        );
        $stmt->execute();
    } elseif ($roleId == 2) {
        $stmt = db()->prepare(
            'SELECT DISTINCT f.id, f.form_type, f.status, e.full_name, f.created_at
            FROM forms f JOIN employees e ON e.id = f.submitted_by
            JOIN approvals a ON a.form_id = f.id
            WHERE a.approver_id = ? AND a.status = "pending"
            ORDER BY f.created_at ASC'
        );
        $stmt->execute([$userId]);
    } else {
        $stmt = db()->prepare(
            'SELECT f.id, f.form_type, f.status, f.created_at, e.full_name
            FROM forms f JOIN employees e ON e.id = f.submitted_by
            WHERE f.submitted_by = ?
            ORDER BY f.created_at DESC LIMIT 30'
        );
        $stmt->execute([$userId]);
    }




    $forms = $stmt->fetchAll();

    $statusBadge = [
        'draft' => 'secondary',
        'submitted' => 'primary',
        'in_approval' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'cancelled' => 'dark',
    ];

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

    ob_start();

    $counts = ['submitted' => 0, 'in_approval' => 0, 'approved' => 0, 'rejected' => 0];
    foreach ($forms as $f) if (isset($counts[$f['status']])) $counts[$f['status']]++;

    $stats = [
        ['label' => 'Submitted', 'key' => 'submitted', 'icon' => '📤'],
        ['label' => 'In Approval', 'key' => 'in_approval', 'icon' => '⏳'],
        ['label' => 'Approved', 'key' => 'approved', 'icon' => '✅'],
        ['label' => 'Rejected', 'key' => 'rejected', 'icon' => '❌'],
    ];
?>

<p class="welcome-text">
    Welcome back, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong> — here's your current activity.
</p>

<div class="stat-grid">
<?php foreach ($stats as $s): ?>
    <div class="stat-card stat-<?= $s['key'] ?>">
        <span class="stat-icon"><?= $s['icon'] ?></span>
        <div>
            <div class="stat-count count-<?= $s['key'] ?>"><?= $counts[$s['key']] ?></div>
            <div class="stat-label"><?= $s['label'] ?></div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php if (empty($forms)): ?>
    <div class="empty-state">
        <div class="empty-icon">📭</div>
        No forms found.
    </div>
<?php else: ?>
<div class="table-wrap">
    <div class="table-wrap-header">Recent Forms</div>
    <table>
        <thead>
            <tr>
                <th class="th-first">#</th>
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
                <td class="muted td-first"><?= $form['id'] ?></td>
                <td><?= $formLabel[$form['form_type']] ?? $form['form_type'] ?></td>
                <?php if ($roleId != 3): ?>
                    <td><?= htmlspecialchars($form['full_name']) ?></td>
                <?php endif; ?>
                <td>
                    <span class="badge badge-<?= $statusBadge[$form['status']] ?? 'secondary' ?>">
                        <?= ucfirst(str_replace('_', ' ', $form['status'])) ?>
                    </span>
                </td>
                <td class="muted"><?= date('M d, Y', strtotime($form['created_at'])) ?></td>
                <td class="td-last text-end">
                    <a href="/processing-system/public/forms/view/<?= $form['id'] ?>" class="btn btn-ghost btn-sm">View</a>
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