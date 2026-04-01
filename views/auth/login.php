<?php
    $loginError = $_SESSION['error'] ?? null;
    unset($_SESSION['error']);
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
    <div class="login-card">
        <div class="login-title">⚙ Login - Processing System</div>

        <?php if ($loginError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>

        <form method="POST" action="/processing-system/public/login">
            
            <?= \App\Helpers\Csrf::field(); ?>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="name@email.com" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <button type="button" class="toggle-icon" id="toggleBtn" aria-label="Toggle password visibility">
                        <i data-lucide="eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="forgot-wrap">
                <a href="/processing-system/public/forgot-password">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="divider"></div>

        <div class="form-footer">
            Don't have an account? <a href="/processing-system/public/register">Create an account</a>
        </div>
    </div>
    <script src="/processing-system/public/scripts/lucide.min.js"></script>
    <script src="/processing-system/public/scripts/auth.js"></script>
</body>
</html>