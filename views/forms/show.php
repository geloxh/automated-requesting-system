<?php
    $formLabel = \App\Helpers\FormLabels::all();

    $statusBadge = \App\Helpers\FormLabels::allBadges();
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
    <div class="show-identity">
        <div class="show-form-title">
            <?= htmlspecialchars($title) ?>
            <span class="show-form-id">#<?= $formId ?></span>
        </div>
        <span class="badge badge-<?= $statusBadge[$form['status']] ?? 'secondary' ?>">
            <?= ucfirst(str_replace('_', ' ', $form['status'])) ?>
        </span>
    </div>
    <button id="btn-back" class="btn btn-ghost btn-sm" data-fallback-url="/processing-system/public/approvals">
        <i class="ti ti-arrow-left"></i> Back
    </button>
</div>

<div class="two-col">

    <div class="card">
        <div class="card-header">Form Details</div>
        <div class="card-body">
            <div class="show-form-type-tag">
                <i class="ti <?= $iconMap[$type] ?? 'ti-file' ?>"></i>
                <?= htmlspecialchars($title) ?>
            </div>
            <div class="dl-grid">
            <?php
                // Human-readable field labels — avoids "from date", "payee", etc.
                $fieldLabels = [
                    'purpose' => 'Purpose',
                    'payment_type' => 'Payment Type',
                    'payee' => 'Payee',
                    'date' => 'Date',
                    'employee_name' => 'Employee Name',
                    'department' => 'Department',
                    'request_date' => 'Request Date',
                    'unit_owner' => 'Unit Owner',
                    'bearer_name' => 'Bearer Name',
                    'service_type' => 'Service Type',
                    'leave_type' => 'Leave Type',
                    'from_date' => 'From Date',
                    'to_date' => 'To Date',
                    'payment_term' => 'Payment Term',
                    'car_available' => 'Car Available',
                    'trip_type' => 'Trip Type',
                ];
            ?>
            <?php if (empty($data)): ?>
                <div class="empty-state" style="padding:1rem">
                    <i class="ti ti-file-off empty-state-icon"></i>
                    No form data available.
                </div
            <?php else: ?>
                <?php foreach ($data as $key => $value):
                    if ($key === 'csrf_token') continue;
                    $label = $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
                ?>
                    <span class="dl-label"><?= htmlspecialchars($label) ?></span>
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
            <?php endif; ?>
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
                        'checker-approval' => 'Approve — Checker Approval',
                        'review-approval' => 'Approve — Review Approval',
                        'process-approval' => 'Approve — Process Approval',
                        'evaluation-approval' => 'Approve — Evaluation Approval',
                        'grant-approval' => 'Grant Approval Request',
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
                        <?php
                            $nextStepHints = [
                                'check-approval' => 'Approving will forward this to the Department Head.',
                                'review-approval' => 'Approving will forward this to the Final Approver.',
                                'process-approval' => 'Approving will forward this to the Finance Head',
                                'evaluation-approval' => 'Approving will forward this to the Final Approver.',
                                'grant-approval' => 'Approving will mark this as Final Approved.',
                                'complete' => 'This will close the request as fullly completed.',
                                'submit' => 'Submitting will send this for Checker Approval.',
                            ];
                        ?>
                        <?php if (isset($nextStepHints[$nextAction])): ?>
                            <p class="action-hint">
                                <i class="ti ti-info-circle"></i>
                                <?= $nextStepHints[$nextAction] ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($nextAction): ?>
                            <button type="submit"
                                name="action"
                                value="approve"
                                formaction="/processing-system/public/forms/<?= $formId ?>/approve/<?= htmlspecialchars($nextAction) ?>"
                                class="btn btn-success btn-block">
                                <i class="ti ti-circle-check"></i>
                                <?= htmlspecialchars($approveLabel) ?>
                            </button>
                        <?php endif; ?>

                        <button type="submit"
                            name="action"
                            value="reject"
                            formaction="/processing-system/public/forms/<?= $formId ?>/reject"
                            class="btn btn-danger btn-block"
                            id="btn-reject">
                            <i class="ti ti-x"></i>Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    </div>

</div>

<script src='/processing-system/public/scripts/show.js'></script>