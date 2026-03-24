<div class="page-header">
    <h5>Profile</h5>
</div>

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

    <div class="form-card">
        <div class="form-section-title">Account Details</div>
        <div class="form-grid g-2">
            <div class="form-group"><label>Employee Code</label><input type="text" value="<?= htmlspecialchars($employee['employee_code']) ?>" disabled></div>
            <div class="form-group"><label>Email</label><input type="email" value="<?= htmlspecialchars($employee['email']) ?>" disabled></div>
            <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= htmlspecialchars($employee['full_name']) ?>" required></div>
            <div class="form-group"><label>Department</label><input type="text" name="department" value="<?= htmlspecialchars($employee['department'] ?? '') ?>"></div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section-title">Change Password <span class="muted section-hint">— leave blank to keep current</span></div>
        <div class="form-grid g-3">
            <div class="form-group"><label>Current Password</label><input type="password" name="current_password"></div>
            <div class="form-group"><label>New Password</label><input type="password" name="new_password"></div>
            <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password"></div>
        </div>
    </div>

    <button class="btn btn-primary">Save Changes</button>
</form>