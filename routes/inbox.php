<div class="page-heading">Approval Inbox</div>
<div class="page-subheading">Forms awaiting your review and decision.</div>

<?php if (empty($approvals)): ?>
    <div class="empty-state">
        <i class="ti ti-circle-check" style="font-size:2.5rem;color:var(--success);display:block;margin-bottom:.5rem"></i>
        You're all caught up! No pending approvals.
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
                <th>Received</th>
                <th>Step</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($approvals as $item): 
            $date = new DateTime($item['created_at']);
        ?>
            <tr>
                <td class="muted td-first"><?= $item['id'] ?></td>
                <td>
                    <div style="font-weight:500"><?= $formLabel[$item['form_type']] ?? $item['form_type'] ?></div>
                </td>
                <td><?= htmlspecialchars($item['owner_name']) ?></td>
                <td class="muted"><?= htmlspecialchars($item['department'] ?? '—') ?></td>
                <td class="muted"><?= $date->format('M d, g:i A') ?></td>
                <td>
                    <span class="badge badge-warning">
                        Step <?= $item['sequence'] ?>
                    </span>
                </td>
                <td class="td-last text-end">
                    <a href="/processing-system/public/forms/view/<?= $item['id'] ?>" class="btn btn-primary btn-sm">Review</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>