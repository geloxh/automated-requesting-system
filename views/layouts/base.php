<?php
    if (!defined('BASE_LOADED')) die('Direct access not allowed');
    $uri = $uri ?? '/';
    $roleLabels = [1 => 'Admin', 2 => 'Approver', 3 => 'Staff', 4 => 'Dept. Head', 5 => 'Checker', 6 => 'Final Approver'];
    $roleName = $roleLabels[$_SESSION['role_id']] ?? 'User';
    $initials = strtoupper(substr($_SESSION['user_name'], 0, 2));
    $roleId = (int) $_SESSION['role_id'];
    $userId = (int) $_SESSION['user_id'];

    // ── Pending count — computed once per request, cached in session for 60s ─
    // FIX: previously this was computed twice per page (sidebar + dashboard banner)
    //      and the sidebar version used string-interpolated SQL (SQL-injection risk).
    //      Now: single prepared-statement helper, result cached in session.
    $pendingCount = 0;
    if ($roleId !== 3) {
        $cacheKey = "pending_count_{$userId}";
        $cacheTs = "pending_count_ts_{$userId}";
        $ttl = 60; // seconds

        if (
            isset($_SESSION[$cacheKey], $_SESSION[$cacheTs]) &&
            (time() - $_SESSION[$cacheTs]) < $ttl
        ) {
            $pendingCount = $_SESSION[$cacheKey];
        } else {
            try {
                if ($roleId === 1) {
                    $ps = db()->prepare(
                        'SELECT COUNT(*) FROM approvals a
                         JOIN forms f ON f.id = a.form_id
                         WHERE a.status = "pending"
                         AND f.status NOT IN ("draft","cancelled","completed","rejected")'
                    );
                    $ps->execute();
                } else {
                    $ps = db()->prepare(
                        'SELECT COUNT(*) FROM approvals a
                         JOIN forms f ON f.id = a.form_id
                         WHERE a.approver_id = ? AND a.status = "pending"
                         AND f.status NOT IN ("draft","cancelled","completed","rejected")'
                    );
                    $ps->execute([$userId]);
                }
                $pendingCount = (int) $ps->fetchColumn();
                $_SESSION[$cacheKey] = $pendingCount;
                $_SESSION[$cacheTs]  = time();
            } catch (\Throwable $e) {
                // DB not ready — fail silently
            }
        }
    }

    // ── Notifications ─────────────────────────────────────────────────────────
    $notifItems  = [];
    $notifUnread = 0;
    if ($userId) {
        try {
            $ns = db()->prepare(
                'SELECT id, form_id, type, message, link, is_read, created_at
                 FROM notifications
                 WHERE user_id = ?
                 ORDER BY created_at DESC LIMIT 10'
            );
            $ns->execute([$userId]);
            $notifItems  = $ns->fetchAll(PDO::FETCH_ASSOC);
            $notifUnread = array_reduce($notifItems, fn($c, $r) => $c + (int)!$r['is_read'], 0);
        } catch (\Throwable $e) {
            // migration not run yet — fail silently
        }
    }
    $typeIcon = [
        'success' => ['dot' => '', 'color' => 'var(--success)'],
        'warning' => ['dot' => 'notif-dot-warning', 'color' => 'var(--warning)'],
        'danger' => ['dot' => 'notif-dot-danger',  'color' => 'var(--danger)'],
        'info' => ['dot' => '', 'color' => 'var(--primary)'],
    ];

    // ── Status label map (human-readable badge text) ──────────────────────────
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(($pageTitle ?? 'Dashboard') . ' · SysPro') ?></title>
    <link rel="stylesheet" href="/processing-system/public/stylesheets/app.css">
</head>
<body>
<div class="layout">

    <!-- ── SIDEBAR ── -->
    <nav id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="ti ti-bolt"></i></div>
            <div>
                <div class="brand-name">SysPro</div>
                <div class="brand-tag">Automated Requesting System</div>
            </div>
        </div>

        <div class="sidebar-nav">

            <!-- Dashboard -->
            <a href="/processing-system/public/dashboard"
               class="<?= ($uri === '/dashboard' || $uri === '/') ? 'active' : '' ?>">
                <i class="ti ti-layout-dashboard"></i> Dashboard
            </a>

            <!-- ── Finance Forms ── -->
            <div class="sidebar-label">Finance</div>
            <a href="/processing-system/public/forms/advance-payment"
               class="<?= str_contains($uri, 'advance-payment') ? 'active' : '' ?>">
                <i class="ti ti-cash"></i> Advance Payment
            </a>
            <a href="/processing-system/public/forms/request-payment"
               class="<?= str_contains($uri, 'request-payment') ? 'active' : '' ?>">
                <i class="ti ti-receipt"></i> Request for Payment
            </a>
            <a href="/processing-system/public/forms/reimbursement"
               class="<?= str_contains($uri, 'reimbursement') ? 'active' : '' ?>">
                <i class="ti ti-credit-card-refund"></i> Reimbursement
            </a>
            <a href="/processing-system/public/forms/liquidation"
               class="<?= str_contains($uri, 'liquidation') ? 'active' : '' ?>">
                <i class="ti ti-calculator"></i> Liquidation
            </a>

            <!-- ── Admin / HR Forms ── -->
            <div class="sidebar-label">Admin / HR</div>
            <a href="/processing-system/public/forms/leave"
               class="<?= str_contains($uri, '/leave') ? 'active' : '' ?>">
                <i class="ti ti-beach"></i> Leave Application
            </a>
            <a href="/processing-system/public/forms/overtime"
               class="<?= str_contains($uri, 'overtime') ? 'active' : '' ?>">
                <i class="ti ti-clock-hour-4"></i> Overtime Auth.
            </a>
            <a href="/processing-system/public/forms/vehicle-request"
               class="<?= str_contains($uri, 'vehicle-request') ? 'active' : '' ?>">
                <i class="ti ti-car"></i> Vehicle Request
            </a>

            <!-- ── Approval ── -->
            <div class="sidebar-label">Approval</div>

            <?php if ($roleId !== 3): ?>
            <a href="/processing-system/public/approvals"
               class="<?= $uri === '/approvals' ? 'active' : '' ?>">
                <i class="ti ti-inbox"></i> Approval Inbox
                <?php if ($pendingCount > 0): ?>
                    <span class="badge-count <?= $pendingCount > 0 ? 'badge-count--pulse' : '' ?>"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>

            <a href="/processing-system/public/my-submissions"
               class="<?= $uri === '/my-submissions' ? 'active' : '' ?>">
                <i class="ti ti-send"></i> My Submissions
            </a>

            <!-- ── Records ── -->
            <div class="sidebar-label">Records</div>
            <a href="/processing-system/public/requests"
               class="<?= $uri === '/requests' ? 'active' : '' ?>">
                <i class="ti ti-file-description"></i> All Requests
            </a>
            <?php if ($roleId === 1): ?>
            <a href="/processing-system/public/employees"
               class="<?= $uri === '/employees' ? 'active' : '' ?>">
                <i class="ti ti-users"></i> Employees
            </a>
            <?php endif; ?>

        </div>

        <div class="sidebar-footer">
            <a href="/processing-system/public/profile" class="user-card">
                <div class="user-avatar"><?= $initials ?></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <div class="user-role"><?= $roleName ?> · <?= htmlspecialchars($_SESSION['department'] ?? '') ?></div>
                </div>
                <i class="ti ti-dots-vertical"></i>
            </a>
        </div>
    </nav>

    <!-- ── NOTIFICATION PANEL ── -->
    <div class="notif-panel" id="notifPanel">
        <div class="notif-panel-header">
            <span class="notif-panel-title">
                Notifications
                <?php if ($notifUnread > 0): ?>
                    <span class="badge badge-danger notif-count-badge"><?= $notifUnread ?></span>
                <?php endif; ?>
            </span>
            <span class="notif-mark-read" id="markAllReadBtn">Mark all read</span>
        </div>

        <div id="notifList">
        <?php if (empty($notifItems)): ?>
            <div class="notif-empty">
                <i class="ti ti-bell-off empty-state-icon"></i>
                No notifications yet.
            </div>
        <?php else: ?>
            <?php foreach ($notifItems as $n):
                $tc = $typeIcon[$n['type']] ?? $typeIcon['info'];
                $ago = (new DateTime())->diff(new DateTime($n['created_at']));
                $agoStr  = $ago->days > 0 ? $ago->days . 'd ago'
                         : ($ago->h > 0 ? $ago->h . 'h ago'
                         : ($ago->i > 0 ? $ago->i . 'm ago' : 'Just now'));
                $isUnread = !(bool)$n['is_read'];
            ?>
            <div class="notif-item <?= $isUnread ? 'notif-item--unread' : '' ?>"
                 data-notif-id="<?= $n['id'] ?>"
                 <?= $n['link'] ? 'data-href="' . htmlspecialchars($n['link']) . '"' : '' ?>>
                <div class="notif-dot-sm <?= $tc['dot'] ?>"></div>
                <div>
                    <div class="notif-text"><?= htmlspecialchars($n['message']) ?></div>
                    <div class="notif-ago"><?= $agoStr ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>

    <!-- ── MAIN ── -->
    <div class="layout-right">

        <div id="topbar">
            <!-- Mobile hamburger -->
            <button class="icon-btn" id="sidebarToggle">
                <i class="ti ti-menu-2"></i>
            </button>

            <div class="topbar-left">
                <!-- Breadcrumb (optional — set $breadcrumbs in the calling view) -->
                <?php if (!empty($breadcrumbs)): ?>
                    <nav class="topbar-breadcrumb" aria-label="Breadcrumb">
                        <?php foreach ($breadcrumbs as $idx => $crumb): ?>
                            <?php if ($idx > 0): ?><span class="bc-sep"><i class="ti ti-chevron-right"></i></span><?php endif; ?>
                            <?php if (!empty($crumb['url'])): ?>
                                <a href="<?= htmlspecialchars($crumb['url']) ?>" class="bc-link"><?= htmlspecialchars($crumb['label']) ?></a>
                            <?php else: ?>
                                <span class="bc-current"><?= htmlspecialchars($crumb['label']) ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>
                <?php else: ?>
                    <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></span>
                <?php endif; ?>
                <span class="topbar-date"><?= date('l, F j, Y') ?></span>
            </div>

            <div class="topbar-right">
                <!-- Search -->
                <div class="topbar-search">
                    <i class="ti ti-search"></i>
                    <input type="text" placeholder="Search requests…" id="topbarSearch">
                </div>

                <!-- Notification bell -->
                <button class="icon-btn" id="notifBtn" title="Notifications">
                    <i class="ti ti-bell"></i>
                    <span class="notif-dot <?= $notifUnread === 0 ? 'notif-dot--hidden' : '' ?>" id="notifDot"></span>
                </button>

                <!-- New Request -->
                <a href="/processing-system/public/forms/advance-payment/create" class="btn-new-req">
                    <i class="ti ti-plus"></i> New Request
                </a>

                <!-- Logout -->
                <form method="POST" action="/processing-system/public/logout">
                    <?= \App\Helpers\Csrf::field() ?>
                    <button class="icon-btn" title="Logout">
                        <i class="ti ti-logout"></i>
                    </button>
                </form>
            </div>
        </div>

        <div id="main"><?= $content ?></div>
    </div>

</div>

<script src="/processing-system/public/scripts/form_table.js"></script>
<script src="/processing-system/public/scripts/table-filter.js"></script>
<script src="/processing-system/public/scripts/app.js"></script>
</body>
</html>
