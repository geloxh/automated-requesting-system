<div class="page-heading">My Submissions</div>
<div class="page-subheading">Track the approval status of all your submitted requests.</div>

<?php
$badgeMap = \App\Helpers\FormLabels::allBadges();

$drafts = array_filter($forms ?? [], fn($f) => $f['status'] === 'draft');
$others = array_filter($forms ?? [], fn($f) => $f['status'] !== 'draft');
$uniqueTypes = array_unique(array_column($forms ?? [], 'form_type'));
sort($uniqueTypes);
?>

<?php if (!empty($drafts)): ?>
<div class="card" style="border-left:3px solid var(--warning);margin-bottom:1rem">
    <div class="card-header" style="display:flex;align-items:center;gap:8px">
        <i class="ti ti-pencil"></i>
        Draft<?= count($drafts) > 1 ? 's' : '' ?> — Ready to Submit
        <span class="badge badge-warning"><?= count($drafts) ?></span>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th class="th-first">#</th>
                    <th>Form Type</th>
                    <th>Date Saved</th>
                    <th class="td-last"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($drafts as $form): ?>
                <tr>
                    <td class="muted td-first"><?= $form['id'] ?></td>
                    <td><?= htmlspecialchars($formLabel[$form['form_type']] ?? $form['form_type']) ?></td>
                    <td class="muted"><?= date('M d, Y', strtotime($form['created_at'])) ?></td>
                    <td class="td-last text-end">
                        <a href="/processing-system/public/forms/view/<?= $form['id'] ?>" class="btn btn-warning btn-sm">
                            Submit Now
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (empty($forms)): ?>
    <div class="empty-state">
        <i class="ti ti-send empty-state-icon"></i>
        You have no submissions yet.
    </div>
<?php else: ?>

<div class="table-wrap">
    <div class="filter-bar" data-filter-bar>
        <input type="search" placeholder="Search by form type or ID…" data-search-input aria-label="Search submissions">
        <select data-filter-select aria-label="Filter by form type">
            <option value="">All form types</option>
            <?php foreach ($uniqueTypes as $ft): ?>
                <option value="<?= htmlspecialchars($formLabel[$ft] ?? $ft) ?>"><?= htmlspecialchars($formLabel[$ft] ?? $ft) ?></option>
            <?php endforeach; ?>
        </select>
        <select data-status-filter aria-label="Filter by status" onchange="filterByStatus(this)">
            <option value="">All statuses</option>
            <option value="draft">Draft</option>
            <option value="submitted">Submitted</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="rejected">Rejected</option>
        </select>
        <span class="filter-count" data-filter-count></span>
    </div>
    <table data-filterable data-search-col="0,1,2" data-filter-col="1">
        <thead>
            <tr>
                <th class="th-first">#</th>
                <th>Form Type</th>
                <th>Status</th>
                <th>Date</th>
                <th class="td-last"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($forms as $form): ?>
            <tr data-status="<?= $form['status'] ?>">
                <td class="muted td-first"><?= $form['id'] ?></td>
                <td><?= htmlspecialchars($formLabel[$form['form_type']] ?? $form['form_type']) ?></td>
                <td>
                    <span class="badge badge-<?= $badgeMap[$form['status']] ?? 'secondary' ?>">
                        <?= ucfirst(str_replace('_', ' ', $form['status'])) ?>
                    </span>
                </td>
                <td class="muted"><?= date('M d, Y', strtotime($form['created_at'])) ?></td>
                <td class="td-last text-end">
                    <?php if ($form['status'] === 'draft'): ?>
                        <a href="/processing-system/public/forms/view/<?= $form['id'] ?>" class="btn btn-warning btn-sm">Submit</a>
                    <?php else: ?>
                        <a href="/processing-system/public/forms/view/<?= $form['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
function filterByStatus(sel) {
    const val = sel.value.toLowerCase();
    const rows = document.querySelectorAll('table[data-filterable] tbody tr');
    const inProgress = ['submitted','supervisor_reviewed','department_checked','checker_approved','final_approved'];
    rows.forEach(function(row) {
        const status = row.dataset.status || '';
        if (!val) { row.style.display = ''; return; }
        if (val === 'in_progress') { row.style.display = inProgress.includes(status) ? '' : 'none'; return; }
        row.style.display = status === val ? '' : 'none';
    });
}
</script>
<?php endif; ?>