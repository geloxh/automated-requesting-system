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

$statusBadge = ['draft' => 'secondary', 'submitted' => 'primary', 'in_approval' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'dark'];
$stepBadge   = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];

$title  = $formLabel[$form['form_type']] ?? $form['form_type'];
$roleId = $_SESSION['role_id'];
$formId = $form['id'];

ob_start();
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="page-header">
    <div>
        <h5 style="margin-bottom:.35rem">
            <?= htmlspecialchars($title) ?>
            <span class="muted" style="font-weight:400">#<?= $formId ?></span>
        </h5>
        <span class="badge badge-<?= $statusBadge[$form['status']] ?? 'secondary' ?>">
            <?= ucfirst(str_replace('_', ' ', $form['status'])) ?>
        </span>
    </div>
    <a href="javascript:history.back()" class="btn btn-ghost btn-sm">← Back</a>
</div>

<div class="two-col">

    <div class="card">
        <div class="card-header">Form Details</div>
        <div class="card-body">
            <div class="dl-grid">
                <?php foreach ($data as $key => $value): ?>
                    <span class="dl-label"><?= htmlspecialchars(str_replace('_', ' ', $key)) ?></span>
                    <span class="dl-value"><?= htmlspecialchars($value) ?></span>
                <?php endforeach; ?>
                <span class="dl-label">Submitted</span>
                <span class="dl-value"><?= date('M d, Y h:i A', strtotime($form['created_at'])) ?></span>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header">Approval Chain</div>
            <?php if (empty($approvalSteps)): ?>
                <div class="card-body">
                    <p class="muted" style="margin:0">No approval steps assigned.</p>
                </div>
            <?php else: ?>
                <ul class="step-list">
                    <?php foreach ($approvalSteps as $step): ?>
                    <li class="step-item">
                        <div>
                            <div class="step-name"><?= htmlspecialchars($step['full_name']) ?></div>
                            <div class="step-meta">Step <?= $step['sequence'] ?></div>
                            <?php if ($step['remarks']): ?>
                                <div class="step-meta">"<?= htmlspecialchars($step['remarks']) ?>"</div>
                            <?php endif; ?>
                            <?php if ($step['approved_at']): ?>
                                <div class="step-meta"><?= date('M d, Y h:i A', strtotime($step['approved_at'])) ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="badge badge-<?= $stepBadge[$step['status']] ?? 'secondary' ?>">
                            <?= ucfirst($step['status']) ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if ($canAct): ?>
        <div class="card" style="margin-top:1rem">
            <div class="card-header">Your Action</div>
            <div class="card-body">
                <form method="POST" id="approvalForm">
                    <?= \App\Helpers\Csrf::field(); ?>
                    <div class="form-group" style="margin-bottom:1rem">
                        <label>Remarks <span class="muted">(optional)</span></label>
                        <textarea name="remarks" rows="2"></textarea>
                    </div>
                    <div style="display:flex;gap:.5rem">
                        <button type="submit"
                            formaction="/processing-system/public/forms/<?= $formId ?>/approve"
                            class="btn btn-success" style="flex:1;justify-content:center">
                            Approve
                        </button>
                        <button type="submit"
                            formaction="/processing-system/public/forms/<?= $formId ?>/reject"
                            class="btn btn-danger" style="flex:1;justify-content:center"
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