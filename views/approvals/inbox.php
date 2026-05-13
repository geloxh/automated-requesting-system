<div class="page-heading">Approval Inbox</div>
<div class="page-subheading">Review and act on requests routed to you for approval.</div>

<?php
$formLabel = $formLabel ?? [];
$badgeMap = [
    'draft' => 'secondary',
    'submitted' => 'primary',
    'in_approval' => 'warning',
    'approved' => 'success',
    'rejected' => 'danger',
    'cancelled' => 'dark',
];
$iconMap = [
    'advance_payment' => ['bg' => '#d1fae5', 'color' => '#10b981', 'icon' => 'ti-cash'],
    'overtime_authorization' => ['bg' => '#ede9fe', 'color' => '#8b5cf6', 'icon' => 'ti-clock-hour-4'],
    'request_for_payment' => ['bg' => '#fce7f3', 'color' => '#ec4899', 'icon' => 'ti-receipt'],
    'work_permit' => ['bg' => '#fef3c7', 'color' => '#f59e0b', 'icon' => 'ti-clipboard-list'],
    'leave_application' => ['bg' => '#dbeafe', 'color' => '#0ea5e9', 'icon' => 'ti-beach'],
    'reimbursement'  => ['bg' => '#ffedd5', 'color' => '#f97316', 'icon' => 'ti-credit-card-refund'],
    'liquidation' => ['bg' => '#e0f2fe', 'color' => '#0284c7', 'icon' => 'ti-calculator'],
    'vehicle_request' => ['bg' => '#fef9c3', 'color' => '#ca8a04', 'icon' => 'ti-car'],
];
$stepLabel = [
    2 => 'Supervisor Review',
    3 => 'Department Check',
    4 => 'Checker Approval',
    5 => 'Final Approval',
    6 => 'Completion',
];
?>

<?php if (empty($approvals)): ?>
    <div class="empty-state">
        <i class="ti ti-inbox" style="font-size:2.5rem;color:var(--border);display:block;margin-bottom:.5rem"></i>
        No pending approvals. You're all caught up!
    </div>
<?php else: ?>

<div class="table-wrap">
    <div class="table-wrap-header" style="display:flex;align-items:center;justify-content:space-between">
        <span>Pending Actions</span>
        <span class="badge badge-warning"><?= count($approvals) ?> item<?= count($approvals) !== 1 ? 's' : '' ?></span>
    </div>
    <table>
        <thead>
            <tr>
                <th class="th-first">Form</th>
                <th>Submitted By</th>
                <th>Department</th>
                <th>Stage</th>
                <th>Date Filed</th>
                <th>Waiting</th>
                <th class="td-last"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($approvals as $row):
            $ic  = $iconMap[$row['form_type']] ?? ['bg' => '#e2e8f0', 'color' => '#64748b', 'icon' => 'ti-file'];
            $ago = (new DateTime())->diff(new DateTime($row['created_at']));
            $waitStr = $ago->days > 0 ? $ago->days . 'd ago' : ($ago->h > 0 ? $ago->h . 'h ago' : 'Just now');
            $isOverdue = $ago->days >= 3;
        ?>
            <tr>
                <td class="td-first">
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="activity-icon" style="background:<?= $ic['bg'] ?>;color:<?= $ic['color'] ?>;width:32px;height:32px;font-size:15px">
                            <i class="ti <?= $ic['icon'] ?>"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:13.5px"><?= $formLabel[$row['form_type']] ?? $row['form_type'] ?></div>
                            <div style="font-size:11.5px;color:var(--text-muted)">#<?= $row['id'] ?></div>
                        </div>
                    </div>
                </td>
                <td><?= htmlspecialchars($row['owner_name']) ?></td>
                <td class="muted"><?= htmlspecialchars($row['department'] ?? '—') ?></td>
                <td>
                    <span class="badge badge-primary"><?= $stepLabel[$row['sequence']] ?? 'Step ' . $row['sequence'] ?></span>
                </td>
                <td class="muted"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                <td>
                    <span style="font-size:12px;font-weight:600;color:<?= $isOverdue ? 'var(--danger)' : 'var(--text-muted)' ?>">
                        <?php if ($isOverdue): ?><i class="ti ti-alert-triangle" style="font-size:13px"></i> <?php endif; ?>
                        <?= $waitStr ?>
                    </span>
                </td>
                <td class="td-last text-end">
                    <a href="/processing-system/public/forms/view/<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                        Review
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
