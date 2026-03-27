<?php
    $error = $_SESSION['error'] ?? null;
    $status = $_SESSION['status'] ?? null; 
    unset($_SESSION['error'], $_SESSION['status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password — Processing System</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="style.css" rel="stylesheet">
</head>
<body>

<div class="login-card">
    <div class="login-title">⚙ Reset Password</div>
    
    <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem; text-align: center;">
        Enter your email address and we'll send you a link to reset your password.
    </p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($status): ?>
        <div class="alert alert-success" style="color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
            <?= htmlspecialchars($status) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/processing-system/public/forgot-password">

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="name@email.com" required autofocus>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Send Reset Link</button>
    </form>

    <div class="divider"></div>

    <div class="form-footer">
        <a href="/login" style="display: flex; align-items: center; justify-content: center; gap: 5px; text-decoration: none; color: #64748b;">
            <i data-lucide="arrow-left" style="width: 16px;"></i> Back to Login
        </a>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();
</script>

</body>
</html>