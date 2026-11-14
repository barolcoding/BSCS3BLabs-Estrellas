<?php
    require 'auth.php';
    require 'db.php';
    require 'player.php';

    // Logged-in users cannot see the register page.
    if (!empty($_SESSION['user_id'])) {
        header("Location: dashboard.php");
        exit();
    }

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!csrf_check($_POST['csrf'] ?? '')) {
            $errors[] = "Session expired. Please try again.";
        }

        if (!preg_match('/^[A-Za-z0-9_]{4,50}$/', $username)) {
            $errors[] = "Username must be 4-50 letters, numbers or underscores.";
        }

        if ($password !== $confirm) {
            $errors[] = "Passwords did not match.";
        }
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        }

        if (!$errors) {
            $dup = fetch_all(run($conn, "SELECT id FROM users WHERE username = ?", 's', [$username]));
            if ($dup) {
                $errors[] = "Username already exists.";
            }
        }

        if (!$errors) {
            // Role is always 'user' here. Admins are created in setup.php or by another admin.
            $new = fetch_all(run($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, 'user') RETURNING id", 'ss',
                [$username, password_hash($password, PASSWORD_DEFAULT)]));
            ensure_stats($conn, (int)$new[0]['id']);   // player profile registration
            flash('success', 'Registered successfully. You can log in now.');
            header("Location: index.php");
            exit();
        }
    }
?>
<html>
    <head>
        <title>Tarbibi | Register</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="style.css" rel="stylesheet">
    </head>
    <body>
        <div id="auth-page" class="container-fluid">
            <div class="row min-vh-100">
                <div class="col-lg-6 d-none d-lg-flex auth-brand">
                    <div class="brand-logo mb-3"><i class="bi bi-shield-lock-fill"></i></div>
                    <div class="h2 fw-bold mb-4">Tarbibi</div>
                    <h1 class="display-5 fw-bold mb-3">Create your<br>account.</h1>
                    <p class="lead opacity-75">Join in a few seconds and start playing.</p>
                </div>

                <div class="col-lg-6 d-flex align-items-center justify-content-center p-4">
                    <div id="login-card">
                        <div id="brand-label" class="fw-bold text-uppercase text-primary mb-2">Tarbibi</div>
                        <h2 class="fw-bold mb-1">Register</h2>
                        <p class="text-muted mb-4">Fill in the details to create an account.</p>

                        <?php if ($errors): ?>
                            <div class="alert alert-danger py-2">
                                <ul class="mb-0 ps-3"><?php foreach ($errors as $err) echo '<li>' . e($err) . '</li>'; ?></ul>
                            </div>
                        <?php endif; ?>

                        <form id="register-form" method="POST">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <label for="username" class="form-label fw-medium">Username</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="username" name="username"
                                       value="<?= e($_POST['username'] ?? '') ?>" placeholder="Choose a username" required autofocus>
                            </div>
                            <label for="password" class="form-label fw-medium">Password</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="At least 8 characters" required>
                            </div>
                            <label for="confirm_password" class="form-label fw-medium">Confirm password</label>
                            <div class="input-group mb-4">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required>
                            </div>
                            <button type="submit" class="btn btn-brand w-100">Register</button>
                        </form>
                        <p class="text-center text-muted mt-3 mb-0">Already registered? <a href="index.php">Log in</a></p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
