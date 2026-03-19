<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow p-4" style="width:360px">
        <h5 class="mb-3 text-center">Processing System</h5>
        <form method="POST" action="/processing-system/public/login">
            \App\Helpers\Csrf::field();
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Login</button>
        </form>
    </div>
    
</body>
</html>
