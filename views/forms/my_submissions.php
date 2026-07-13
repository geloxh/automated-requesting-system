<div class="page-heading">My Submissions</div>
<div class="page-subheading">Track the approval status of all your submitted requests.</div>

<?php
    $badgeMap = \App\Helpers\FormLabels::allBadges();
    $statusLabels = \App\Helpers\FormLabels::allStatusLabels();

    // Support pre-filtering from KPI card links (?status=draft|approved|rejected|in_approval)
    $activeFilter = $_GET['status'] ?? '';
    $inApprovalStatuses = ['submitted','immediatehead_approved','process_approved','department_reviewed','finance_reviewed'];

    $drafts = array_filter($forms ?? [], fn($f) => $f['status'] === 'draft');
    $others = array_filter($forms ?? [], fn($f) => $f['status'] !== 'draft');
    $uniqueTypes = array_unique(array_column($forms ?? [], 'form_type'));
    sort($uniqueTypes);
?>

<?php if (!empty($drafts)): ?>
<div class="card card--draft-alert">
    <div class="card-header card-header--flex">
        <i class="ti ti-pencil"></i>
        Draft<?= count($drafts) > 1 ? 's' : '' ?> — Ready to Submit
        <span class="badge badge-warning"><?= count($drafts) ?></span>
    </div>
    <div class="card-body card-body--flush">
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
                    <td class="muted td-stage">
                        <a href="<?= url('forms/view/' . $form['id']) ?>" class="btn btn-warning btn-sm">
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
        <select id="statusFilter" aria-label="Filter by status">
            <option value="">All statuses</option>
            <option value="draft" <?= $activeFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="submitted" <?= $activeFilter === 'submitted' ? 'selected' : '' ?>>Submitted</option>
            <option value="in_approval" <?= $activeFilter === 'in_approval' ? 'selected' : '' ?>>In Approval</option>
            <option value="approved" <?= $activeFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="rejected" <?= $activeFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
        <span class="filter-count" data-filter-count></span>
    </div>
    <table data-filterable data-search-col="0,1,2" data-filter-col="1">
        <thead>
            <tr>
                <th class="th-first">#</th>
                <th>Form Type</th>
                <th>Status</th>
                <th>Stage</th>
                <th>Date</th>
                <th class="td-last"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($forms as $form):
            $humanStatus = $statusLabels[$form['status']] ?? ucwords(str_replace('_', ' ', $form['status']));
            $stageName   = \App\Helpers\FormLabels::currentStage(
                $form['status'],
                isset($form['current_step']) ? (int)$form['current_step'] : null,
                $form['form_type']
            );
        ?>
            <tr data-status="<?= $form['status'] ?>">
                <td class="muted td-first"><?= $form['id'] ?></td>
                <td><?= htmlspecialchars($formLabel[$form['form_type']] ?? $form['form_type']) ?></td>
                <td>
                    <span class="badge badge-<?= $badgeMap[$form['status']] ?? 'secondary' ?>">
                        <?= htmlspecialchars($humanStatus) ?>
                    </span>
                </td>
                <td class="muted" class="muted text-xs"><?= htmlspecialchars($stageName) ?></td>
                <td class="muted"><?= date('M d, Y', strtotime($form['created_at'])) ?></td>
                <td class="td-last text-end">
                    <?php
                        $editableStatuses = ['draft', 'submitted', 'rejected'];
                        $canEditRow = in_array($form['status'], $editableStatuses, true);
                    ?>
                    <?php if ($form['status'] === 'draft'): ?>
                        <a href="<?= url('forms/view/' . $form['id']) ?>" class="btn btn-warning btn-sm">Submit</a>
                    <?php else: ?>
                        <a href="<?= url('forms/view/' . $form['id']) ?>" class="btn btn-ghost btn-sm">View</a>
                    <?php endif; ?>
                    <?php if ($canEditRow): ?>
                        <a href="<?= url('forms/' . $form['id'] . '/edit') ?>" class="btn btn-secondary btn-sm">
                            <i class="ti ti-pencil"></i>
                        </a>
                        <form method="POST" action="<?= url('forms/' . $form['id'] . '/delete') ?>"
                              id="deleteForm-<?= $form['id'] ?>"
                              class="form-inline-btn">
                            <?= \App\Helpers\Csrf::field() ?>
                            <button
                                type="button"
                                class="btn btn-danger btn-sm"
                                data-modal="deleteModal"
                                data-target-form="deleteForm-<?= $form['id'] ?>"
                                data-form-title="<?= htmlspecialchars(($formLabel[$form['form_type']] ?? $form['form_type']) . ' #' . $form['id']) ?>"
                            >
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<!-- Delete confirmation modal — shared by every row's delete button. -->
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