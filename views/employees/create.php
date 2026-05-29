<div class="page-header">
    <h5>Add Employee</h5>
</div>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="form-card">
    <form method="POST" action="/processing-system/public/employees/create">

        <?= \App\Helpers\Csrf::field() ?>
        
        <div class="form-grid g-2">
            <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
            <div class="form-group">
                <label>Username <small class="text-muted">(optional)</small></label>
                <input type="text" name="username" placeholder="e.g. jdelacruz">
            </div>
            <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <div class="form-group">
                <label>Role</label>
                <select name="role_id" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Department</label><input type="text" name="department"></div>
            <div class="form-group">
                <label>Supervisor (Immediate Manager)</label>
                <select name="supervisor_id">
                    <option value="">-- None --</option>
                    <?php foreach ($supervisors as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Optional. Usually required for Staff to route approvals.</small>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-send"></i> Submit for Approval
            </button>
            <a href="javascript:history.back()" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>