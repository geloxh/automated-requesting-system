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

    $counts = ['draft' => 0, 'submitted' => 0, 'in_approval' => 0, 'approved' => 0, 'rejected' => 0];
    foreach ($forms as $f) if (isset($counts[$f['status']])) $counts[$f['status']]++;
?>

<div class="page-heading">Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?> 👋</div>
<div class="page-subheading"><?= date('l, F j, Y') ?> — here's your current activity.</div>

<div class="kpi-grid">
    <div class="kpi-card blue">
        <div class="kpi-icon blue"><i class="ti ti-send"></i></div>
        <div class="kpi-label">Submitted</div>
        <div class="kpi-value"><?= $counts['submitted'] ?></div>
        <div class="kpi-delta">Awaiting review</div>
    </div>
    <div class="kpi-card amber">
        <div class="kpi-icon amber"><i class="ti ti-hourglass"></i></div>
        <div class="kpi-label">In Approval</div>
        <div class="kpi-value"><?= $counts['in_approval'] ?></div>
        <div class="kpi-delta">Pending decisions</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-icon green"><i class="ti ti-circle-check"></i></div>
        <div class="kpi-label">Approved</div>
        <div class="kpi-value"><?= $counts['approved'] ?></div>
        <div class="kpi-delta">Completed requests</div>
    </div>
    <div class="kpi-card purple">
        <div class="kpi-icon purple"><i class="ti ti-circle-x"></i></div>
        <div class="kpi-label">Rejected</div>
        <div class="kpi-value"><?= $counts['rejected'] ?></div>
        <div class="kpi-delta">Needs resubmission</div>
    </div>
</div>

<div class="section-row">
    <div class="card-panel">
        <div class="card-panel-header">
            <span class="card-panel-title">Recent Activity</span>
        </div>
        <?php if (empty($forms)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                No forms found.
            </div>
        <?php else: ?>
            <?php
            $iconMap = [
                'advance_payment' => ['bg' => '#d1fae5', 'color' => '#10b981', 'icon' => 'ti-cash'],
                'overtime_authorization' => ['bg' => '#ede9fe', 'color' => '#8b5cf6', 'icon' => 'ti-clock-hour-4'],
                'request_for_payment' => ['bg' => '#fce7f3', 'color' => '#ec4899', 'icon' => 'ti-receipt'],
                'work_permit' => ['bg' => '#fef3c7', 'color' => '#f59e0b', 'icon' => 'ti-clipboard-list'],
                'leave_application' => ['bg' => '#dbeafe', 'color' => '#0ea5e9', 'icon' => 'ti-beach'],
                'reimbursement' => ['bg' => '#ffedd5', 'color' => '#f97316', 'icon' => 'ti-credit-card-refund'],
                'liquidation' => ['bg' => '#e0f2fe', 'color' => '#0284c7', 'icon' => 'ti-calculator'],
                'vehicle_request' => ['bg' => '#fef9c3', 'color' => '#ca8a04', 'icon' => 'ti-car'],
            ];
            $badgeMap = [
                'draft' => 'secondary',
                'submitted' => 'primary',
                'in_approval' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
                'cancelled' => 'dark',
            ];
            foreach (array_slice($forms, 0, 8) as $form):
                $ic = $iconMap[$form['form_type']] ?? ['bg' => '#e2e8f0', 'color' => '#64748b', 'icon' => 'ti-file'];
                $ago = (new DateTime())->diff(new DateTime($form['created_at']));
                $timeStr = $ago->days >= 1
                    ? date('M d', strtotime($form['created_at']))
                    : ($ago->h >= 1 ? $ago->h . 'h ago' : ($ago->i >= 1 ? $ago->i . 'm ago' : 'Just now'));
            ?>
            <div class="activity-item">
                <div class="activity-icon" style="background:<?= $ic['bg'] ?>;color:<?= $ic['color'] ?>">
                    <i class="ti <?= $ic['icon'] ?>"></i>
                </div>
                <div>
                    <div class="activity-text"><?= htmlspecialchars($formLabel[$form['form_type']] ?? $form['form_type']) ?></div>
                    <div class="activity-sub"><?= htmlspecialchars($form['full_name']) ?></div>
                </div>
                <div class="activity-time">
                    <div style="margin-bottom:4px"><?= $timeStr ?></div>
                    <span class="badge badge-<?= $badgeMap[$form['status']] ?? 'secondary' ?>">
                        <?= ucfirst(str_replace('_', ' ', $form['status'])) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card-panel">
        <div class="card-panel-header">
            <span class="card-panel-title">New Request</span>
        </div>
        <?php
        $quickForms = [
            ['slug' => 'advance-payment', 'label' => 'Advance', 'desc' => 'Cash advance', 'color' => '#10b981', 'icon' => 'ti-cash'],
            ['slug' => 'overtime', 'label' => 'Overtime', 'desc' => 'OT authorization','color' => '#8b5cf6', 'icon' => 'ti-clock-hour-4'],
            ['slug' => 'leave', 'label' => 'Leave', 'desc' => 'File absence', 'color' => '#0ea5e9', 'icon' => 'ti-beach'],
            ['slug' => 'vehicle-request', 'label' => 'Vehicle', 'desc' => 'Reserve vehicle', 'color' => '#f59e0b', 'icon' => 'ti-car'],
            ['slug' => 'request-payment', 'label' => 'Payment', 'desc' => 'Request payment', 'color' => '#ec4899', 'icon' => 'ti-receipt'],
            ['slug' => 'reimbursement', 'label' => 'Reimburse', 'desc' => 'Claim expenses', 'color' => '#f97316', 'icon' => 'ti-credit-card-refund'],
        ];
        ?>
        <div style="display:grid;grid-template-columns:repeat(2,1fr)">
            <?php foreach ($quickForms as $i => $qf): ?>
            <a href="/processing-system/public/forms/<?= $qf['slug'] ?>/create"
               style="display:flex;flex-direction:column;gap:4px;padding:14px 16px;
                      border-right:<?= ($i % 2 === 0) ? '1px solid var(--border)' : 'none' ?>;
                      border-bottom:<?= ($i < 4) ? '1px solid var(--border)' : 'none' ?>;
                      text-decoration:none;transition:background .15s;"
               onmouseover="this.style.background='var(--surface)'"
               onmouseout="this.style.background='transparent'">
                <span style="font-size:20px;color:<?= $qf['color'] ?>"><i class="ti <?= $qf['icon'] ?>"></i></span>
                <span style="font-size:12.5px;font-weight:600;color:var(--text-main)"><?= $qf['label'] ?></span>
                <span style="font-size:11px;color:var(--text-muted)"><?= $qf['desc'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$content   = ob_get_clean();
$pageTitle = 'Dashboard';
require __DIR__ . '/base.php';