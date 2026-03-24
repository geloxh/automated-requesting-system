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
    <link rel="stylesheet" href="/processing-system/public/stylesheets/app.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-title">⚙ Processing System</div>
        <?php if ($loginError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        <form method="POST" action="/processing-system/public/login">
            <?= \App\Helpers\Csrf::field(); ?>
            <div class="form-group mt-1">
                <label>Email</label>
                <input type="email" name="email" required autofocus>
            </div>
            <div class="form-group mt-1-25">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button class="btn btn-primary btn-full">Login</button>
        </form>
    </div>
</div>
</body>
</html>