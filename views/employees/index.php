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
                        <select name="employment_status" onchange="this.form.submit()" class="form-select-sm">
                            <?php foreach (['employed', 'resigned', 'floating'] as $s): ?>
                                <option value="<?= $s ?>" <?= $e['employment_status'] === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td>
                    <?php if ($e['id'] != $_SESSION['user_id']): ?>
                    <form method="POST" action="/processing-system/public/employees/<?= $e['id'] ?>/delete"
                          onsubmit="return confirm('Delete <?= htmlspecialchars($e['full_name'], ENT_QUOTES) ?>?')">
                        <?= \App\Helpers\Csrf::field() ?>
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>