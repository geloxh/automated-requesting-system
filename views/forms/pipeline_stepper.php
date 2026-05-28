<?php
/**
 * pipeline_stepper.php
 *
 * Renders the horizontal approval-progress stepper on the form detail view.
 *
 * Expects:  $form  — the form row (requires 'form_type' and 'status')
 * Optionally: $approvalSteps — array of approval rows (id, sequence,
 *             approver_id, status, approved_at, remarks, full_name)
 *             When present, each completed node shows the approver name + date.
 *
 * FIX: Previously this file contained two complete steppers rendered back-to-back
 *      (an inline-style <table> version AND a CSS-class div version).  Both were
 *      output on every page load.  The table version also used a hardcoded
 *      admin-pipeline status list, breaking the display for finance forms.
 *      This rewrite:
 *        1. Removes the duplicate table stepper entirely.
 *        2. Selects the correct pipeline (admin vs finance) based on form_type.
 *        3. Surfaces approver name + timestamp per completed step when available.
 */

use App\Helpers\FormLabels;

// ── Choose the correct pipeline for this form type ──────────────────────────
$financeTypes = ['advance_payment', 'request_for_payment', 'reimbursement', 'liquidation'];
$isFinance = in_array($form['form_type'], $financeTypes, true);

if ($isFinance) {
    // Finance pipeline: submitted → checker → process → evaluation → final → completed
    $statusOrder = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'checker_approved' => 'Checker',
        'process_approved' => 'Process',
        'finance_reviewed' => 'Evaluation',
        'final_approved' => 'Final Approval',
        'completed' => 'Approved',
    ];
} else {
    // Admin pipeline: submitted → checker → review → final → completed
    $statusOrder = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'checker_approved' => 'Checker',
        'department_reviewed' => 'Review',
        'final_approved' => 'Final Approval',
        'completed' => 'Approved',
    ];
}

$formStatus = $form['status'] ?? 'draft';
$isRejected = $formStatus === 'rejected';
$statusKeys = array_keys($statusOrder);
$currentIndex = array_search($formStatus, $statusKeys, true);
if ($currentIndex === false) $currentIndex = 0;

// Build a quick lookup: sequence → approval row (for name/date tooltips)
$stepBySeq = [];
if (!empty($approvalSteps) && is_array($approvalSteps)) {
    foreach ($approvalSteps as $as) {
        $stepBySeq[(int)$as['sequence']] = $as;
    }
}
// sequence 1 = submission (no approval row); sequences 2+ map to pipeline steps
// status index 0=draft, 1=submitted, 2=step2, 3=step3 ...
// so pipeline sequence for index $i = $i  (index 1 = seq 1, index 2 = seq 2 …)
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

        // Look up approver info for completed steps (sequence = index)
        $stepRow = $stepBySeq[$i] ?? null;
        $approverName = ($stepRow && $state === 'done') ? htmlspecialchars($stepRow['full_name'] ?? '') : '';
        $approvedAt = ($stepRow && $state === 'done' && !empty($stepRow['approved_at']))
                        ? date('M d, Y', strtotime($stepRow['approved_at']))
                        : '';
    ?>
    <div class="approval-step <?= $state === 'done' ? 'is-done' : '' ?>
                               <?= $state === 'current' ? 'is-current'  : '' ?>
                               <?= ($isRejected && $state !== 'done') ? 'is-rejected' : '' ?>">
        <div class="step-dot <?= $state ?>">
            <?= $state === 'done' ? '<i class="ti ti-check"></i>' : ($i + 1) ?>
        </div>
        <div class="step-meta">
            <div class="step-name"><?= htmlspecialchars($label) ?></div>
            <?php if ($approverName): ?>
                <div class="step-approver"><?= $approverName ?></div>
            <?php endif; ?>
            <?php if ($approvedAt): ?>
                <div class="step-date"><?= $approvedAt ?></div>
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
