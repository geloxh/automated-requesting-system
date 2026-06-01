<div class="page-header">
    <h5>Employees</h5>
    <a href="/processing-system/public/employees/create" class="btn btn-primary btn-sm">+ Add Employee</a>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th> 
                <th>Role</th>
                <th>Supervisor</th>
                <th>Active</th>
                <th>Employment Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $e): ?>
            <tr>
                <td><?= htmlspecialchars($e['employee_code']) ?></td>
                <td><?= htmlspecialchars($e['full_name']) ?></td>
                <td><?= htmlspecialchars($e['email']) ?></td>
                <td><?= htmlspecialchars($e['department']) ?></td>
                <td><?= htmlspecialchars($e['role_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($e['supervisor_name'] ?? 'None') ?></td>
                <td>
                    <?php if ($e['is_active']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="/processing-system/public/employees/<?= $e['id'] ?>/status">
                        <?= \App\Helpers\Csrf::field() ?>
                        <select name="employment_status" class="form-select-sm auto-submit">
                            <?php foreach (['employed', 'resigned', 'floating'] as $s): ?>
                                <option value="<?= $s ?>" <?= $e['employment_status'] === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td>
                    <div style="display: flex; gap: 4px; align-items: center;">
                        <a href="/processing-system/public/employees/edit/<?= $e['id'] ?>" class="btn btn-ghost btn-sm" title="Edit">
                            <i class="ti ti-edit"></i> Edit
                        </a>
                        
                        <?php if ($e['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" action="/processing-system/public/employees/<?= $e['id'] ?>/delete"
                              data-confirm="Deactivate <?= htmlspecialchars($e['full_name'], ENT_QUOTES) ?>?">
                            <?= \App\Helpers\Csrf::field() ?>
                            <button class="btn btn-danger btn-sm" title="Deactivate">Delete</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>