<?php if (!defined('BASE_LOADED')) die('Direct access not allowed'); ?>

<nav id="sidebar" class="<?= ($GLOBALS['sidebar_collapsed'] ?? false) ? 'collapsed' : '' ?>">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="ti ti-bolt"></i></div>
        <div class="brand-text">
            <div class="brand-name">ARS</div>
            <div class="brand-tag">Automated Requesting System</div>
        </div>
        <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle sidebar">
            <i class="ti ti-layout-sidebar-left-collapse" id="sidebarToggleIcon"></i>
        </button>
    </div>

    <div class="sidebar-nav">

        <a href="/processing-system/public/dashboard"
           data-tooltip="Dashboard"
           class="<?= ($uri === '/dashboard' || $uri === '/') ? 'active' : '' ?>">
            <i class="ti ti-layout-dashboard"></i> <span>Dashboard</span>
        </a>

        <div class="sidebar-group" data-group="finance">
            <div class="sidebar-label sidebar-label--toggle" data-toggle="finance">
                <i class="ti ti-coin"></i>
                <span>Finance</span>
                <i class="ti ti-chevron-down sidebar-chevron"></i>
            </div>
            <div class="sidebar-group-links" id="group-finance">
                <a href="/processing-system/public/forms/advance-payment"
                   data-tooltip="Advance Payment"
                   class="<?= str_contains($uri, 'advance-payment') ? 'active' : '' ?>">
                    <i class="ti ti-cash"></i> <span>Advance Payment</span>
                </a>
                <a href="/processing-system/public/forms/request-payment"
                   data-tooltip="Request for Payment"
                   class="<?= str_contains($uri, 'request-payment') ? 'active' : '' ?>">
                    <i class="ti ti-receipt"></i> <span>Request for Payment</span>
                </a>
                <a href="/processing-system/public/forms/reimbursement"
                   data-tooltip="Reimbursement"
                   class="<?= str_contains($uri, 'reimbursement') ? 'active' : '' ?>">
                    <i class="ti ti-credit-card-refund"></i> <span>Reimbursement</span>
                </a>
                <a href="/processing-system/public/forms/liquidation"
                   data-tooltip="Liquidation"
                   class="<?= str_contains($uri, 'liquidation') ? 'active' : '' ?>">
                    <i class="ti ti-calculator"></i> <span>Liquidation</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group" data-group="admin">
            <div class="sidebar-label sidebar-label--toggle" data-toggle="admin">
                <i class="ti ti-building"></i>
                <span>Admin / HR</span>
                <i class="ti ti-chevron-down sidebar-chevron"></i>
            </div>
            <div class="sidebar-group-links" id="group-admin">
                <a href="/processing-system/public/forms/leave"
                   data-tooltip="Leave Application"
                   class="<?= str_contains($uri, '/leave') ? 'active' : '' ?>">
                    <i class="ti ti-beach"></i> <span>Leave Application</span>
                </a>
                <a href="/processing-system/public/forms/overtime"
                   data-tooltip="Overtime Auth."
                   class="<?= str_contains($uri, 'overtime') ? 'active' : '' ?>">
                    <i class="ti ti-clock-hour-4"></i> <span>Overtime Auth.</span>
                </a>
                <a href="/processing-system/public/forms/vehicle-request"
                   data-tooltip="Vehicle Request"
                   class="<?= str_contains($uri, 'vehicle-request') ? 'active' : '' ?>">
                    <i class="ti ti-car"></i> <span>Vehicle Request</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group" data-group="approval">
            <div class="sidebar-label sidebar-label--toggle" data-toggle="approval">
                <i class="ti ti-checks"></i>
                <span>Approval</span>
                <i class="ti ti-chevron-down sidebar-chevron"></i>
            </div>
            <div class="sidebar-group-links" id="group-approval">
                <?php if ($roleId !== 3): ?>
                <a href="/processing-system/public/approvals"
                   data-tooltip="Approval Inbox"
                   class="<?= $uri === '/approvals' ? 'active' : '' ?>">
                    <i class="ti ti-inbox"></i> <span>Approval Inbox</span>
                    <?php if ($pendingCount > 0): ?>
                        <span class="badge-count <?= $pendingCount > 0 ? 'badge-count--pulse' : '' ?>"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
                <a href="/processing-system/public/my-submissions"
                   data-tooltip="My Submissions"
                   class="<?= $uri === '/my-submissions' ? 'active' : '' ?>">
                    <i class="ti ti-send"></i> <span>My Submissions</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group" data-group="records">
            <div class="sidebar-label sidebar-label--toggle" data-toggle="records">
                <i class="ti ti-archive"></i>
                <span>Records</span>
                <i class="ti ti-chevron-down sidebar-chevron"></i>
            </div>
            <div class="sidebar-group-links" id="group-records">
                <a href="/processing-system/public/requests"
                   data-tooltip="All Requests"
                   class="<?= $uri === '/requests' ? 'active' : '' ?>">
                    <i class="ti ti-file-description"></i> <span>All Requests</span>
                </a>
                <?php if ($roleId === 1): ?>
                <a href="/processing-system/public/employees"
                    data-tooltip="Employees"
                    class="<?= $uri === '/employees' ? 'active' : '' ?>">
                    <i class="ti ti-users"></i> <span>Employees</span>
                </a>
                <a href="/processing-system/public/departments"
                    data-tooltip="Departments"
                    class="<?= $uri === '/departments' ? 'active' : '' ?>">
                    <i class="ti ti-building-community"></i> <span>Departments</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="sidebar-footer">
        <a href="/processing-system/public/settings"
           data-tooltip="Settings"
           class="sidebar-settings-link <?= $uri === '/settings' ? 'active' : '' ?>">
            <i class="ti ti-settings"></i> <span>Settings</span>
        </a>
        <a href="/processing-system/public/profile" class="user-card" data-tooltip="Profile">
            <div class="user-avatar"><?= $initials ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                <div class="user-role"><?= $roleName ?> · <?= htmlspecialchars($_SESSION['department'] ?? '') ?></div>
            </div>
            <i class="ti ti-dots-vertical"></i>
        </a>
    </div>
</nav>