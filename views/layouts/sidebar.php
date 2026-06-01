<?php if (!defined('BASE_LOADED')) die('Direct access not allowed'); ?>

<nav id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="ti ti-bolt"></i></div>
        <div>
            <div class="brand-name">SysPro</div>
            <div class="brand-tag">Automated Requesting System</div>
        </div>
    </div>

    <div class="sidebar-nav">

        <a href="/processing-system/public/dashboard"
           class="<?= ($uri === '/dashboard' || $uri === '/') ? 'active' : '' ?>">
            <i class="ti ti-layout-dashboard"></i> Dashboard
        </a>

        <div class="sidebar-group" data-group="finance">
            <div class="sidebar-label sidebar-label--toggle" data-toggle="finance">
                <span>Finance</span>
                <i class="ti ti-chevron-down sidebar-chevron"></i>
            </div>
            <div class="sidebar-group-links" id="group-finance">
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
            </div>
        </div>

        <div class="sidebar-group" data-group="admin">
            <div class="sidebar-label sidebar-label--toggle" data-toggle="admin">
                <span>Admin / HR</span>
                <i class="ti ti-chevron-down sidebar-chevron"></i>
            </div>
            <div class="sidebar-group-links" id="group-admin">
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
            </div>
        </div>

        <div class="sidebar-group" data-group="approval">
            <div class="sidebar-label sidebar-label--toggle" data-toggle="approval">
                <span>Approval</span>
                <i class="ti ti-chevron-down sidebar-chevron"></i>
            </div>
            <div class="sidebar-group-links" id="group-approval">
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
            </div>
        </div>

        <div class="sidebar-group" data-group="records">
            <div class="sidebar-label sidebar-label--toggle" data-toggle="records">
                <span>Records</span>
                <i class="ti ti-chevron-down sidebar-chevron"></i>
            </div>
            <div class="sidebar-group-links" id="group-records">
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
        </div>

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