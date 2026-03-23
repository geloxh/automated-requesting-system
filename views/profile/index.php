<h4 class="mb-3">Profile</h4>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<form method="POST" action="/processing-system/public/profile">
    <?= \App\Helpers\Csrf::field() ?>
    <div class="mb-3">
        <label>Employee Code</label>
        <input class="form-control" value="<?= htmlspecialchars($employee['employee_code']) ?>" disabled>
    </div>
    <div class="mb-3">
        <label>Full Name</label>
        <input class="form-control" name="full_name" value="<?= htmlspecialchars($employee['full_name']) ?>" required>
    </div>
    <div class="mb-3">
        <label>Email</label>
        <input class="form-control" value="<?= htmlspecialchars($employee['email']) ?>" disabled>
    </div>
    <div class="mb-3">
        <label>Department</label>
        <input class="form-control" name="department" value="<?= htmlspecialchars($employee['department'] ?? '')?>">
    </div>

    <hr>
    <p class="text-muted small">Leave password fields blank to keep current password.</p>
    <div class="mb-3">
        <label>Current Password</label>
        <input class="form-control" type="password" name="current_password">
    </div>
    <div class="mb-3">
        <label>Current Password</label>
        <input class="form-control" type="password" name="current_password">
    </div>
    <div class="mb-3">
        <label>New Password</label>
        <input class="form-control" type="password" name="new_password">
    </div>

    <button class="btn btn-primary">Save Changes</button>
</form>