<?php
    $formLabel = [
        'advance_payment'        => 'Advance Payment',
        'overtime_authorization' => 'Overtime Authorization',
        'request_for_payment'    => 'Request for Payment',
        'work_permit'            => 'Work Permit',
        'leave_application'      => 'Leave Application',
        'reimbursement'          => 'Reimbursement',
        'liquidation'            => 'Liquidation',
        'vehicle_request'        => 'Vehicle Request',
    ];

    $statusBadge = [
        'draft'       => 'secondary',
        'submitted'   => 'primary',
        'in_approval' => 'warning',
        'approved'    => 'success',
        'rejected'    => 'danger',
        'cancelled'   => 'dark',
    ];

    $roleId = $_SESSION['role_id'];
    $title = $formLabel[$formType] ?? $formType;

    ob_start(); 
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><?= htmlspecialchars($title) ?></h5>
    <?php if ($roleId != 2): // approvers dont' submit ?>
        <a href="/processing-system/public/forms/<?= $slug ?>/create" class="btn btn-primary btn-sm">+ New Request</a>
    <?php endif; ?>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (empty($forms)): ?>
    <p class="text-muted">No <?= htmlspecialchars($title) ?> forms found.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover bg-white rounded shadow-sm">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <?php if ($roleId != 3): ?><th>Submitted By</th><?php endif; ?>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($forms as $form): ?>
                <tr>
                    <td><?= $form['id'] ?></td>
                    <?php if ($roleId != 3): ?>
                        <td><?= htmlspecialchars($form['full_name']) ?></td>
                    <?php endif; ?>
                    <td>
                        <span class="badge bg-<?= $statusBadge[$form['status']] ?? 'secondary' ?>">
                            <?= ucfirst(str_replace('_', ' ', $form['status'])) ?>
                        </span>
                    </td>
                    <td><?= date('M d, Y', strtotime($form['created_at'])) ?></td>
                    <td>
                        <a href="/processing-system/public/forms/view/<?= $form['id'] ?>"
                           class="btn btn-sm btn-outline-primary">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$pageTitle = $title;
require __DIR__ . '/../layouts/base.php';