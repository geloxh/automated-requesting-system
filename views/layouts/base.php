<?php 
    if (!defined('BASE_LOADED')) die('Direct access not allowed'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Processing System') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #sidebar { width: 220px; min-height: 100vh; background: #212529; }
        #sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 10px 16px; }
        #sidebar a:hover, #sidebar a.active { background: #343a40; color: #fff; }
        #main { flex: 1; background: #f8f9fa; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0">Processing System</span>
    <div class="d-flex align-items-center gap-3">
        <span class="text-white-50 small"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <form method="POST" action="/processing-system/public/logout" class="m-0">
            <button class="btn btn-sm btn-outline-light">Logout</button>
        </form>
    </div>
</nav>

<!-- Body -->
<div class="d-flex">

    <!-- Sidebar -->
    <div id="sidebar">
        <a href="/processing-system/public/dashboard" class="<?= ($uri === '/dashboard' || $uri === '/') ? 'active' : '' ?>">Dashboard</a>

        <?php if ($_SESSION['role_id'] == 1): // admin ?>
            <div class="text-uppercase text-secondary small px-3 pt-3" style="font-size:.7rem">Admin</div>
            <a href="/processing-system/public/employees">Employees</a>
        <?php endif; ?>

        <div class="text-uppercase text-secondary small px-3 pt-3" style="font-size:.7rem">Forms</div>
        <a href="/processing-system/public/forms/advance-payment">Advance Payment</a>
        <a href="/processing-system/public/forms/overtime">Overtime Authorization</a>
        <a href="/processing-system/public/forms/request-payment">Request for Payment</a>
        <a href="/processing-system/public/forms/work-permit">Work Permit</a>
        <a href="/processing-system/public/forms/leave">Leave Application</a>
        <a href="/processing-system/public/forms/reimbursement">Reimbursement</a>
        <a href="/processing-system/public/forms/liquidation">Liquidation</a>
        <a href="/processing-system/public/forms/vehicle-request">Vehicle Request</a>
    </div>

    <!-- Main Content -->
    <div id="main" class="p-4 w-100">
        <?= $content ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
