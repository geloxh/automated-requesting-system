<?php
    $registerError = $_SESSION['error'] ?? null;
    unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register — Processing System</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="/processing-system/public/stylesheets/auth.css" rel="stylesheet">
</head>

<body>
    <div class="register-card">
        <div class="register-title">⚙ Register - Processing System</div>

        <?php if ($registerError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($registerError) ?></div>
        <?php endif; ?>

        <form method="POST" action="/processing-system/public/register">

            <?= \App\Helpers\Csrf::field(); ?>

            <div class="form-group">
                <label for="firstname">First Name</label>
                <input type="text" id="firstname" name="firstname" required autofocus>
            </div>

            <div class="form-group">
                <label for="lastname">Last Name</label>
                <input type="text" id="lastname" name="lastname" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="text" id="email" name="email" placeholder="name@email.com" required>
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

            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <div class="divider"></div>

        <div class="form-footer">
            Already have an account? <a href="/processing-system/public/login">Login</a>
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