<?php
    $roleLabels = [ 1 => 'Admin', 2 => 'Approver', 3 => 'Staff', 4 => 'Dept. Head', 5 => 'Checker', 6 => 'Final Approver' ];
    $roleName = $roleLabels[$employee['role_id']] ?? 'User';
    $initials = strtoupper(substr($employee['full_name'], 0, 2));
    $updatedAt = $employee['updated_at'] ? date('M d, Y', strtotime($employee['updated_at'])) : '—'
?>

<link rel="stylesheet" href="/processing-system/public/stylesheets/profile.css">
<div class="page-header">
    <div class="page-title-group">
        <h5 class="page-heading">My Profile</h5>
        <p class="page-subheading">Manage your account details and login credentials.</p>
    </div>
    <span style="font-size:.75rem; color:var(--text-soft); display:flex; align-items:center; gap:5px;">
        <i class="ti ti-clock" style="font-size:13px"></i>
    </span>
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
            <div class="form-group"><label>Email</label><input type="email" value="<?= htmlspecialchars($employee['email']) ?>"></div>
            <div class="form-group"><label>Username</label><input type="text" name="username" value="<?= htmlspecialchars($employee['username'] ?? '') ?>"></div>
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