<?php
    $formLabel = \App\Helpers\FormLabels::all();

    $statusBadge = [
        'draft' => 'secondary',
        'submitted' => 'primary',
        'supervisor_reviewed' => 'info',
        'department_checked' => 'info',
        'checker_approved' => 'warning',
        'final_approved' => 'success',
        'completed' => 'success',
        'rejected' => 'danger',
        'cancelled' => 'dark',
        // legacy / admin-path values kept for safety
        'in_approval' => 'warning',
        'approved' => 'success',
    ];
    $stepBadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];

    $type = $form['form_type'] ?? $form['type'] ?? 'unknown';
    $title = $formLabel[$type] ?? ucwords(str_replace('_', ' ', $type));
    $roleId = $_SESSION['role_id'];
    $formId = $form['id'];
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
        <span class="badge badge-<?= $statusBadge[$form['status']] ?? 'secondary' ?>">
            <?= ucfirst(str_replace('_', ' ', $form['status'])) ?>
        </span>
    </div>
    <button id="btn-back" class="btn btn-ghost btn-sm">← Back</button>
</div>

<div class="two-col">

    <div class="card">
        <div class="card-header">Form Details</div>
        <div class="card-body">
            <div class="dl-grid">
                <?php foreach ($data ?? [] as $key => $value): ?>
                    <span class="dl-label"><?= htmlspecialchars(str_replace('_', ' ', $key)) ?></span>
                    <span class="dl-value">
                        <?php if (is_array($value)): ?>
                            <?= htmlspecialchars(implode(', ', $value)) ?>
                        <?php else: ?>
                            <?= htmlspecialchars($value) ?>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
                <span class="dl-label">Submitted</span>
                <span class="dl-value"><?= date('M d, Y h:i A', strtotime($form['created_at'])) ?></span>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header">Approval Trail</div>
            <div class="card-body">
                <?php require __DIR__ . '/approval_trail.php'; ?>
            </div>
        </div>
        
        <?php if ($canAct): ?>
        <div class="card card-action">
            <div class="card-header">Your Action</div>
            <div class="card-body">
                <?php
                    // Map $nextAction to the human-readable step label for the button
                    $actionLabels = [
                        'submit' => 'Submit for Approval',
                        'supervisor-review' => 'Approve — Supervisor Review',
                        'department-check' => 'Approve — Department Check',
                        'checker-supervisor' => 'Approve — Checker Supervisor',
                        'final-approval' => 'Approve — Final Approval',
                        'complete' => 'Mark as Completed',
                    ];
                    $approveLabel = $actionLabels[$nextAction] ?? 'Approve';
                ?>
                <form method="POST" id="approvalForm" enctype="multipart/form-data">
                    <?= \App\Helpers\Csrf::field(); ?>
                    <div class="form-group form-group--spaced">
                        <label>
                            Remarks
                            <?php if ($nextAction !== 'submit'): ?>
                                <span class="muted" id="remarks-hint">(required if rejecting)</span>
                            <?php endif; ?>
                        </label>
                        <textarea name="remarks" rows="2" id="remarksField"></textarea>

                        <label>Attach File <span class="muted">(optional — image or PDF)</span></label>
                        <input type="file" name="approval_file" accept="image/*,.pdf">
                    </div>

                    <div class="action-btns">
                        <?php if ($nextAction): ?>
                        <button type="submit"
                            name="action"
                            value="approve"
                            formaction="/processing-system/public/forms/<?= $formId ?>/approve/<?= htmlspecialchars($nextAction) ?>"
                            class="btn btn-success btn-block">
                            <?= htmlspecialchars($approveLabel) ?>
                        </button>
                        <?php endif; ?>

                        <button type="submit"
                            name="action"
                            value="reject"
                            formaction="/processing-system/public/forms/<?= $formId ?>/reject"
                            class="btn btn-danger btn-block"
                            id="btn-reject">
                            Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<script src='/processing-system/public/scripts/show.js'></script>