<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Employees</h4>
    <a href="/processing-system/public/employees/create" class="btn btn-primary btn-sm">+ Add Employee</a>
</div>
<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>Code</th><th>Name</th><th>Email</th><th>Department</th><th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($employees as $e): ?>
        <tr>
            <td><?= htmlspecialchars($e['employee_code']) ?></td>
            <td><?= htmlspecialchars($e['full_name']) ?></td>
            <td><?= htmlspecialchars($e['email']) ?></td>
            <td><?= htmlspecialchars($e['department']) ?></td>
            <td><?= $e['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>