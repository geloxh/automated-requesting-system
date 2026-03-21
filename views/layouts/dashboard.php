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
            'SELECT f.id, f.form_type, f.status, e.full_name, f.created_at
            FROM forms f JOIN employees e ON e.id = f.submitted_by
            JOIN approvals a ON a.form_id = f.id
            WHERE a.approver_id = ? AND a.status = "pending"
            ORDER BY a.sequence ASC, f.created_at ASC'
        );
        $stmt->execute([$userId]);
    } else {
        $stmt = db()->prepare(
            'SELECT id, form_type, status, created_at
            FROM forms WHERE submitted_by = ?
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

    ob_start();

    $counts = ['submitted' => 0, 'in_approval' => 0, 'approved' => 0, 'rejected' => 0];
    foreach ($forms as $f) if (isset($counts[$f['status']])) $counts[$f['status']]++;

    $stats = [
        ['label' => 'Submitted',   'key' => 'submitted',   'icon' => '📤', 'color' => '#4361ee'],
        ['label' => 'In Approval', 'key' => 'in_approval', 'icon' => '⏳', 'color' => '#f4a261'],
        ['label' => 'Approved',    'key' => 'approved',    'icon' => '✅', 'color' => '#2a9d8f'],
        ['label' => 'Rejected',    'key' => 'rejected',    'icon' => '❌', 'color' => '#e63946'],
    ];
?>

<p class="welcome-text">
    Welcome back, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong> — here's your current activity.
</p>

<div class="stat-grid">
<?php foreach ($stats as $s): ?>
    <div class="stat-card" style="border-left: 3px solid <?= $s['color'] ?>">
        <span style="font-size:1.2rem"><?= $s['icon'] ?></span>
        <div>
            <div class="stat-count" style="color:<?= $s['color'] ?>"><?= $counts[$s['key']] ?></div>
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
                <th style="padding-left:1.25rem">#</th>
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
                <td class="muted" style="padding-left:1.25rem"><?= $form['id'] ?></td>
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
                <td class="text-end" style="padding-right:1rem">
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