<?php
    require 'auth.php';
    require 'db.php';

    // Active session -> dashboard. Login page is only reachable again through logout.
    if (!empty($_SESSION['user_id'])) {
        header("Location: dashboard.php");
        exit();
    }

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!csrf_check($_POST['csrf'] ?? '')) {
            $errors[] = "Session expired. Please try again.";
        } elseif ($username === '' || $password === '') {
            $errors[] = "Username and password cannot be empty.";
        } else {
            $stmt = run($conn, "SELECT id, username, password, role FROM users WHERE username = ?", 's', [$username]);
            $user = fetch_all($stmt)[0] ?? null;

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];
            // CREATE: record this login session.
            run($conn, "INSERT INTO login_sessions (user_id, ip_address, user_agent) VALUES (?, ?, ?)", 'iss',
                [$user['id'], $_SERVER['REMOTE_ADDR'] ?? '', substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)]);
                header("Location: dashboard.php");
                exit();
            }
            $errors[] = "Incorrect username or password.";
        }
    }
    $flash = flash();
?>
<html>
    <head>
        <title>Tarbibi | Log in</title>
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
                    <h1 class="display-5 fw-bold mb-3">Secure access,<br>simply managed.</h1>
                    <p class="lead opacity-75 mb-4">A vertical shooter for players, with admins managing the accounts.</p>
                    <ul class="brand-points list-unstyled">
                        <li><i class="bi bi-check-circle-fill me-2"></i>Role-based access for admins and users</li>
                        <li><i class="bi bi-check-circle-fill me-2"></i>Hashed passwords and protected sessions</li>
                        <li><i class="bi bi-check-circle-fill me-2"></i>Powered by Supabase</li>
                    </ul>
                </div>

                <div class="col-lg-6 d-flex align-items-center justify-content-center p-4">
                    <div id="login-card">
                        <div id="brand-label" class="fw-bold text-uppercase text-primary mb-2">Tarbibi</div>
                        <h2 class="fw-bold mb-1">Welcome back</h2>
                        <p class="text-muted mb-4">Log in to continue to your dashboard.</p>

                        <?php foreach ($errors as $err): ?>
                            <div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-2"></i><?= e($err) ?></div>
                        <?php endforeach; ?>
                        <?php if ($flash): ?>
                            <div class="alert alert-<?= e($flash['type']) ?> py-2"><?= e($flash['message']) ?></div>
                        <?php endif; ?>

                        <form id="login-form" method="POST">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <label for="username" class="form-label fw-medium">Username</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="username" name="username"
                                       value="<?= e($_POST['username'] ?? '') ?>" placeholder="Enter your username" required autofocus>
                            </div>
                            <label for="password" class="form-label fw-medium">Password</label>
                            <div class="input-group mb-4">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                <button type="button" id="toggle-password" class="btn btn-outline-secondary" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <button type="submit" class="btn btn-brand w-100">Log in</button>
                        </form>
                        <p class="text-center text-muted mt-3 mb-0">New here? <a href="signup.php">Create an account</a></p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('toggle-password').addEventListener('click', function () {
                var field = document.getElementById('password');
                var show = field.type === 'password';
                field.type = show ? 'text' : 'password';
                this.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            });
        </script>
    </body>
</html>
