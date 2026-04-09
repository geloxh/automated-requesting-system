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
            <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <div class="form-group">
                <label>Role</label>
                <select name="role_id" required>
                    <option value="">-- Select --</option>
                    <option value="1">Admin</option>
                    <option value="2">Approver</option>
                    <option value="3">Staff</option>
                </select>
            </div>
            <div class="form-group"><label>Department</label><input type="text" name="department"></div>
        </div>
        <div class="action-btns mt-1">
            <button class="btn btn-primary">Save</button>
            <a href="/processing-system/public/employees" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>