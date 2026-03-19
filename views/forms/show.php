<?php
    // Variables: $form (array), $approvalSteps (array), $canAct (bool), $data (array)
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

    $stepBadge = [
        'pending'  => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
    ];

    $title = $formLabel[$form['form_type']] ?? $form['form_type'];
    $roleId = $_SESSION['role_id'];
    $formId = $form['id'];

    ob_start();
?>

<!-- Flash Messages -->
<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1"><?= htmlspecialchars($title) ?> <span class="text-muted fs-6">#<?= $formId ?></span></h5>
        <span class="badge bg-<?= $statusBadge[$form['status']] ?? 'secondary' ?> fs-6">
            <?= ucfirst(str_replace('_', ' ', $form['status'])) ?>
        </span>
    </div>
    <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>

<div class="row g-4">

    <!-- Form Data -->
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">Form Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <?php foreach ($data as $key => $value): ?>
                        <dt class="col-sm-4 text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $key)) ?></dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($value) ?></dd>
                    <?php endforeach; ?>
                    <dt class="col-sm-4">Submitted</dt>
                    <dd class="col-sm-8"><?= date('M d, Y h:i A', strtotime($form['created_at'])) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Approval Chain -->
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">Approval Chain</div>
            <div class="card-body p-0">
                <?php if (empty($approvalSteps)): ?>
                    <p class="text-muted p-3 mb-0">No approval steps assigned.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($approvalSteps as $step): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($step['full_name']) ?></div>
                            <small class="text-muted">Step <?= $step['sequence'] ?></small>
                            <?php if ($step['remarks']): ?>
                                <div class="text-muted small mt-1">"<?= htmlspecialchars($step['remarks']) ?>"</div>
                            <?php endif; ?>
                            <?php if ($step['approved_at']): ?>
                                <div class="text-muted small"><?= date('M d, Y h:i A', strtotime($step['approved_at'])) ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-<?= $stepBadge[$step['status']] ?? 'secondary' ?>">
                            <?= ucfirst($step['status']) ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Approve / Reject — only for the current pending approver -->
        <?php if ($canAct): ?>
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-dark text-white">Your Action</div>
            <div class="card-body">
                <form method="POST" id="approvalForm">
                    <div class="mb-3">
                        <label class="form-label">Remarks <span class="text-muted small">(optional)</span></label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit"
                            formaction="/processing-system/public/forms/<?= $formId ?>/approve"
                            class="btn btn-success w-50">
                            Approve
                        </button>
                        <button type="submit"
                            formaction="/processing-system/public/forms/<?= $formId ?>/reject"
                            class="btn btn-danger w-50"
                            onclick="return confirm('Reject this form?')">
                            Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php
$content   = ob_get_clean();
$pageTitle = $title . ' #' . $formId;
require __DIR__ . '/../layouts/base.php';
