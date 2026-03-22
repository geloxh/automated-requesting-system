<h4 class="mb-3">Add Employee</h4>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
<form method="POST" action="/processing-system/public/employees/create">
    <?= \App\Helpers\Csrf::field() ?>
    <div class="mb-3"><label>Employee Code</label><input class="form-control" name="employee_code" required></div>
    <div class="mb-3"><label>Full Name</label><input class="form-control" name="full_name" required></div>
    <div class="mb-3"><label>Email</label><input class="form-control" type="email" name="email" required></div>
    <div class="mb-3"><label>Password</label><input class="form-control" type="password" name="password" required></div>
    <div class="mb-3"><label>Role ID</label><input class="form-control" type="number" name="role_id" required></div>
    <div class="mb-3"><label>Department</label><input class="form-control" name="department"></div>
    <button class="btn btn-primary">Save</button>
    <a href="/processing-system/public/employees" class="btn btn-secondary">Cancel</a>
</form>