<?php
    // Single navigation file. Every page includes it with a PHP include statement.
    $current = basename($_SERVER['SCRIPT_NAME']);
    $links = [
        'dashboard.php' => 'Play',
        'progress.php'  => 'Progress',
        'users.php'     => is_admin() ? 'Manage Users' : 'My Profile',
    ];
?>
<nav id="main-nav" class="navbar navbar-expand-md navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-shield-lock-fill me-2"></i>Tarbibi</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav-links">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="nav-links" class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <?php foreach ($links as $file => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current === $file ? 'active' : '' ?>" href="<?= $file ?>"><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <span class="navbar-text me-3">
                <?= e($_SESSION['username']) ?>
                <span class="badge <?= is_admin() ? 'text-bg-light' : 'text-bg-secondary' ?>"><?= e($_SESSION['role']) ?></span>
            </span>
            <a href="logout.php" id="logout-btn" class="btn btn-sm btn-outline-light">Log out</a>
        </div>
    </div>
</nav>
