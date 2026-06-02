<div class="page-header">
    <div class="page-title-group">
        <h5 class="page-heading">Edit Employee</h5>
        <p class="page-subheading">Update profile and system permissions for <?= htmlspecialchars($employee['full_name']) ?></p>
    </div>
    <a href="/processing-system/public/employees" class="btn btn-ghost">
        <i class="ti ti-arrow-left"></i> Back to List
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="form-card">
    <form method="POST" action="/processing-system/public/employees/update/<?= $employee['id'] ?>">
        <?= \App\Helpers\Csrf::field() ?>
        
        <div class="form-grid g-2">
            <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= htmlspecialchars($employee['full_name']) ?>" required></div>
            <div class="form-group">
                <label>Username <span class="muted">(optional, used for login)</span></label>
                <input type="text" name="username" value="<?= htmlspecialchars($employee['username'] ?? '') ?>" placeholder="e.g. juan">
            </div>
            <div class="form-group"><label>Email Address</label><input type="email" name="email" value="<?= htmlspecialchars($employee['email']) ?>" required></div>
            <div class="form-group">
                <label>New Password <span class="muted">(Leave blank to keep current)</span></label>
                <input type="password" name="password" placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role_id" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>" <?= $employee['role_id'] == $role['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Department</label>
                <select name="department">
                    <option value="">-- Select --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['name']) ?>"
                            <?= $employee['department'] === $dept['name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Supervisor (Immediate Manager)</label>
                <select name="supervisor_id">
                    <option value="">-- None --</option>
                    <?php foreach ($supervisors as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $employee['supervisor_id'] == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group g-span-2">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" <?= $employee['is_active'] ? 'checked' : '' ?>> 
                    Account is active and can login
                </label>
            </div>
        </div>
        <div class="form-action">
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save Changes</button>
            <a href="/processing-system/public/employees" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>