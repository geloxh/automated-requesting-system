<?php 
    if (!defined('BASE_LOADED')) die('Direct access not allowed'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Processing System') ?></title>
    <link rel="stylesheet" href="/processing-system/public/stylesheets/app.css">
</head>
<body>
<div class="layout">

    <div id="sidebar">
        <div class="sidebar-brand">⚙ Processing System</div>
        <div class="sidebar-nav">
            <a href="/processing-system/public/dashboard"
               class="<?= ($uri === '/dashboard' || $uri === '/') ? 'active' : '' ?>">
                ▦ Dashboard
            </a>
            <?php if ($_SESSION['role_id'] == 1): ?>
                <div class="sidebar-label">Admin</div>
                <a href="/processing-system/public/employees">👥 Employees</a>
            <?php endif; ?>
            <div class="sidebar-label">Forms</div>
            <a href="/processing-system/public/forms/advance-payment">💵 Advance Payment</a>
            <a href="/processing-system/public/forms/overtime">🕐 Overtime Auth.</a>
            <a href="/processing-system/public/forms/request-payment">🧾 Request for Payment</a>
            <a href="/processing-system/public/forms/work-permit">🛡 Work Permit</a>
            <a href="/processing-system/public/forms/leave">📅 Leave Application</a>
            <a href="/processing-system/public/forms/reimbursement">👛 Reimbursement</a>
            <a href="/processing-system/public/forms/liquidation">📋 Liquidation</a>
            <a href="/processing-system/public/forms/vehicle-request">🚛 Vehicle Request</a>
        </div>
    </div>

    <div class="layout-right">
        <div id="topbar">
            <div class="topbar-left">
                <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></span>
                <span class="topbar-date"><?= date('l, F j, Y') ?></span>
            </div>
            <div class="topbar-right">
                <a href="/processing-system/public/profile" class="topbar-user">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </a>
                <form method="POST" action="/processing-system/public/logout">
                    <button class="btn btn-ghost btn-sm">Logout</button>
                </form>
            </div>
        </div>
        <div id="main"><?= $content ?></div>
    </div>

</div>
<script src="/processing-system/public/scripts/form_table.js"></script>
</body>
</html>