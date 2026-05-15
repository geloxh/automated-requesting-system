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

    // KPI counts
    $counts = ['draft' => 0, 'submitted' => 0, 'in_approval' => 0, 'approved' => 0, 'rejected' => 0];
    foreach ($forms as $f) if (isset($counts[$f['status']])) $counts[$f['status']]++;

    // Form volume counts per type
    $typeCounts = [];
    foreach ($forms as $f) {
        $typeCounts[$f['form_type']] = ($typeCounts[$f['form_type']] ?? 0) + 1;
    }
    $maxTypeCount = max(array_values($typeCounts) ?: [1]);

    // Icon + color map
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

    $quickForms = [
        ['slug' => 'advance-payment', 'label' => 'Advance',   'desc' => 'Cash advance',    'color' => '#10b981', 'icon' => 'ti-cash'],
        ['slug' => 'overtime', 'label' => 'Overtime',  'desc' => 'OT authorization','color' => '#8b5cf6', 'icon' => 'ti-clock-hour-4'],
        ['slug' => 'leave', 'label' => 'Leave', 'desc' => 'File absence',    'color' => '#0ea5e9', 'icon' => 'ti-beach'],
        ['slug' => 'vehicle-request', 'label' => 'Vehicle', 'desc' => 'Reserve vehicle', 'color' => '#f59e0b', 'icon' => 'ti-car'],
        ['slug' => 'request-payment', 'label' => 'Payment', 'desc' => 'Request payment', 'color' => '#ec4899', 'icon' => 'ti-receipt'],
        ['slug' => 'reimbursement', 'label' => 'Reimburse', 'desc' => 'Claim expenses',  'color' => '#f97316', 'icon' => 'ti-credit-card-refund'],
    ];
?>

<!-- ── Page heading ── -->
<div class="page-heading">Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?> 👋</div>
<div class="page-subheading"><?= date('l, F j, Y') ?> — here's your current activity.</div>

<!-- ── KPI Cards ── -->
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

<!-- ── Section row: Activity + Right column ── -->
<div class="section-row">

    <!-- Activity feed -->
    <div class="card-panel">
        <div class="card-panel-header">
            <span class="card-panel-title">Recent Activity</span>
            <a href="/processing-system/public/forms/advance-payment" class="card-panel-link">View all →</a>
        </div>
        <?php if (empty($forms)): ?>
            <div class="empty-state">
                <i class="ti ti-inbox empty-state-icon"></i>
                No activity yet.
            </div>
        <?php else: ?>
            <?php foreach (array_slice($forms, 0, 8) as $form):
                $ic  = $iconMap[$form['form_type']] ?? ['bg' => '#e2e8f0', 'color' => '#64748b', 'icon' => 'ti-file'];
                $ago = (new DateTime())->diff(new DateTime($form['created_at']));
                $timeStr = $ago->days >= 1
                    ? date('M d', strtotime($form['created_at']))
                    : ($ago->h >= 1 ? $ago->h . 'h ago' : ($ago->i >= 1 ? $ago->i . 'm ago' : 'Just now'));
            ?>
            <a href="/processing-system/public/forms/view/<?= $form['id'] ?>" class="activity-item activity-link">
                <div class="activity-icon activity-icon-dynamic" style="--icon-bg:<?= $ic['bg'] ?>;--icon-color:<?= $ic['color'] ?>">
                    <i class="ti <?= $ic['icon'] ?>"></i>
                </div>
                <div class="activity-text-wrap">
                    <div class="activity-text"><?= htmlspecialchars($formLabel[$form['form_type']] ?? $form['form_type']) ?></div>
                    <div class="activity-sub"><?= htmlspecialchars($form['full_name']) ?></div>
                </div>
                <div class="activity-time">
                    <div class="activity-time"><?= $timeStr ?></div>
                    <span class="badge badge-<?= $badgeMap[$form['status']] ?? 'secondary' ?>">
                        <?= ucfirst(str_replace('_', ' ', $form['status'])) ?>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Right column -->
    <div class="dashboard-cols">

        <!-- Quick new request -->
        <div class="card-panel">
            <div class="card-panel-header">
                <span class="card-panel-title">New Request</span>
            </div>
            <div class="quick-form-grid">
                <?php foreach ($quickForms as $i => $qf): ?>
                <a href="/processing-system/public/forms/<?= $qf['slug'] ?>/create"
                   class="quick-form-btn <?= ($i % 2 === 0) ? 'border-right' : '' ?> <?= ($i < 4) ? 'border-bottom' : '' ?>">
                    <span class="qf-icon" style="--qf-color:<?= $qf['color'] ?>"><i class="ti <?= $qf['icon'] ?>"></i></span>
                    <span class="qf-label"><?= $qf['label'] ?></span>
                    <span class="qf-desc"><?= $qf['desc'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Form volume -->
        <div class="card-panel">
            <div class="card-panel-header">
                <span class="card-panel-title">Form Volume</span>
                <span class="card-panel-link">This period</span>
            </div>
            <?php if (empty($typeCounts)): ?>
                <div class="empty-state empty-state-padded">No data yet.</div>
            <?php else:
                $barColors = ['#0ea5e9','#10b981','#f59e0b','#8b5cf6','#f97316','#ec4899','#0284c7','#ca8a04'];
                $i = 0;
                arsort($typeCounts);
                foreach ($typeCounts as $type => $count):
                    $pct = round(($count / $maxTypeCount) * 100);
            ?>
            <div class="vol-row">
                <span class="vol-label"><?= $formLabel[$type] ?? $type ?></span>
                <div class="vol-bar">
                    <div class="vol-fill" data-color="<?= $i % count($barColors) ?>" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="vol-count"><?= $count ?></span>
            </div>
            <?php $i++; endforeach; endif; ?>
        </div>

    </div>
</div>

<?php
$content   = ob_get_clean();
$pageTitle = 'Dashboard';
require __DIR__ . '/base.php';