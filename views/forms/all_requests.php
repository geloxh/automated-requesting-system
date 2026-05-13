<div class="page-heading">All Requests</div>
<div class="page-subheading">Complete record of all submitted forms across all departments.</div>

<?php if (empty($forms)): ?>
    <div class="empty-state">
        <i class="ti ti-file-description" style="font-size:2.5rem;color:var(--border);display:block;margin-bottom:.5rem"></i>
        No requests found.
    </div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th class="th-first">#</th>
                <th>Form Type</th>
                <th>Submitted By</th>
                <th>Department</th>
                <th>Status</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php
        $badgeMap = ['draft'=>'secondary','submitted'=>'primary','in_approval'=>'warning',
                     'approved'=>'success','rejected'=>'danger','cancelled'=>'dark'];
        foreach ($forms as $form): ?>
            <tr>
                <td class="muted td-first"><?= $form['id'] ?></td>
                <td><?= $formLabel[$form['form_type']] ?? $form['form_type'] ?></td>
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
<?php endif; ?>
