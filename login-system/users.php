<?php
    require 'auth.php';
    require 'db.php';
    require_login();

    $admin  = is_admin();
    $me     = (int)$_SESSION['user_id'];
    $errors = [];

    // Checks username/password rules. $needPassword is true when creating an account.
    function validate_user($conn, $username, $password, $needPassword, $ignoreId) {
        $errs = [];
        if (!preg_match('/^[A-Za-z0-9_]{4,50}$/', $username)) {
            $errs[] = "Username must be 4-50 letters, numbers or underscores.";
        } else {
            $dup = fetch_all(run($conn, "SELECT id FROM users WHERE username = ? AND id <> ?", 'si', [$username, $ignoreId]));
            if ($dup) {
                $errs[] = "Username already exists.";
            }
        }
        if ($needPassword || $password !== '') {
            if (strlen($password) < 8) {
                $errs[] = "Password must be at least 8 characters.";
            }
        }
        return $errs;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action   = $_POST['action'] ?? '';
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!csrf_check($_POST['csrf'] ?? '')) {
            $errors[] = "Session expired. Please try again.";

        // CREATE (admin only)
        } elseif ($action === 'create' && $admin) {
            $role   = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';
            $errors = validate_user($conn, $username, $password, true, 0);
            if (!$errors) {
                run($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, ?)", 'sss',
                    [$username, password_hash($password, PASSWORD_DEFAULT), $role]);
                flash('success', 'User created.');
                header("Location: users.php");
                exit();
            }

        // UPDATE (admin: any user, regular user: only themselves)
        } elseif ($action === 'update') {
            $id  = $admin ? (int)($_POST['id'] ?? 0) : $me;
            $row = fetch_all(run($conn, "SELECT id, role FROM users WHERE id = ?", 'i', [$id]))[0] ?? null;
            if (!$row) {
                $errors[] = "User not found.";
            } else {
                $role = $row['role'];
                if ($admin && $id !== $me) {
                    $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';
                }
                $errors = validate_user($conn, $username, $password, false, $id);
                if (!$errors) {
                    if ($password !== '') {
                        run($conn, "UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?", 'sssi',
                            [$username, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
                    } else {
                        run($conn, "UPDATE users SET username = ?, role = ? WHERE id = ?", 'ssi', [$username, $role, $id]);
                    }
                    if ($id === $me) {
                        $_SESSION['username'] = $username;
                    }
                    flash('success', 'Account updated.');
                    header("Location: users.php");
                    exit();
                }
            }

        // DELETE (admin only, never yourself)
        } elseif ($action === 'delete' && $admin) {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === $me) {
                flash('danger', 'You cannot delete your own account.');
            } else {
                $stmt = run($conn, "DELETE FROM users WHERE id = ?", 'i', [$id]);
                flash(pg_affected_rows($stmt) > 0 ? 'success' : 'warning',
                      pg_affected_rows($stmt) > 0 ? 'User deleted.' : 'User not found.');
            }
            header("Location: users.php");
            exit();
        }
    }

    // READ
    $users = [];
    if ($admin) {
        $users = fetch_all(run($conn, "SELECT id, username, role, created_at FROM users ORDER BY role, username"));
    }
    $editId = $admin ? (int)($_GET['edit'] ?? 0) : $me;
    $edit   = $editId ? (fetch_all(run($conn, "SELECT id, username, role FROM users WHERE id = ?", 'i', [$editId]))[0] ?? null) : null;
    $flash  = flash();
?>
<html>
    <head>
        <title><?= $admin ? 'Manage Users' : 'My Profile' ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="style.css" rel="stylesheet">
    </head>
    <body>
        <?php include 'nav.php'; ?>

        <div id="users-page" class="container">
            <h1 class="page-title h3 mb-4"><?= $admin ? 'Manage Users' : 'My Profile' ?></h1>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger py-2"><?= e($err) ?></div>
            <?php endforeach; ?>
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> py-2"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div id="user-form" class="card shadow-sm card-body">
                        <h2 class="h5"><?= $edit ? 'Edit account' : 'Add user' ?></h2>
                        <form method="POST">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
                            <?php if ($edit): ?>
                                <input type="hidden" name="id" value="<?= e($edit['id']) ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username"
                                       value="<?= e($_POST['username'] ?? $edit['username'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Password <?= $edit ? '<small class="text-muted">(blank = keep current)</small>' : '' ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" <?= $edit ? '' : 'required' ?>>
                            </div>
                            <?php if ($admin && (!$edit || (int)$edit['id'] !== $me)): ?>
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role">
                                        <option value="user" <?= ($edit['role'] ?? '') === 'user' ? 'selected' : '' ?>>user</option>
                                        <option value="admin" <?= ($edit['role'] ?? '') === 'admin' ? 'selected' : '' ?>>admin</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary"><?= $edit ? 'Save changes' : 'Create user' ?></button>
                            <?php if ($admin && $edit): ?>
                                <a href="users.php" class="btn btn-link">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <?php if ($admin): ?>
                    <div class="col-lg-8">
                        <div class="card table-card shadow-sm">
                            <table id="users-table" class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>ID</th><th>Username</th><th>Role</th><th>Created</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><?= e($u['id']) ?></td>
                                            <td><?= e($u['username']) ?></td>
                                            <td><?= e($u['role']) ?></td>
                                            <td><?= e($u['created_at']) ?></td>
                                            <td class="text-end">
                                                <a href="users.php?edit=<?= e($u['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <?php if ((int)$u['id'] !== $me): ?>
                                                    <form method="POST" class="d-inline"
                                                          onsubmit="return confirm('Delete <?= e($u['username']) ?>?');">
                                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
