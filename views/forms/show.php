<?php
    $formLabel = \App\Helpers\FormLabels::all();
    $statusBadge = \App\Helpers\FormLabels::allBadges();
    $stepBadge = [ 'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger' ];

    $type = $form['form_type'] ?? 'unknown';
    $title = \App\Helpers\FormLabels::get($type);
    $roleId = $_SESSION['role_id'];
    $formId = $form['id'];
    $humanStatus = \App\Helpers\FormLabels::statusLabel($form['status']);

    // Submitter controls: edit/delete allowed on draft, submitted, rejected
    $isOwner = ((int)$form['submitted_by'] === (int)$_SESSION['user_id']) || $roleId == 1;
    $editableStatuses = ['draft', 'submitted', 'rejected'];
    $canEdit = $isOwner && in_array($form['status'], $editableStatuses, true);
    $canDelete = $isOwner && in_array($form['status'], $editableStatuses, true);

    $submittedBy = $form['submitter_name'] ?? ($form['submitted_by_name'] ?? null);
    $submittedDate = !empty($form['created_at']) ? date('M j, Y', strtotime($form['created_at'])) : null;
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="page-header show-page-header">

    <!-- Left: identity block -->
    <div class="show-identity">
        <button id="btn-back" class="btn btn-ghost btn-sm show-back-btn" data-fallback-url="<?= url('approvals') ?>" title="Go back">
            <i class="ti ti-arrow-left"></i>
        </button>
        <div class="show-title-block">
            <div class="show-form-title">
                <?= htmlspecialchars($title) ?>
                <span class="show-form-id">#<?= $formId ?></span>
            </div>
            <?php if ($submittedBy || $submittedDate): ?>
            <div class="show-form-meta">
                <?php if ($submittedBy): ?>
                    <i class="ti ti-user"></i> <?= htmlspecialchars($submittedBy) ?>
                <?php endif; ?>
                <?php if ($submittedDate): ?>
                    <span class="show-meta-sep">·</span>
                    <i class="ti ti-calendar"></i> <?= $submittedDate ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <span class="badge badge-<?= $statusBadge[$form['status']] ?? 'secondary' ?> show-status-badge">
            <?= htmlspecialchars($humanStatus) ?>
        </span>
    </div>

    <!-- Right: actions -->
    <div class="show-actions">

        <button type="button" class="btn btn-secondary btn-sm" id="btn-share-trigger" data-modal="shareModal">
            <i class="ti ti-share"></i> Share
        </button>

        <?php if ($canEdit): ?>
        <a href="<?= url('forms/' . $formId . '/edit') ?>" class="btn btn-secondary btn-sm">
            <i class="ti ti-pencil"></i> Edit
        </a>
        <?php endif; ?>

        <?php if ($canDelete): ?>
        <!-- Delete form — CSRF token lives here, modal just triggers submit -->
        <form method="POST"
              action="<?= url('forms/' . $formId . '/delete') ?>"
              id="deleteForm">
            <?= \App\Helpers\Csrf::field() ?>
            <button 
                type="button"
                class="btn btn-danger btn-sm"
                id="btn-delete-trigger"
                data-modal="deleteModal"
                data-form-title="<?= htmlspecialchars($title . ' #' . $formId) ?>"
            >
                <i class="ti ti-trash"></i> Delete
            </button>
        </form>
        <?php endif; ?>

    </div>

</div>

<!-- Share-to-chat modal -->
<div class="ars-modal-backdrop" id="shareModal" role="dialog" aria-modal="true" aria-labelledby="shareModalTitle" hidden>
    <div class="ars-modal">
        <div class="ars-modal-icon">
            <i class="ti ti-share"></i>
        </div>
        <h3 class="ars-modal-title" id="shareModalTitle">Share this form</h3>
        <p class="ars-modal-body">
            Send <strong><?= htmlspecialchars($title . ' #' . $formId) ?></strong> to a coworker via Messaging.
        </p>

        <div class="form-group">
            <label class="show-field-label">Send to</label>
            <input type="text" id="shareRecipientSearch" placeholder="Search people…" autocomplete="off">
            <div id="shareRecipientList" class="share-recipient-list"></div>
        </div>

        <div class="form-group">
            <label class="show-field-label">Note <span class="show-field-hint">optional</span></label>
            <textarea id="shareNote" rows="2" placeholder="Add a message…"></textarea>
        </div>

        <div id="shareModalError" class="alert alert-danger" hidden></div>

        <div class="ars-modal-actions">
            <button type="button" class="btn btn-ghost btn-sm" data-modal-close="shareModal">Cancel</button>
            <button type="button" class="btn btn-primary btn-sm" id="btn-share-confirm" disabled>
                <i class="ti ti-send"></i> Share
            </button>
        </div>
    </div>
</div>

<?php if ($canDelete): ?>
<!-- Delete confirmation modal — no CSRF here, just UI -->
<div class="ars-modal-backdrop" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle" hidden>
    <div class="ars-modal">
        <div class="ars-modal-icon ars-modal-icon--danger">
            <i class="ti ti-trash"></i>
        </div>
        <h3 class="ars-modal-title" id="deleteModalTitle">Delete Form?</h3>
        <p class="ars-modal-body">
            You're about to permanently delete
            <strong id="deleteModalFormName"></strong>.
            This action cannot be undone.
        </p>
        <div class="ars-modal-actions">
            <button type="button" class="btn btn-ghost btn-sm" data-modal-close="deleteModal">
                Cancel
            </button>
            <button type="button" class="btn btn-danger btn-sm" id="btn-delete-confirm">
                <i class="ti ti-trash"></i> Yes, Delete
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="two-col">

    <!-- ── Left: Form Details ── -->
    <div class="card show-details-card">
        <div class="card-header card-header--flex">
            <i class="ti ti-file-description"></i>
            Form Details
        </div>
        <div class="card-body">
            <?php if (empty($data)): ?>
                <div class="empty-state">
                    <i class="ti ti-file-off empty-state-icon"></i>
                    <p>No form data available.</p>
                </div>
            <?php else: ?>
            <?php
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
            <dl class="show-dl">
                <?php foreach ($data as $key => $value):
                    if ($key === 'csrf_token') continue;
                    $label = $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
                    $looksLikeDate = preg_match('/date|_at$/', $key);

                    // Repeatable line-item fields (e.g. item_date[]) arrive here as
                    // arrays — format each element individually instead of passing
                    // the whole array to strtotime(), which throws a TypeError.
                    if (is_array($value)) {
                        $display = implode(', ', array_map(function ($v) use ($looksLikeDate) {
                            if ($looksLikeDate && is_string($v) && $v !== '' && strtotime($v) !== false) {
                                return date('M d, Y', strtotime($v));
                            }
                            return $v;
                        }, $value));
                    } else {
                        $isDate = $looksLikeDate && is_string($value) && $value !== '' && strtotime($value) !== false;
                        $display = $isDate ? date('M d, Y', strtotime($value)) : $value;
                    }
                ?>
                    <div class="show-dl-row">
                        <dt class="show-dl-label"><?= htmlspecialchars($label) ?></dt>
                        <dd class="show-dl-value"><?= htmlspecialchars($display) ?></dd>
                    </div>
                <?php endforeach; ?>
                <div class="show-dl-row show-dl-row--muted">
                    <dt class="show-dl-label">Submitted</dt>
                    <dd class="show-dl-value"><?= date('M d, Y · g:i A', strtotime($form['created_at'])) ?></dd>
                </div>
            </dl>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Right column ── -->
    <div class="show-right-col">

        <!-- Approval Trail -->
        <div class="card">
            <div class="card-header card-header--flex">
                <i class="ti ti-timeline"></i>
                Approval Trail
            </div>
            <div class="card-body">
                <?php require __DIR__ . '/pipeline_stepper.php'; ?>
            </div>
        </div>

        <!-- Approver action card -->
        <?php if ($canAct): ?>
        <div class="card card-action show-action-card">
            <div class="card-header card-header--flex">
                <i class="ti ti-writing"></i>
                Your Action
            </div>
            <div class="card-body">
                <?php
                    $actionLabels = [
                        'submit' => 'Submit for Approval',
                        'checker-approval' => 'Approve — Checker',
                        'review-approval' => 'Approve — Review',
                        'process-approval' => 'Approve — Process',
                        'evaluation-approval' => 'Approve — Evaluation',
                        'grant-approval' => 'Grant Final Approval',
                        'complete' => 'Mark as Completed',
                    ];
                    $approveLabel = $actionLabels[$nextAction] ?? 'Approve';

                    $nextStepHints = [
                        'checker-approval' => 'Forwards to the Department Head.',
                        'review-approval' => 'Forwards to the Final Approver.',
                        'process-approval' => 'Forwards to the Finance Head.',
                        'evaluation-approval' => 'Forwards to the Final Approver.',
                        'grant-approval' => \App\Helpers\FormLabels::isAdminForm($form['form_type'])
                            ? 'Approves and completes this request.'
                            : 'Marks this request as Final Approved.',
                        'complete' => 'Closes the request as fully completed.',
                        'submit' => 'Sends this for Checker Approval.',
                    ];
                ?>

                <!-- CSRF token lives here; no inline handlers -->
                <form method="POST" id="approvalForm" enctype="multipart/form-data">
                    <?= \App\Helpers\Csrf::field(); ?>

                    <?php if (isset($nextStepHints[$nextAction])): ?>
                    <div class="show-next-hint">
                        <i class="ti ti-arrow-right"></i>
                        <?= htmlspecialchars($nextStepHints[$nextAction]) ?>
                    </div>
                    <?php endif; ?>

                    <div class="form-group show-remarks-group">
                        <label class="show-field-label">
                            Remarks
                            <?php if ($nextAction !== 'submit'): ?>
                                <span class="show-field-hint" id="remarks-hint">required to reject</span>
                            <?php endif; ?>
                        </label>
                        <textarea 
                            name="remarks"
                            rows="3"
                            id="remarksField"
                            placeholder="Add a note for the next approver…"
                        ></textarea>
                    </div>

                    <div class="form-group show-attach-group">
                        <label class="show-field-label">
                            Signature
                            <span class="show-field-hint">optional — draw or upload</span>
                        </label>

                        <div class="sig-tabs" role="tablist">
                            <button type="button" class="sig-tab active" id="sigTabDraw" role="tab" aria-selected="true">
                                <i class="ti ti-signature"></i> Draw
                            </button>
                            <button type="button" class="sig-tab" id="sigTabUpload" role="tab" aria-selected="false">
                                <i class="ti ti-paperclip"></i> Upload
                            </button>
                        </div>

                        <!-- Draw-signature panel -->
                        <div class="sig-panel" id="sigPanelDraw" role="tabpanel">
                            <div class="sig-pad-wrap" id="sigPadWrap">
                                <span class="sig-placeholder" id="sigPlaceholder">Sign here</span>
                                <canvas class="sig-canvas" id="sigCanvas"></canvas>
                                <span class="sig-baseline"></span>
                            </div>
                            <div class="sig-pad-toolbar">
                                <span class="sig-pad-status" id="sigStatus">
                                    <i class="ti ti-info-circle"></i> Draw with your mouse or finger
                                </span>
                                <button type="button" class="btn btn-ghost btn-sm" id="sigClear">
                                    <i class="ti ti-eraser"></i> Clear
                                </button>
                            </div>
                        </div>

                        <!-- Upload-file panel -->
                        <div class="sig-panel sig-hidden" id="sigPanelUpload" role="tabpanel">
                            <label class="show-file-drop" id="fileDrop">
                                <i class="ti ti-upload"></i>
                                <span id="fileDropLabel">Drag &amp; drop, or click to attach</span>
                                <span class="show-file-drop-sub">Image or PDF, up to 5&nbsp;MB</span>
                                <input 
                                    type="file"
                                    name="approval_file"
                                    accept="image/jpeg,image/png,image/gif,application/pdf"
                                    class="hidden-input"
                                    id="approvalFile"
                                >
                            </label>

                            <div class="file-preview sig-hidden" id="filePreview">
                                <img class="file-preview-thumb sig-hidden" id="filePreviewImg" alt="">
                                <i class="ti ti-file-type-pdf file-preview-icon sig-hidden" id="filePreviewPdfIcon"></i>
                                <div class="file-preview-info">
                                    <span class="file-preview-name" id="filePreviewName"></span>
                                    <span class="file-preview-size" id="filePreviewSize"></span>
                                </div>
                                <button type="button" class="file-preview-remove" id="filePreviewRemove" title="Remove file">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>

                            <div class="file-error sig-hidden" id="fileError">
                                <i class="ti ti-alert-circle"></i>
                                <span id="fileErrorText"></span>
                            </div>
                        </div>
                    </div>

                    <div class="show-action-btns">
                        <?php if ($nextAction): ?>
                            <button 
                                type="submit"
                                name="action"
                                value="approve"
                                formaction="<?= url('forms/' . $formId . '/approve/' . htmlspecialchars($nextAction)) ?>"
                                class="btn btn-success btn-block show-btn-approve"
                            >
                                <i class="ti ti-circle-check"></i>
                                <?= htmlspecialchars($approveLabel) ?>
                            </button>
                        <?php endif; ?>

                        <?php if ($nextAction !== 'submit'): ?>
                        <button 
                            type="submit"
                            name="action"
                            value="reject"
                            formaction="<?= url('forms/' . $formId . '/reject') ?>"
                            class="btn btn-outline-danger btn-block"
                            id="btn-reject"
                        >
                            <i class="ti ti-x"></i> Reject
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Terminal status card -->
        <?php if (!$canAct && in_array($form['status'], ['completed', 'rejected'], true)): ?>
        <div class="card show-terminal-card">
            <div class="card-body">
                <?php if ($form['status'] === 'completed'): ?>
                    <div class="show-terminal show-terminal--success">
                        <div class="show-terminal-icon"><i class="ti ti-circle-check"></i></div>
                        <div>
                            <div class="show-terminal-title">Request Completed</div>
                            <div class="show-terminal-sub">This request has been fully approved and completed.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="show-terminal show-terminal--danger">
                        <div class="show-terminal-icon show-terminal-icon--danger"><i class="ti ti-circle-x"></i></div>
                        <div>
                            <div class="show-terminal-title">Request Rejected</div>
                            <div class="show-terminal-sub">Check the approval trail above for the rejection reason.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.show-right-col -->

</div>

<script nonce="<?= htmlspecialchars($GLOBALS['csp_nonce'] ?? '') ?>">window.SHARE_FORM_ID = <?= (int) $formId ?>;</script>
<script src='<?= url('scripts/share-form.js') ?>'></script>
<script src='<?= url('scripts/show.js') ?>'></script>