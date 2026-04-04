<?php
    $loginError = $_SESSION['login_error'] ?? null;
    $registerError = $_SESSION['register_error'] ?? null;
    unset($_SESSION['login_error'], $_SESSION['register_error']);
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

<div class="cont <?= isset($_SESSION['show_signup']) ? 's--signup' : '' ?>">

    <!-- Sign In Form -->
    <div class="form sign-in">
        <h2>Welcome Back</h2>

        <?php if ($loginError): ?>
            <p style="color:red;text-align:center;font-size:13px;"><?= htmlspecialchars($loginError) ?></p>
        <?php endif; ?>

        <form method="POST" action="/processing-system/public/login">
            <?= \App\Helpers\Csrf::field() ?>
            <label>
                <span>Email</span>
                <input type="email" name="email" required autofocus>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required>
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
            <div class="img__btn">
                <span class="m--up">Sign Up</span>
                <span class="m--in">Sign In</span>
            </div>
        </div>

        <!-- Sign Up Form -->
        <div class="form sign-up">
            <h2>Create Account</h2>

            <?php if ($registerError): ?>
                <p style="color:red;text-align:center;font-size:13px;"><?= htmlspecialchars($registerError) ?></p>
            <?php endif; ?>

            <form method="POST" action="/processing-system/public/register">
                <?= \App\Helpers\Csrf::field() ?>
                <label>
                    <span>First Name</span>
                    <input type="text" name="firstname" required>
                </label>
                <label>
                    <span>Last Name</span>
                    <input type="text" name="lastname" required>
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" required>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" required>
                </label>
                <label>
                    <span>Confirm Password</span>
                    <input type="password" name="password_confirmation" required>
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