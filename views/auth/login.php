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
    <title>Login — Processing System</title>
    <link href="/processing-system/public/stylesheets/auth.css" rel="stylesheet">
</head>
<body>

<div class="cont <?= $showSignup ? 's--signup' : '' ?>">

    <!-- Sign In -->
    <div class="form sign-in">
        <h2>Welcome Back</h2>

        <?php if ($success): ?>
            <p class="form-success" role="alert"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <?php if ($loginError): ?>
            <p class="form-error" role="alert"><?= htmlspecialchars($loginError) ?></p>
        <?php endif; ?>

        <form method="POST" action="/processing-system/public/login">
            <?= \App\Helpers\Csrf::field() ?>
            <label for="login_email">
                <span>Email</span>
                <input type="email" id="login_email" name="email"
                       value="<?= htmlspecialchars($oldEmail) ?>" required autofocus>
            </label>
            <label for="login_password">
                <span>Password</span>
                <div class="password-wrapper">
                    <input type="password" id="login_password" name="password" required>
                    <button type="button" class="toggle-icon" id="toggleBtn" aria-label="Toggle password visibility">
                        <i data-lucide="eye" id="eyeIcon"></i>
                    </button>
                </div>
            </label>
            <p class="forgot-pass"><a href="/processing-system/public/forgot-password">Forgot password?</a></p>
            <button type="submit" class="submit">Sign In</button>
        </form>
    </div>

    <!-- Sliding Panel -->
    <div class="sub-cont">
        <div class="img">
            <div class="img__text m--up">
                <h3>Don't have an account? Please sign up!</h3>
            </div>
            <div class="img__text m--in">
                <h3>Already have an account? Sign in.</h3>
            </div>
            <div class="img__btn" role="button" aria-label="Toggle between sign in and sign up">
                <span class="m--up">Sign Up</span>
                <span class="m--in">Sign In</span>
            </div>
        </div>

        <!-- Sign Up -->
        <div class="form sign-up">
            <h2>Create Account</h2>

            <?php if ($registerError): ?>
                <p class="form-error" role="alert"><?= htmlspecialchars($registerError) ?></p>
            <?php endif; ?>

            <form method="POST" action="/processing-system/public/register">
                <?= \App\Helpers\Csrf::field() ?>
                <label for="reg_firstname">
                    <span>First Name</span>
                    <input type="text" id="reg_firstname" name="firstname"
                           value="<?= htmlspecialchars($oldRegister['firstname'] ?? '') ?>" required>
                </label>
                <label for="reg_lastname">
                    <span>Last Name</span>
                    <input type="text" id="reg_lastname" name="lastname"
                           value="<?= htmlspecialchars($oldRegister['lastname'] ?? '') ?>" required>
                </label>
                <label for="reg_email">
                    <span>Email</span>
                    <input type="email" id="reg_email" name="email"
                           value="<?= htmlspecialchars($oldRegister['email'] ?? '') ?>" required>
                </label>
                <label for="reg_password">
                    <span>Password</span>
                    <div class="password-wrapper">
                        <input type="password" id="reg_password" name="password"
                               autocomplete="new-password" required>
                        <button type="button" class="toggle-icon" id="toggleBtnReg" aria-label="Toggle password visibility">
                            <i data-lucide="eye" id="eyeIconReg"></i>
                        </button>
                    </div>
                </label>
                <label for="reg_confirm">
                    <span>Confirm Password</span>
                    <div class="password-wrapper">
                        <input type="password" id="reg_confirm" name="password_confirmation"
                               autocomplete="new-password" required>
                        <button type="button" class="toggle-icon" id="toggleBtnConfirm" aria-label="Toggle confirm password visibility">
                            <i data-lucide="eye" id="eyeIconConfirm"></i>
                        </button>
                    </div>
                </label>
                <button type="submit" class="submit">Sign Up</button>
            </form>
        </div>
    </div>
</div>

<script src="/processing-system/public/scripts/lucide.min.js"></script>
<script src="/processing-system/public/scripts/auth.js"></script>
</body>
</html>