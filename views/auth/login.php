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
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="/processing-system/public/stylesheets/auth.css" rel="stylesheet">
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
                <button type="button" class="toggle-icon" onclick="togglePassword()" aria-label="Toggle password visibility">
                    <i data-lucide="eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <div style="text-align: right; margin-bottom: 1rem;">
            <a href="/processing-system/public/forgot-password" style="font-size: 0.8rem; color: #64748b; text-decoration: none;">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary">Login</button>
    </form>

    <div class="divider"></div>

    <div class="form-footer">
        Don't have an account? <a href="/processing-system/public/register">Create an account</a>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();

    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            // Change icon to eye-off
            eyeIcon.setAttribute('data-lucide', 'eye-off');
        } else {
            passwordInput.type = 'password';
            // Change icon back to eye
            eyeIcon.setAttribute('data-lucide', 'eye');
        }
        
        // Re-render the icon
        lucide.createIcons();
    }
</script>

</body>
</html>