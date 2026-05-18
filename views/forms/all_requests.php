<div class="page-heading">All Requests</div>
<div class="page-subheading">Complete record of all submitted forms across all departments.</div>


<?php
    $badgeMap = [
        'draft' => 'secondary',
        'submitted' => 'primary',
        'supervisor_reviewed' => 'info',
        'department_checked' => 'info',
        'checker_approved' => 'warning',
        'final_approved' => 'success',
        'rejected' => 'danger',
        'cancelled' => 'dark',
        'in_approval' => 'warning',
        'approved' => 'success',
    ];

    $uniqueTypes = array_unique(array_column(forms ?? [], 'form_type' ));
    sort($uniqueTypes);
    $uniqueDepts = array_unique(array_filter(array_column($forms ?? [], 'department')));
    sort($uniqueDepts);
?>
    
<?php if (empty($forms)): ?>
    <div class="empty-state">
        <i class="ti ti-file-description empty-state-icon"></i>
        No requests found.
    </div>
<?php else: ?>

<div class="table-wrap">
    <div class="filter-bar" data-filter-bar>
        <input type="search" placeholder="Search by name, department, form..." data-search-input aria-label="Search requests">
        <select data-filter-select aria-label="Filter by form type">
            <option value="">All form types</option>
            <?php foreach ($uniqueTypes as $ft): ?>
                <option value="<?= htmlspecialchars($formLabel[$ft] ?? $ft) ?>"><?= htmlspecialchars($formLabel[$ft] ?? $ft) ?></option>
            <?php endforeach; ?>
        </select>
        <select aria-label="Filter by department" onchange="filterByDept(this)">
            <option value="">All departments</option>
            <?php foreach ($uniqueDepts as $dept): ?>
                <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
            <?php endforeach; ?>
        </select>
        <select aria-label="Filter by status" onchange="filterByStatusAll(this)">
            <option value="">All statuses</option>
            <option value="submitted">submitted_by</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="rejected">Rejected</option>
        </select>
        <span class="filter-count" data-filter-count></span>
    </div>
    <table data-filterable data-search-col="0, 1, 2, 3" data-filter-col="0">
        <thead>
            <tr>
                <th class="th-first">#</th>
                <th>Form Type</th>
                <th>Submitted By</th>
                <th>Department</th>
                <th>Status</th>
                <th>Date</th>
                <th class="td-last"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($forms as $form): ?>
                <tr data-status="<?= $form['status'] ?>" data-dept="<?= htmlspecialchars($form['department'] ?? '') ?>">
                    <td class="muted td-first"><?= $form['id'] ?></td>
                    <td><?= htmlspecialchars($formLabel[$form['form_type']] ?? $form['form_type']) ?></td>
                    <td><?= htmlspecialchars($form['full_name']) ?></td>
                    <td class="muted"><?= htmlspecialchars($form['department'] ?? '—') ?></td>
                    <td>
                        <span class="badge badge-<?= $badgeMap[$form['status']] ?? 'secondary' ?>">
                            <?= ucfirst(str_replace('_', ' ', $form['status'])) ?>
                        </span>
                    </td>
                    <td class="muted"><?= date('M d, Y', strtotime($form['created_at'])) ?></td>
                    <td class="td-last text-end">
                        <a href="/processing-system/public/forms/view/<?= $form['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src='/processing-system/public/scripts/all_requests.js'></script>
<?php endif; ?>
