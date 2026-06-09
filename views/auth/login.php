<?php
    $loginError = $_SESSION['login_error']    ?? null;
    $registerError = $_SESSION['register_error'] ?? null;
    $showSignup = isset($_SESSION['show_signup']);
    $oldEmail = $_SESSION['old_email']      ?? '';
    $oldRegister = $_SESSION['old_register']   ?? [];
    $success = $_SESSION['success']        ?? null;
    unset($_SESSION['login_error'], $_SESSION['register_error'],
          $_SESSION['show_signup'], $_SESSION['old_email'],
          $_SESSION['old_register'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — ARS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="/automated-requesting-system/public/stylesheets/auth.css" rel="stylesheet">
</head>

<body>

<div class="auth-wrap">

    <!-- Left branding panel -->
    <div class="auth-panel">
        <div class="auth-brand-icon">⚡</div>
        <div class="auth-brand-name">ARS</div>
        <div class="auth-brand-tag">Automated Requesting System</div>
        <p class="auth-panel-desc">Paperless forms, multi-level approvals, and real-time tracking — all in one place.</p>
        <ul class="auth-features">
            <li>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Advance Payment & Reimbursement
            </li>
            <li>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Leave & Overtime Authorization
            </li>
            <li>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Multi-level Approval Workflow
            </li>
        </ul>
        <p class="dev-sys">&copy; 2026 &middot; <span class="dev-name">geloxh</span></p>
    </div>

    <!-- Right form panel -->
    <div class="auth-form-panel">

        <!-- Tab switcher -->
        <div class="auth-tabs" role="tablist">
            <button class="auth-tab <?= !$showSignup ? 'active' : '' ?>" data-tab="signin" role="tab">Sign In</button>
            <button class="auth-tab <?= $showSignup  ? 'active' : '' ?>" data-tab="signup" role="tab">Sign Up</button>
        </div>

        <!-- Sign In pane -->
        <div class="auth-pane <?= !$showSignup ? 'active' : '' ?>" id="pane-signin">
            <div class="auth-title">Welcome back</div>
            <div class="auth-subtitle">Sign in to your ARS account</div>

            <?php if ($success): ?>
                <p class="form-success" role="alert"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>
            <?php if ($loginError): ?>
                <p class="form-error" role="alert"><?= htmlspecialchars($loginError) ?></p>
            <?php endif; ?>

            <form method="POST" action="/automated-requesting-system/public/login">
                <?= \App\Helpers\Csrf::field() ?>
                <div class="auth-field">
                    <label for="login_email">Email or Username</label>
                    <input type="text" id="login_email" name="email"
                           value="<?= htmlspecialchars($oldEmail) ?>"
                           required autofocus autocomplete="username"
                           placeholder="you@company.com">
                </div>
                <div class="auth-field">
                    <label for="login_password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="login_password" name="password"
                               required autocomplete="current-password" placeholder="••••••••">
                        <button type="button" class="toggle-icon" id="toggleBtn" aria-label="Toggle password visibility">
                            <i data-lucide="eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="auth-forgot">
                    <a href="/automated-requesting-system/public/forgot-password">Forgot password?</a>
                </div>
                <button type="submit" class="auth-btn">Sign In</button>
            </form>
        </div>

        <!-- Sign Up pane -->
        <div class="auth-pane <?= $showSignup ? 'active' : '' ?>" id="pane-signup">
            <div class="auth-title">Create account</div>
            <div class="auth-subtitle">Register to access ARS</div>

            <?php if ($registerError): ?>
                <p class="form-error" role="alert"><?= htmlspecialchars($registerError) ?></p>
            <?php endif; ?>

            <form method="POST" action="/automated-requesting-system/public/register">
                <?= \App\Helpers\Csrf::field() ?>
                <div class="auth-field-row">
                    <div class="auth-field">
                        <label for="reg_firstname">First Name</label>
                        <input type="text" id="reg_firstname" name="firstname"
                               value="<?= htmlspecialchars($oldRegister['firstname'] ?? '') ?>" required>
                    </div>
                    <div class="auth-field">
                        <label for="reg_lastname">Last Name</label>
                        <input type="text" id="reg_lastname" name="lastname"
                               value="<?= htmlspecialchars($oldRegister['lastname'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="auth-field">
                    <label for="reg_email">Email</label>
                    <input type="email" id="reg_email" name="email"
                           value="<?= htmlspecialchars($oldRegister['email'] ?? '') ?>"
                           required placeholder="you@company.com">
                </div>
                <div class="auth-field">
                    <label for="reg_password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="reg_password" name="password"
                               autocomplete="new-password" required placeholder="Min. 8 characters">
                        <button type="button" class="toggle-icon" id="toggleBtnReg" aria-label="Toggle password visibility">
                            <i data-lucide="eye" id="eyeIconReg"></i>
                        </button>
                    </div>
                </div>
                <div class="auth-field">
                    <label for="reg_confirm">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="reg_confirm" name="password_confirmation"
                               autocomplete="new-password" required placeholder="Repeat password">
                        <button type="button" class="toggle-icon" id="toggleBtnConfirm" aria-label="Toggle confirm password visibility">
                            <i data-lucide="eye" id="eyeIconConfirm"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="auth-btn">Create Account</button>
            </form>
        </div>

    </div>
</div>

<script src="/automated-requesting-system/public/scripts/lucide.min.js"></script>
<script src="/automated-requesting-system/public/scripts/auth.js"></script>
</body>
</html>