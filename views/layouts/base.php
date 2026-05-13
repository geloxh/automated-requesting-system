<?php
    if (!defined('BASE_LOADED')) die('Direct access not allowed');
    $uri = $uri ?? '/';
    $roleLabels = [1 => 'Admin', 2 => 'Approver', 3 => 'Staff'];
    $roleName = $roleLabels[$_SESSION['role_id']] ?? 'User';
    $initials = strtoupper(substr($_SESSION['user_name'], 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Processing System') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/processing-system/public/stylesheets/app.css">
</head>
<body>
<div class="layout">

    <!-- ── SIDEBAR ── -->
    <nav id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="ti ti-bolt"></i></div>
            <div>
                <div class="brand-name">ProFlow</div>
                <div class="brand-tag">SME Processing</div>
            </div>
        </div>

        <div class="sidebar-nav">

            <!-- Dashboard -->
            <a href="/processing-system/public/dashboard"
            class="<?= ($uri === '/dashboard' || $uri === '/') ? 'active' : '' ?>">
                <i class="ti ti-layout-dashboard"></i> Dashboard
            </a>

            <!-- Forms -->
            <div class="sidebar-label">Forms</div>
            <a href="/processing-system/public/forms/advance-payment"
            class="<?= str_contains($uri, 'advance-payment') ? 'active' : '' ?>">
                <i class="ti ti-cash"></i> Advance Payment
            </a>
            <a href="/processing-system/public/forms/overtime"
            class="<?= str_contains($uri, 'overtime') ? 'active' : '' ?>">
                <i class="ti ti-clock-hour-4"></i> Overtime Auth.
            </a>
            <a href="/processing-system/public/forms/request-payment"
            class="<?= str_contains($uri, 'request-payment') ? 'active' : '' ?>">
                <i class="ti ti-receipt"></i> Request for Payment
            </a>
            <a href="/processing-system/public/forms/work-permit"
            class="<?= str_contains($uri, 'work-permit') ? 'active' : '' ?>">
                <i class="ti ti-clipboard-list"></i> Work Permit
            </a>
            <a href="/processing-system/public/forms/leave"
            class="<?= str_contains($uri, '/leave') ? 'active' : '' ?>">
                <i class="ti ti-beach"></i> Leave Application
            </a>
            <a href="/processing-system/public/forms/reimbursement"
            class="<?= str_contains($uri, 'reimbursement') ? 'active' : '' ?>">
                <i class="ti ti-credit-card-refund"></i> Reimbursement
            </a>
            <a href="/processing-system/public/forms/liquidation"
            class="<?= str_contains($uri, 'liquidation') ? 'active' : '' ?>">
                <i class="ti ti-calculator"></i> Liquidation
            </a>
            <a href="/processing-system/public/forms/vehicle-request"
            class="<?= str_contains($uri, 'vehicle-request') ? 'active' : '' ?>">
                <i class="ti ti-car"></i> Vehicle Request
            </a>

            <!-- Approval — visible to roles 1,2,4,5,6 -->
            <?php if ($_SESSION['role_id'] != 3):
                $pendingCount = (function() {
                    if ($_SESSION['role_id'] == 1) {
                        // Admin sees all pending approvals across the system
                        $stmt = db()->prepare(
                            'SELECT COUNT(*) FROM approvals a
                            JOIN forms f ON f.id = a.form_id
                            WHERE a.status = "pending"
                            AND f.status NOT IN ("draft","cancelled","completed","rejected")'
                        );
                        $stmt->execute();
                    } else {
                        // Other approver roles see only their assigned steps
                        $stmt = db()->prepare(
                            'SELECT COUNT(*) FROM approvals a
                            JOIN forms f ON f.id = a.form_id
                            WHERE a.approver_id = ? AND a.status = "pending"
                            AND f.status NOT IN ("draft","cancelled","completed","rejected")'
                        );
                        $stmt->execute([$_SESSION['user_id']]);
                    }
                    return (int) $stmt->fetchColumn();
                })();
            ?>
            <div class="sidebar-label">Approval</div>
            <a href="/processing-system/public/approvals"
            class="<?= $uri === '/approvals' ? 'active' : '' ?>">
                <i class="ti ti-inbox"></i> Approval Inbox
                <?php if ($pendingCount > 0): ?>
                    <span class="badge-count"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
            <a href="/processing-system/public/my-submissions"
            class="<?= $uri === '/my-submissions' ? 'active' : '' ?>">
                <i class="ti ti-send"></i> My Submissions
            </a>
            <?php endif; ?>

            <!-- Records -->
            <div class="sidebar-label">Records</div>
            <a href="/processing-system/public/requests"
            class="<?= $uri === '/requests' ? 'active' : '' ?>">
                <i class="ti ti-file-description"></i> All Requests
            </a>
            <?php if ($_SESSION['role_id'] == 1): ?>
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
            <span class="notif-panel-title">Notifications</span>
            <span class="notif-mark-read" onclick="clearNotifDot()">Mark all read</span>
        </div>
        <div class="notif-item">
            <div class="notif-dot-sm"></div>
            <div>
                <div class="notif-text">Your <strong>Leave Application</strong> is pending approval</div>
                <div class="notif-ago">Just now</div>
            </div>
        </div>
        <div class="notif-item">
            <div class="notif-dot-sm"></div>
            <div>
                <div class="notif-text"><strong>Advance Payment</strong> has been approved</div>
                <div class="notif-ago">1 hour ago</div>
            </div>
        </div>
        <div class="notif-item">
            <div class="notif-dot-sm" style="background:var(--warning)"></div>
            <div>
                <div class="notif-text"><strong>Reimbursement</strong> was returned for revision</div>
                <div class="notif-ago">Yesterday</div>
            </div>
        </div>
    </div>

    <!-- ── MAIN ── -->
    <div class="layout-right">

        <div id="topbar">
            <!-- Mobile hamburger -->
            <button class="icon-btn" id="sidebarToggle" style="display:none">
                <i class="ti ti-menu-2"></i>
            </button>

            <div class="topbar-left">
                <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></span>
                <span class="topbar-date"><?= date('l, F j, Y') ?></span>
            </div>

            <div class="topbar-right">
                <!-- Search -->
                <div class="topbar-search">
                    <i class="ti ti-search"></i>
                    <input type="text" placeholder="Search requests…" id="topbarSearch">
                </div>

                <!-- Notification bell -->
                <button class="icon-btn" id="notifBtn" onclick="toggleNotif()" title="Notifications">
                    <i class="ti ti-bell"></i>
                    <span class="notif-dot" id="notifDot"></span>
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
<script>
    // Notification toggle
    function toggleNotif() {
        document.getElementById('notifPanel').classList.toggle('open');
    }
    function clearNotifDot() {
        document.getElementById('notifDot').style.display = 'none';
        document.querySelectorAll('.notif-dot-sm').forEach(d => d.style.background = '#cbd5e1');
    }
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#notifPanel') && !e.target.closest('#notifBtn')) {
            document.getElementById('notifPanel').classList.remove('open');
        }
    });

    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar       = document.getElementById('sidebar');
    sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#sidebar') && !e.target.closest('#sidebarToggle')) {
            sidebar.classList.remove('open');
        }
    });

    // Show hamburger only on mobile
    function checkMobile() {
        sidebarToggle.style.display = window.innerWidth <= 900 ? 'flex' : 'none';
    }
    checkMobile();
    window.addEventListener('resize', checkMobile);
</script>
</body>
</html>