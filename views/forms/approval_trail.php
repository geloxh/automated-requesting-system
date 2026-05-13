<?php
/**
 * Approval Trail partial
 * Expects: $approvalSteps  — rows from approvals JOIN employees (full_name, sequence, status, remarks, approved_at)
 *          $form['status'] — current form status
 */

$icons = [
    1 => 'ti-send',
    2 => 'ti-user-check',
    3 => 'ti-building-bank',
    4 => 'ti-shield-check',
    5 => 'ti-circle-check',
    6 => 'ti-circle-check',
];

$labels = [
    1 => ['name' => 'Submitted', 'role' => 'Requestor'],
    2 => ['name' => 'Immediate Supervisor', 'role' => 'Level 1 Approval'],
    3 => ['name' => 'Department Head', 'role' => 'Level 2 Approval'],
    4 => ['name' => 'Checker', 'role' => 'Level 3 Approval'],
    5 => ['name' => 'Final Approver', 'role' => 'Final Approval'],
    6 => ['name' => 'Completed', 'role' => 'Final Approval'],
];

// Index by sequence (DB column), not level
$stepsBySeq = [];
foreach ($approvalSteps as $s) {
    $stepsBySeq[(int)$s['sequence']] = $s;
}

// Find the lowest pending sequence — that's the active step
$activeSec = null;
foreach ($stepsBySeq as $seq => $s) {
    if ($s['status'] === 'pending') {
        $activeSec = $seq;
        break;
    }
}
?>

<div class="form-section-title">Approval Trail</div>
<div class="approval-trail">
<?php for ($seq = 1; $seq <= 6; $seq++):
    $step = $stepsBySeq[$seq] ?? null;
    $status = $step['status'] ?? null;

    if ($status === 'approved') {
        $dotClass = 'done';
        $timeText = $step['approved_at']
            ? date('M d, Y h:i A', strtotime($step['approved_at']))
            : 'Approved';
    } elseif ($status === 'rejected') {
        $dotClass = 'rejected';
        $timeText = $step['approved_at']
            ? date('M d, Y h:i A', strtotime($step['approved_at']))
            : 'Rejected';
    } elseif ($status === 'pending') {
        $dotClass = ($seq === $activeSec) ? 'current' : 'pending';
        $timeText = ($seq === $activeSec) ? 'Awaiting approval' : 'Pending';
    } else {
        // No DB row yet for this sequence
        $dotClass = 'pending';
        $timeText = 'Pending';
    }

    // Use the actual approver name from the JOIN, fall back to generic label
    $name = $step['full_name'] ?? ($labels[$seq]['name'] ?? "Step $seq");
    $role = $labels[$seq]['role'] ?? '';
    $icon = $icons[$seq] ?? 'ti-circle';
    $remarks = $step['remarks'] ?? '';
?>
    <div class="approval-step">
        <div class="step-dot <?= $dotClass ?>"><i class="ti <?= $icon ?>"></i></div>
        <div class="step-info">
            <div class="step-name"><?= htmlspecialchars($name) ?></div>
            <div class="step-role"><?= htmlspecialchars($role) ?></div>
            <div class="step-time"><?= htmlspecialchars($timeText) ?></div>
            <?php if ($remarks): ?>
                <div class="step-time" style="font-style:italic">"<?= htmlspecialchars($remarks) ?>"</div>
            <?php endif; ?>
        </div>
    </div>
<?php endfor; ?>
</div>