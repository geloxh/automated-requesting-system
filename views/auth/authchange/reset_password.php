<?php
    $token = $_GET['token'] ?? '';
    
    $error = $_SESSION['error'] ?? null;
    $status = $_SESSION['status'] ?? null;
    unset($_SESSION['error'], $_SESSION['status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create New Password — Processing System</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="/processing-system/public/stylesheets/auth.css" rel="stylesheet">
</head>
<body>

<div class="reset-password-card">
    <div class="reset-password-title">⚙ Create New Password</div>
    
    <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem; text-align: center;">
        Please enter your new password below to secure your account.
    </p>

    <?php if ($error): ?>
        <div class="alert alert-danger" 
        style="color: #721c24; background-color: #f8d7da; 
        padding: 10px; border-radius: 4px; margin-bottom: 1rem; border: 1px solid #f5c6cb;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/processing-system/public/update-password">

        <?= \App\Helpers\Csrf::field() ?>
        
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="form-group">
            <label for="password">New Password</label>
            <div style="position: relative;">
                <input type="password" id="password" name="password" placeholder="••••••••" required autofocus style="width: 100%;">
            </div>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
            <label for="password_confirmation">Confirm New Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem; width: 100%;">
            Update Password
        </button>
    </form>

    <div class="divider"></div>

    <div class="form-footer">
        <a href="/processing-system/public/login" style="display: flex; align-items: center; justify-content: center; gap: 5px; text-decoration: none; color: #64748b;">
            <i data-lucide="x-circle" style="width: 16px;"></i> Cancel and Login
        </a>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();
</script>

</body>
</html>