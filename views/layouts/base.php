<?php
    if (!defined('BASE_LOADED')) die('Direct access not allowed');
    $uri = $uri ?? '/';
    $roleLabels = [1 => 'Admin', 2 => 'Approver', 3 => 'Staff'];
    $roleName   = $roleLabels[$_SESSION['role_id']] ?? 'User';
    $initials   = strtoupper(substr($_SESSION['user_name'], 0, 2));
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

    <nav id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="ti ti-bolt"></i></div>
            <div>
                <div class="brand-name">ProFlow</div>
                <div class="brand-tag">SME Processing</div>
            </div>
        </div>

        <div class="sidebar-nav">
            <a href="/processing-system/public/dashboard"
               class="<?= ($uri === '/dashboard' || $uri === '/') ? 'active' : '' ?>">
                <i class="ti ti-layout-dashboard"></i> Dashboard
            </a>

            <?php if ($_SESSION['role_id'] == 1): ?>
                <div class="sidebar-label">Admin</div>
                <a href="/processing-system/public/employees"
                   class="<?= $uri === '/employees' ? 'active' : '' ?>">
                    <i class="ti ti-users"></i> Employees
                </a>
            <?php endif; ?>

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
        </div>

        <div class="sidebar-footer">
            <a href="/processing-system/public/profile" class="user-card" style="text-decoration:none">
                <div class="user-avatar"><?= $initials ?></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <div class="user-role"><?= $roleName ?></div>
                </div>
                <i class="ti ti-chevron-right"></i>
            </a>
        </div>
    </nav>

    <div class="layout-right">
        <div id="topbar">
            <div class="topbar-left">
                <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></span>
                <span class="topbar-date"><?= date('l, F j, Y') ?></span>
            </div>
            <div class="topbar-right">
                <form method="POST" action="/processing-system/public/logout">
                    <?= \App\Helpers\Csrf::field() ?>
                    <button class="btn btn-ghost btn-sm">
                        <i class="ti ti-logout"></i> Logout
                    </button>
                </form>
            </div>
        </div>
        <div id="main"><?= $content ?></div>
    </div>

</div>
<script src="/processing-system/public/scripts/form_table.js"></script>
</body>
</html>