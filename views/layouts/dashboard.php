<?php
    define('BASE_LOADED', true);
    use App\Middleware\AuthMiddleware;
    AuthMiddleware::require();

    $roleId = (int) $_SESSION['role_id'];
    $userId = (int) $_SESSION['user_id'];

    // ── Recent activity query (last 30 days, role-scoped) ───────────────────
    if ($roleId === 1) {
        $stmt = db()->prepare(
            'SELECT f.id, f.form_type, f.status, e.full_name, f.created_at
            FROM forms f JOIN employees e ON e.id = f.submitted_by
            WHERE f.status NOT IN ("draft", "cancelled")
            AND f.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY f.created_at DESC LIMIT 50'
        );
        $stmt->execute();
    } elseif (in_array($roleId, [2, 4, 5, 6], true)) {
        $stmt = db()->prepare(
            'SELECT DISTINCT f.id, f.form_type, f.status, e.full_name, f.created_at
            FROM forms f JOIN employees e ON e.id = f.submitted_by
            JOIN approvals a ON a.form_id = f.id
            WHERE (a.approver_id = ? OR f.submitted_by = ?)
            AND f.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY f.created_at DESC'
        );
        $stmt->execute([$userId, $userId]);
    } else {
        $stmt = db()->prepare(
            'SELECT f.id, f.form_type, f.status, f.created_at, e.full_name
            FROM forms f JOIN employees e ON e.id = f.submitted_by
            WHERE f.submitted_by = ?
            AND f.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY f.created_at DESC LIMIT 30'
        );
        $stmt->execute([$userId]);
    }

    $forms = $stmt->fetchAll();
    $formLabel = \App\Helpers\FormLabels::all();
    $badgeMap = \App\Helpers\FormLabels::allBadges();

    ob_start();

    // ── Status → human label map (used in activity feed badges) ────────────
    // FIX: was using ucfirst(str_replace('_',' ',$status)) which produced
    //      "Checker approved" instead of a meaningful label.
    $statusLabels = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'checker_approved' => 'With Checker',
        'process_approved' => 'Processing',
        'department_reviewed' => 'Dept. Review',
        'finance_reviewed' => 'Finance Review',
        'final_approved' => 'Final Approved',
        'completed' => 'Completed',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    // ── KPI bucket mapping ──────────────────────────────────────────────────
    // FIX: original $inApprovalStatuses omitted finance pipeline statuses
    //      (process_approved, finance_reviewed), so finance forms were not
    //      counted in "In Approval".
    $inApprovalStatuses = [
        'submitted',
        'checker_approved',
        'process_approved',
        'department_reviewed',
        'finance_reviewed',
    ];
    $approvedStatuses = ['final_approved', 'completed'];

    $counts = ['draft' => 0, 'in_approval' => 0, 'approved' => 0, 'rejected' => 0];
    foreach ($forms as $f) {
        $s = $f['status'];
        if ($s === 'draft') {
            $counts['draft']++;
        } elseif (in_array($s, $inApprovalStatuses, true)) {
            $counts['in_approval']++;
        } elseif (in_array($s, $approvedStatuses, true)) {
            $counts['approved']++;
        } elseif ($s === 'rejected') {
            $counts['rejected']++;
        }
    }

    // ── Icon + fixed colour map (keyed by form_type) ───────────────────────
    $iconMap = [
        'advance_payment' => ['bg' => '#d1fae5', 'color' => '#10b981', 'icon' => 'ti-cash', 'barColor' => '#10b981'],
        'overtime_authorization' => ['bg' => '#ede9fe', 'color' => '#8b5cf6', 'icon' => 'ti-clock-hour-4', 'barColor' => '#8b5cf6'],
        'request_for_payment' => ['bg' => '#fce7f3', 'color' => '#ec4899', 'icon' => 'ti-receipt', 'barColor' => '#ec4899'],
        'leave_application' => ['bg' => '#dbeafe', 'color' => '#0ea5e9', 'icon' => 'ti-beach', 'barColor' => '#0ea5e9'],
        'reimbursement' => ['bg' => '#ffedd5', 'color' => '#f97316', 'icon' => 'ti-credit-card-refund', 'barColor' => '#f97316'],
        'liquidation' => ['bg' => '#e0f2fe', 'color' => '#0284c7', 'icon' => 'ti-calculator', 'barColor' => '#0284c7'],
        'vehicle_request' => ['bg' => '#fef9c3', 'color' => '#ca8a04', 'icon' => 'ti-car', 'barColor' => '#ca8a04'],
    ];

    // ── Form volume (FIX: use type-fixed colours, not index-cycling array) ─
    $typeCounts   = [];
    foreach ($forms as $f) {
        $typeCounts[$f['form_type']] = ($typeCounts[$f['form_type']] ?? 0) + 1;
    }
    $maxTypeCount = max(array_values($typeCounts) ?: [1]);
    arsort($typeCounts);

    $quickForms = [
        ['slug' => 'advance-payment', 'label' => 'Advance', 'desc' => 'Cash advance', 'color' => '#10b981', 'icon' => 'ti-cash'],
        ['slug' => 'overtime', 'label' => 'Overtime', 'desc' => 'OT authorization','color' => '#8b5cf6', 'icon' => 'ti-clock-hour-4'],
        ['slug' => 'request-payment', 'label' => 'Payment', 'desc' => 'Request payment', 'color' => '#ec4899', 'icon' => 'ti-receipt'],
        ['slug' => 'leave', 'label' => 'Leave', 'desc' => 'File absence', 'color' => '#0ea5e9', 'icon' => 'ti-beach'],
        ['slug' => 'reimbursement', 'label' => 'Reimburse', 'desc' => 'Claim expenses', 'color' => '#f97316', 'icon' => 'ti-credit-card-refund'],
        ['slug' => 'liquidation', 'label' => 'Liquidation','desc' => 'Clear advance', 'color' => '#0284c7', 'icon' => 'ti-calculator'],
        ['slug' => 'vehicle-request', 'label' => 'Vehicle', 'desc' => 'Reserve vehicle', 'color' => '#ca8a04', 'icon' => 'ti-car'],
    ];

    // ── Pending alert — reuse the session-cached count from base.php ───────
    // FIX: original code ran a second raw string-interpolated SQL query here.
    //      base.php now caches $pendingCount in session; it is available via
    //      $_SESSION["pending_count_{$userId}"] but we just let base.php inject
    //      it.  For the dashboard alert we re-read the session cache directly.
    $dashPending = 0;
    if (in_array($roleId, [2, 4, 5, 6], true)) {
        $cacheKey    = "pending_count_{$userId}";
        $dashPending = (int) ($_SESSION[$cacheKey] ?? 0);
    }
?>

<!-- ── Page heading ── -->
<div class="page-heading">Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?> 👋</div>
<div class="page-subheading"><?= date('l, F j, Y') ?> — here's your current activity.</div>

<?php if ($dashPending > 0): ?>
    <a href="/processing-system/public/approvals" class="dash-alert">
        <i class="ti ti-bell-ringing"></i>
        <span>You have <strong><?= $dashPending ?> pending approval<?= $dashPending > 1 ? 's' : '' ?></strong> waiting for your action.</span>
        <span class="dash-alert-cta">Go to Inbox <i class="ti ti-arrow-right"></i></span>
    </a>
<?php endif; ?>

<!-- ── KPI Cards (last 30 days) ── -->
<!-- FIX: previously only "In Approval" was a link; all four are now clickable -->
<div class="kpi-grid">
    <a href="/processing-system/public/my-submissions?status=in_approval" class="kpi-card blue kpi-card--link">
        <div class="kpi-icon blue"><i class="ti ti-hourglass"></i></div>
        <div class="kpi-label">In Approval</div>
        <div class="kpi-value"><?= $counts['in_approval'] ?></div>
        <div class="kpi-delta kpi-delta--period">Last 30 days · Submitted &amp; pending</div>
    </a>
    <a href="/processing-system/public/my-submissions?status=approved" class="kpi-card green kpi-card--link">
        <div class="kpi-icon green"><i class="ti ti-circle-check"></i></div>
        <div class="kpi-label">Approved</div>
        <div class="kpi-value"><?= $counts['approved'] ?></div>
        <div class="kpi-delta kpi-delta--period">Last 30 days · Final approved &amp; completed</div>
    </a>
    <a href="/processing-system/public/my-submissions?status=draft" class="kpi-card amber kpi-card--link">
        <div class="kpi-icon amber"><i class="ti ti-file-pencil"></i></div>
        <div class="kpi-label">Drafts</div>
        <div class="kpi-value"><?= $counts['draft'] ?></div>
        <div class="kpi-delta kpi-delta--period">Last 30 days · Not yet submitted</div>
    </a>
    <a href="/processing-system/public/my-submissions?status=rejected" class="kpi-card purple kpi-card--link">
        <div class="kpi-icon purple"><i class="ti ti-circle-x"></i></div>
        <div class="kpi-label">Rejected</div>
        <div class="kpi-value"><?= $counts['rejected'] ?></div>
        <div class="kpi-delta kpi-delta--period">Last 30 days · Needs resubmission</div>
    </a>
</div>

<!-- ── Section row: Activity + Right column ── -->
<div class="section-row">

    <!-- Activity feed -->
    <div class="card-panel">
        <div class="card-panel-header">
            <span class="card-panel-title">Recent Activity</span>
            <?php $allLink = ($roleId === 1)
                ? '/processing-system/public/requests'
                : (in_array($roleId, [2, 4, 5, 6], true)
                    ? '/processing-system/public/approvals'
                    : '/processing-system/public/my-submissions');
            ?>
            <a href="<?= $allLink ?>" class="card-panel-link">View all →</a>
        </div>
        <?php if (empty($forms)): ?>
            <div class="empty-state">
                <i class="ti ti-inbox empty-state-icon"></i>
                No activity yet.
            </div>
        <?php else: ?>
            <?php foreach (array_slice($forms, 0, 8) as $form):
                $ic = $iconMap[$form['form_type']] ?? ['bg' => '#e2e8f0', 'color' => '#64748b', 'icon' => 'ti-file'];
                $ago = (new DateTime())->diff(new DateTime($form['created_at']));
                $timeStr = $ago->days >= 1
                    ? date('M d', strtotime($form['created_at']))
                    : ($ago->h >= 1 ? $ago->h . 'h ago' : ($ago->i >= 1 ? $ago->i . 'm ago' : 'Just now'));
                // FIX: use human label map instead of ucfirst(str_replace)
                $humanStatus = $statusLabels[$form['status']] ?? ucwords(str_replace('_', ' ', $form['status']));
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
                    <div><?= $timeStr ?></div>
                    <span class="badge badge-<?= $badgeMap[$form['status']] ?? 'secondary' ?>">
                        <?= htmlspecialchars($humanStatus) ?>
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
                   class="quick-form-btn <?= ($i % 2 === 0) ? 'border-right' : '' ?> <?= ($i < count($quickForms) - 2) ? 'border-bottom' : '' ?>">
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
                <span class="card-panel-link" style="font-size:11px;color:var(--text-muted)">Last 30 days</span>
            </div>
            <?php if (empty($typeCounts)): ?>
                <div class="empty-state empty-state-padded">No data yet.</div>
            <?php else:
                foreach ($typeCounts as $type => $count):
                    $pct      = round(($count / $maxTypeCount) * 100);
                    // FIX: use the fixed colour from $iconMap, not an index-cycling array
                    $barColor = $iconMap[$type]['barColor'] ?? '#94a3b8';
            ?>
            <div class="vol-row">
                <span class="vol-label"><?= $formLabel[$type] ?? $type ?></span>
                <div class="vol-bar">
                    <div class="vol-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>"></div>
                </div>
                <span class="vol-count"><?= $count ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>

    </div>
</div>

<?php
$content   = ob_get_clean();
$pageTitle = 'Dashboard';
require __DIR__ . '/base.php';