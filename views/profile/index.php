<?php
    $roleLabels = [ 1 => 'Admin', 2 => 'Approver', 3 => 'Staff', 4 => 'Dept. Head', 5 => 'Checker', 6 => 'Final Approver' ];
    $roleName = $roleLabels[$employee['role_id']] ?? 'User';
    $initials = strtoupper(substr($employee['full_name'], 0, 2));
    $updatedAt = $employee['updated_at'] ? date('M d, Y', strtotime($employee['updated_at'])) : '—'
?>

<?php $avatarUrl = !empty($employee['avatar']) ? htmlspecialchars($employee['avatar']) : null; ?>

<link rel="stylesheet" href="/processing-system/public/stylesheets/profile.css">
<div class="page-header">
    <div class="page-title-group">
        <h5 class="page-heading">My Profile</h5>
        <p class="page-subheading">Manage your account details and login credentials.</p>
    </div>
    <span style="font-size:.75rem; color:var(--text-soft); display:flex; align-items:center; gap:5px;">
        <i class="ti ti-clock" style="font-size:13px"></i>
        Last updated <?= $updatedAt ?>
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

<div class="profile-layout">

    <!-- Sidebar -->
    <aside class="profile-sidebar">
        
        <div class="profile-avatar-wrap">
            <?php if ($avatarUrl): ?>
                <img src="<?= $avatarUrl ?>" class="profile-avatar-img" alt="Profile picture">
            <?php else: ?>
                <div class="profile-avatar"><?= $initials ?></div>
            <?php endif; ?>

            <form method="POST" action="/processing-system/public/profile/avatar"
                enctype="multipart/form-data" id="avatar-form">
                <?= \App\Helpers\Csrf::field() ?>
                <input type="file" name="avatar" id="avatar-input"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    style="display:none"
                    onchange="document.getElementById('avatar-form').submit()">
                <button type="button" class="avatar-edit-btn"
                        onclick="document.getElementById('avatar-input').click()"
                        title="Change photo">
                    <i class="ti ti-camera"></i>
                </button>
            </form>
        </div>

        <div>
            <div class="profile-name"><?= htmlspecialchars($employee['full_name']) ?></div>
            <div class="profile-role"><?= $roleName ?></div>
            <div class="profile-code"><?= htmlspecialchars($employee['employee_code']) ?></div>
        </div>
        <span class="badge-active"><i class="ti ti-circle-check" style="font-size:11px"></i> Active</span>
        <div class="profile-divider"></div>
        <div class="profile-meta">
            <?php if (!empty($employee['department'])): ?>
                <div class="profile-meta-row"><i class="ti ti-building"></i> <?= htmlspecialchars($employee['department']) ?></div>
                <?php endif; ?>
                <div class="profile-meta-row"><i class="ti ti-mail"></i> <?= htmlspecialchars($employee['email']) ?></div>
                <?php if (!empty($employee['supervisor_name'])): ?>
                <div class="profile-meta-row"><i class="ti ti-user"></i> <?= htmlspecialchars($employee['supervisor_name']) ?></div>
                <?php endif; ?>
            <div class="profile-meta-column"><i class="ti  ti-user"></i></div>
        </div>
        <div class="profile-divider"></div>
        <nav class="profile-nav">
            <a href="#account" class="profile-nav-item active"><i class="ti ti-id-badge"></i> Account details</a>
            <a href="#password" class="profile-nav-item"><i class="ti ti-lock"></i> Password</a>
        </nav>
    </aside>

    <!-- Main -->
    <div class="profile-main">

        <!-- Account Details -->
        <div class="section-card" id="account">
            <form method="POST" action="/processing-system/public/profile">
                <?= \App\Helpers\Csrf::field() ?>
                <input type="hidden" name="section" value="account">

                <div class="section-head">
                    <div class="section-head-left"><i class="ti ti-user-circle"></i> Account Details</div>
                    <span class="section-hint">Fields marked * are required</span>
                </div>
                <div class="section-body">
                    <div class="fields-grid">
                        <div class="field-group">
                            <label class="field-label">Employee code</label>
                            <input type="text" value="<?= htmlspecialchars($employee['email']) ?>" disabled>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Email address</label>
                            <input type="email" value="<?= htmlspecialchars($employee['email']) ?>" disabled>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Full name *</label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($employee['full_name']) ?>" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">
                                Username
                                <span class="field-optional">optional</span>
                            </label>
                            <div class="field-input-wrap">
                                <input type="text" name="username" value="<?= htmlspecialchars($employee['username'] ?? '') ?>" placeholder="e.g. jdelacruz">
                                <i class="ti ti-at"></i>
                            </div>
                        </div>
                        <div class="field-group field-full">
                            <label class="field-label">Department</label>
                            <input type="text" name="department" value="<?= htmlspecialchars($employee['department'] ?? '') ?>" >
                        </div>
                    </div>
                </div>
                <div class="section-footer">
                    <span class="footer-hint">
                        <i class="ti ti-info-circle"></i>
                        Email and employee code cannot be changed here.
                    </span>
                    <div class="btn-row">
                        <a href="/processing-system/public/profile" class="btn btn-ghost">Discard</a>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save changes</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Password -->
        <div class="section-card" id="password">
            <form method="POST" action="/processing-system/public/profile">
                <?= \App\Helpers\Csrf::field() ?>
                <input type="hidden" name="section" value="password">

                <div class="section-head">
                    <div class="section-head-left"><i class="ti ti-lock"></i> Change password</div>
                    <span class="section-hint">Leave blank to keep current</span>
                </div>
                <div class="section-body">
                    <div class="field-grid">
                        <div class="field-group">
                            <label class="field-label">Current password</label>
                            <div class="field-input-wrap">
                                <input type="password" name="current_password" placeholder="••••••••">
                                <i clas="ti ti-eye"></i>
                            </div>
                        </div>
                        <div class="field-group"></div> <!-- spacer -->
                        <div class="field-group">
                            <label class="field-label">New password</label>
                            <div class="field-input-wrap">
                                <input type="password" name="new_password" placeholder="Min. 8 characters">
                                <i class="ti ti-eye"></i>
                            </div>
                            <span class="field-hint">At least 8 characters</span>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Confirm new password</label>
                            <div class="field-input-wrap">
                                <input type="password" name="confirm_password" placeholder="Repeat new password">
                                <i class="ti ti-eye"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-footer">
                    <span class="footer-hint">
                        <i class="ti ti-shield-check"></i>
                        Use a strong, unique password you don't reuse elsewhere.
                    </span>
                    <div class="btn-row">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-lock-check"></i> Update password</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/processing-system/public/scripts/profile.js"></script>