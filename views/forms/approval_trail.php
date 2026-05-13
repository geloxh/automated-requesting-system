<?php
/**
 * Approval Trail partial
 * Expects: $approvalSteps (array from Approval::findByForm)
 *          $form['status'] for context
 *
 * Each step needs: full_name, level, status, remarks, approved_at, approver_name
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
    1 => ['name' => 'Submitted',          'role' => 'Requestor'],
    2 => ['name' => 'Immediate Supervisor','role' => 'Level 1 Approval'],
    3 => ['name' => 'Department Head',     'role' => 'Level 2 Approval'],
    4 => ['name' => 'Checker',             'role' => 'Level 3 Approval'],
    5 => ['name' => 'Final Approver',      'role' => 'Final Approval'],
    6 => ['name' => 'Completed',           'role' => 'Final Approval'],
];

// Index steps by level for easy lookup
$stepsByLevel = [];
foreach ($approvalSteps as $s) {
    $stepsByLevel[(int)$s['level']] = $s;
}
?>

<div class="form-section-title">Approval Trail</div>
<div class="approval-trail">
<?php for ($lvl = 1; $lvl <= 6; $lvl++):
    $step   = $stepsByLevel[$lvl] ?? null;
    $status = $step['status'] ?? 'pending';

    if ($status === 'approved') {
        $dotClass = 'done';
        $timeText = $step['approved_at']
            ? date('M d, Y h:i A', strtotime($step['approved_at']))
            : 'Approved';
    } elseif ($status === 'pending') {
        // Is this the active (current) pending step?
        $dotClass = ($step !== null) ? 'current' : 'pending';
        $timeText = ($step !== null) ? 'Awaiting approval' : 'Pending';
    } elseif ($status === 'rejected') {
        $dotClass = 'rejected'; // add CSS below if needed
        $timeText = $step['approved_at']
            ? date('M d, Y h:i A', strtotime($step['approved_at']))
            : 'Rejected';
    } else {
        $dotClass = 'pending';
        $timeText = 'Pending';
    }

    $name    = $step['approver_name'] ?? ($labels[$lvl]['name'] ?? "Level $lvl");
    $role    = $labels[$lvl]['role'] ?? '';
    $icon    = $icons[$lvl] ?? 'ti-circle';
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