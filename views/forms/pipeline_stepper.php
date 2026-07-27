<?php
/**
 * pipeline_stepper.php
 *
 * Renders the horizontal approval-progress stepper on the form detail view.
 *
 * Expects: $form — the form row (requires 'form_type' and 'status')
 * Optionally: 
 * $approvalSteps — array of approval rows (id, sequence,
 *                  approver_id, status, approved_at, remarks, full_name)
 * When present, each completed node shows the approver name + date.
 *
 */

use App\Helpers\FormLabels;

// ── Choose the correct pipeline for this form type ── /
$financeTypes = ['advance_payment', 'request_for_payment', 'reimbursement', 'liquidation'];
$isFinance = in_array($form['form_type'], $financeTypes, true);

if ($isFinance) {
    $statusOrder = [
        'draft' => 'Draft (Preview)',
        'submitted' => 'Submitted (Employee Form Request)',
        'immediatehead_approved' => 'Checker (Immediate Head Checking)',
        'process_approved' => 'Process (Accounting Checking)',
        'finance_reviewed' => 'Evaluation (Finance Head Checking)',
        'completed' => 'Final Approval (Approved & Completed)',
    ];
} else {
    // Admin pipeline: Draft (Preview) → Submitted (Employee) → Checker (Immediate Head) → Review (Department Head) → Final Approval (Management, finalizes the form)
    $statusOrder = [
        'draft' => 'Draft (Preview)',
        'submitted' => 'Submitted (Employee Form Request)',
        'immediatehead_approved' => 'Checker (Immediate Head Checking)',
        'department_reviewed' => 'Evaluation (Dept. Head Checking)',
        'completed' => 'Final (Management Approval)',
    ];
}
 
$formStatus = $form['status'] ?? 'draft';
$isRejected = $formStatus === 'rejected';
$statusKeys = array_keys($statusOrder);
$currentIndex = array_search($formStatus, $statusKeys, true);
if ($currentIndex === false) $currentIndex = 0;

// Build a quick lookup: sequence → array of approval rows. Usually one
// row per sequence, but stages with concurrent approvers (reimbursement's
// dual-checker sign-off) can have more than one.
$stepsBySeq = [];
if (!empty($approvalSteps) && is_array($approvalSteps)) {
    foreach ($approvalSteps as $as) {
        $stepsBySeq[(int)$as['sequence']][] = $as;
    }
}
// Pipeline index maps directly to sequence number:
// index 0 = draft (no approval row), index 1 = sequence 1 (submitter's row),
// index 2 = sequence 2 (first approver), etc.

// Normalises a stored file path and reports whether it's an image, for the
// inline attachment/signature preview.
$normalizeStepFile = function (?string $path): array {
    if (!$path) return ['path' => '', 'isImage' => false];
    if (str_starts_with($path, 'storage/approvals/')) {
        $path = 'uploads/approvals/' . basename($path);
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return ['path' => $path, 'isImage' => in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)];
};
?>

<div class="approval-trail">
    <?php foreach ($statusOrder as $statusKey => $label):
        $i = array_search($statusKey, $statusKeys, true);

        // Determine visual state
        if ($isRejected) {
            $state = ($i < $currentIndex) ? 'done' : 'pending';
        } elseif ($i < $currentIndex) {
            $state = 'done';
        } elseif ($i === $currentIndex) {
            $state = 'current';
        } else {
            $state = 'pending';
        }

        // Look up approval rows for this step (usually one, sometimes two
        // for a concurrent stage — either dual sign-off, where BOTH must
        // sign [e.g. Vehicle Request's checker-approval], or a race stage,
        // where EITHER qualified approver acts and the other is auto-skipped
        // [Final Approval, shared by the real Final Approver and AdminApprover]).
        $rowsAtStep = $stepsBySeq[$i] ?? [];
        $actedRows = array_values(array_filter(
            $rowsAtStep, fn($r) => in_array($r['status'], ['approved', 'rejected'], true)
        ));
        $pendingRows = array_values(array_filter($rowsAtStep, fn($r) => $r['status'] === 'pending'));
        $skippedRows = array_values(array_filter($rowsAtStep, fn($r) => $r['status'] === 'skipped'));
        $isDualStage = count($rowsAtStep) > 1;
        $isRaceStage = $isDualStage && count($skippedRows) > 0;
    ?>
    <div class="approval-step <?= $state === 'done' ? 'is-done' : '' ?>
                               <?= $state === 'current' ? 'is-current'  : '' ?>
                               <?= ($isRejected && $state !== 'done') ? 'is-rejected' : '' ?>">
        <div class="step-dot <?= $state ?>">
            <?= $state === 'done' ? '<i class="ti ti-check"></i>' : ($i + 1) ?>
        </div>
        <div class="step-meta">
            <div class="step-name">
                <?= htmlspecialchars($label) ?>
                <?php if ($isRaceStage): ?>
                    <span class="step-dual-badge">Approved by 1 of <?= count($rowsAtStep) ?> qualified approvers</span>
                <?php elseif ($isDualStage): ?>
                    <span class="step-dual-badge"><?= count($actedRows) ?>/<?= count($rowsAtStep) ?> signed</span>
                <?php endif; ?>
            </div>

            <?php foreach ($actedRows as $row):
                $rowName = htmlspecialchars($row['full_name'] ?? '');
                $rowDate = !empty($row['approved_at']) ? date('M d, Y', strtotime($row['approved_at'])) : '';
                $rowRemarks = $row['remarks'] ?? '';
                $rowFile = $normalizeStepFile($row['file_path'] ?? null);
            ?>
                <div class="step-signee<?= $isDualStage ? ' step-signee--dual' : '' ?>">
                    <?php if ($rowName): ?><div class="step-approver"><?= $rowName ?></div><?php endif; ?>
                    <?php if ($rowDate): ?><div class="step-date"><?= $rowDate ?></div><?php endif; ?>
                    <?php if ($rowRemarks): ?>
                        <div class="step-remark">
                            <i class="ti ti-message"></i>
                            <?= htmlspecialchars($rowRemarks) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($rowFile['path']): ?>
                        <a href="<?= url($rowFile['path']) ?>"
                           class="step-attachment"
                           target="_blank"
                           rel="noopener">
                            <?php if ($rowFile['isImage']): ?>
                                <img src="<?= url($rowFile['path']) ?>" alt="" class="step-attachment-thumb">
                                <span>View signature</span>
                            <?php else: ?>
                                <span class="step-attachment-icon"><i class="ti ti-file-type-pdf"></i></span>
                                <span>View attachment</span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php foreach ($skippedRows as $row): $rowName = htmlspecialchars($row['full_name'] ?? ''); ?>
                <div class="step-signee step-signee--skipped">
                    <?php if ($rowName): ?><div class="step-approver step-approver--muted"><?= $rowName ?></div><?php endif; ?>
                    <div class="step-remark step-remark--muted">
                        <i class="ti ti-arrow-guide"></i>
                        Not needed — already approved by another qualified approver
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($isDualStage && $pendingRows): ?>
                <div class="step-waiting">
                    <i class="ti ti-hourglass-low"></i>
                    Waiting on <?= implode($isRaceStage ? ' or ' : ' &amp; ', array_map(
                        fn($r) => htmlspecialchars($r['full_name'] ?? 'checker'), $pendingRows
                    )) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($isRejected): ?>
    <p class="stepper-rejected-note">
        <i class="ti ti-circle-x"></i> This form was <strong>rejected</strong>.
    </p>
<?php endif; ?>