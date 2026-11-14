<?php
    require 'auth.php';
    require 'db.php';
    require 'player.php';
    require_login();

    $admin = is_admin();
    $uid   = (int)$_SESSION['user_id'];
    ensure_stats($conn, $uid);

    // Whitelist of upgrades: form value => [column, label]
    $upgrades = [
        'hp'    => ['hp_level',    'Max health'],
        'speed' => ['speed_level', 'Move speed'],
        'fire'  => ['fire_level',  'Fire rate'],
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if (!csrf_check($_POST['csrf'] ?? '')) {
            flash('danger', 'Session expired. Please try again.');

        // UPDATE: spend coins to upgrade a stat
        } elseif ($action === 'upgrade' && isset($upgrades[$_POST['stat'] ?? ''])) {
            $col   = $upgrades[$_POST['stat']][0];
            $level = (int)get_stats($conn, $uid)[$col];
            $cost  = upgrade_cost($level);
            $stmt  = run($conn, "UPDATE player_stats SET coins = coins - ?, $col = $col + 1, updated_at = CURRENT_TIMESTAMP
                                 WHERE user_id = ? AND coins >= ? AND $col < ?", 'iiii', [$cost, $uid, $cost, MAX_LEVEL]);
            if (pg_affected_rows($stmt) > 0) {
                flash('success', $upgrades[$_POST['stat']][1] . ' upgraded.');
                award($conn, $uid, 'first_upgrade');
                if ($level + 1 >= MAX_LEVEL) {
                    award($conn, $uid, 'max_upgrade');
                }
            } else {
                flash('warning', 'Not enough coins, or already at max level.');
            }

        // DELETE: reset account progress (admins can reset any player)
        } elseif ($action === 'reset') {
            $target = ($admin && !empty($_POST['user_id'])) ? (int)$_POST['user_id'] : $uid;
            run($conn, "UPDATE player_stats SET coins = 0, high_score = 0, hp_level = 0, speed_level = 0, fire_level = 0, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?", 'i', [$target]);
            run($conn, "DELETE FROM game_logs WHERE user_id = ?", 'i', [$target]);
            run($conn, "DELETE FROM achievements WHERE user_id = ?", 'i', [$target]);
            flash('success', 'Progress reset.');

        // DELETE: clear old history logs, keeping the latest 10 runs
        } elseif ($action === 'clear_logs') {
            if ($admin) {
                run($conn, "DELETE FROM game_logs WHERE id NOT IN (
                                SELECT id FROM (SELECT id, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY played_at DESC, id DESC) AS rn
                                                FROM game_logs) t WHERE rn <= 10)");
            } else {
                run($conn, "DELETE FROM game_logs WHERE user_id = ? AND id NOT IN (
                                SELECT id FROM game_logs WHERE user_id = ? ORDER BY played_at DESC, id DESC LIMIT 10)", 'ii', [$uid, $uid]);
            }
            flash('success', 'Old logs cleared. The latest 10 runs per player were kept.');

        // DELETE: clear old login sessions, keeping the latest 5
        } elseif ($action === 'clear_sessions') {
            if ($admin) {
                run($conn, "DELETE FROM login_sessions WHERE id NOT IN (
                                SELECT id FROM (SELECT id, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY logged_in_at DESC, id DESC) AS rn
                                                FROM login_sessions) t WHERE rn <= 5)");
            } else {
                run($conn, "DELETE FROM login_sessions WHERE user_id = ? AND id NOT IN (
                                SELECT id FROM login_sessions WHERE user_id = ? ORDER BY logged_in_at DESC, id DESC LIMIT 5)", 'ii', [$uid, $uid]);
            }
            flash('success', 'Old sessions cleared. The latest 5 per player were kept.');
        }
        header("Location: progress.php");
        exit();
    }

    // READ
    $stats = get_stats($conn, $uid);

    $leaders = fetch_all(run($conn, "SELECT u.username, s.high_score AS best, s.coins
                                     FROM player_stats s JOIN users u ON u.id = s.user_id
                                     WHERE s.high_score > 0 ORDER BY s.high_score DESC, u.username LIMIT 10"));

    // READ: this player's unlocked achievements (code => date)
    $myAch = [];
    foreach (fetch_all(run($conn, "SELECT code, unlocked_at FROM achievements WHERE user_id = ?", 'i', [$uid])) as $row) {
        $myAch[$row['code']] = $row['unlocked_at'];
    }

    // READ: login sessions (admins see everyone's)
    if ($admin) {
        $sessions = fetch_all(run($conn, "SELECT u.username, l.logged_in_at, l.ip_address FROM login_sessions l
                                          JOIN users u ON u.id = l.user_id ORDER BY l.logged_in_at DESC, l.id DESC LIMIT 15"));
    } else {
        $sessions = fetch_all(run($conn, "SELECT u.username, l.logged_in_at, l.ip_address FROM login_sessions l
                                          JOIN users u ON u.id = l.user_id WHERE l.user_id = ?
                                          ORDER BY l.logged_in_at DESC, l.id DESC LIMIT 15", 'i', [$uid]));
    }

    if ($admin) {
        $history = fetch_all(run($conn, "SELECT g.score, g.coins_earned, g.played_at, u.username
                                         FROM game_logs g JOIN users u ON u.id = g.user_id
                                         ORDER BY g.played_at DESC, g.id DESC LIMIT 15"));
        $players = fetch_all(run($conn, "SELECT u.id, u.username, s.coins, s.hp_level, s.speed_level, s.fire_level
                                         FROM users u LEFT JOIN player_stats s ON s.user_id = u.id
                                         WHERE u.role = 'user' ORDER BY u.username"));
    } else {
        $history = fetch_all(run($conn, "SELECT g.score, g.coins_earned, g.played_at, u.username
                                         FROM game_logs g JOIN users u ON u.id = g.user_id
                                         WHERE g.user_id = ? ORDER BY g.played_at DESC, g.id DESC LIMIT 15", 'i', [$uid]));
    }
    $flash = flash();
?>
<html>
    <head>
        <title>Tarbibi | Progress</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="style.css" rel="stylesheet">
    </head>
    <body>
        <?php include 'nav.php'; ?>

        <div id="progress-page" class="container pb-5">
            <h1 class="page-title h3 mb-4">Progress</h1>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> py-2"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-lg-5">
                    <div id="inventory" class="panel-card card-body mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 fw-bold mb-0">Inventory</h2>
                            <span class="fw-bold"><i class="bi bi-coin me-1"></i><?= e($stats['coins']) ?> coins</span>
                        </div>

                        <?php foreach ($upgrades as $key => [$col, $label]): ?>
                            <?php $level = (int)$stats[$col]; $maxed = $level >= MAX_LEVEL; ?>
                            <div class="upgrade-row mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span><?= e($label) ?></span><span>Lv <?= $level ?>/<?= MAX_LEVEL ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:8px">
                                        <div class="progress-bar bg-dark" style="width: <?= $level / MAX_LEVEL * 100 ?>%"></div>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="upgrade">
                                        <input type="hidden" name="stat" value="<?= e($key) ?>">
                                        <button class="btn btn-sm btn-outline-dark" <?= $maxed ? 'disabled' : '' ?>>
                                            <?= $maxed ? 'Max' : 'Upgrade (' . upgrade_cost($level) . ')' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="achievements" class="panel-card card-body mb-3">
                        <h2 class="h5 fw-bold">Achievements <small class="text-muted fs-6"><?= count($myAch) ?>/<?= count(ACHIEVEMENTS) ?></small></h2>
                        <ul class="list-unstyled small mb-0">
                            <?php foreach (ACHIEVEMENTS as $code => [$title, $desc]): ?>
                                <?php $got = isset($myAch[$code]); ?>
                                <li class="py-1 <?= $got ? '' : 'text-muted' ?>">
                                    <i class="bi bi-<?= $got ? 'trophy-fill' : 'lock' ?> me-2"></i><strong><?= e($title) ?></strong> &ndash; <?= e($desc) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div id="danger-zone" class="panel-card card-body">
                        <h2 class="h6 fw-bold">Reset progress</h2>
                        <p class="small text-muted">Sets coins, upgrades and high score back to zero, and deletes your run history and achievements.</p>
                        <form method="POST" onsubmit="return confirm('Reset all your progress? This cannot be undone.');">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="reset">
                            <button class="btn btn-sm btn-outline-danger">Reset my progress</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div id="leaderboard" class="table-card mb-3">
                        <div class="p-3 pb-2"><h2 class="h5 fw-bold mb-0">Leaderboard</h2></div>
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th class="ps-3">#</th><th>Player</th><th>High score</th><th>Coins</th></tr></thead>
                            <tbody>
                                <?php foreach ($leaders as $i => $l): ?>
                                    <tr class="<?= $l['username'] === $_SESSION['username'] ? 'table-active' : '' ?>">
                                        <td class="ps-3"><?= $i + 1 ?></td>
                                        <td><?= e($l['username']) ?></td>
                                        <td><?= e($l['best']) ?></td>
                                        <td><?= e($l['coins']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$leaders): ?>
                                    <tr><td colspan="4" class="text-muted ps-3">No runs yet. Play a game to get on the board.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="history" class="table-card">
                        <div class="p-3 pb-2 d-flex justify-content-between align-items-center">
                            <h2 class="h5 fw-bold mb-0"><?= $admin ? 'Recent runs (all players)' : 'Your recent runs' ?></h2>
                            <form method="POST" onsubmit="return confirm('Delete old logs? The latest 10 runs per player are kept.');">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="clear_logs">
                                <button class="btn btn-sm btn-outline-dark">Clear old history logs</button>
                            </form>
                        </div>
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th class="ps-3">Player</th><th>Score</th><th>Coins</th><th>Played</th></tr></thead>
                            <tbody>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td class="ps-3"><?= e($h['username']) ?></td>
                                        <td><?= e($h['score']) ?></td>
                                        <td>+<?= e($h['coins_earned']) ?></td>
                                        <td class="text-muted"><?= e(date('M j, g:i a', strtotime($h['played_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$history): ?>
                                    <tr><td colspan="4" class="text-muted ps-3">Nothing saved yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="sessions" class="table-card mt-3">
                        <div class="p-3 pb-2 d-flex justify-content-between align-items-center">
                            <h2 class="h5 fw-bold mb-0"><?= $admin ? 'Login sessions (all players)' : 'Your login sessions' ?></h2>
                            <form method="POST" onsubmit="return confirm('Delete old sessions? The latest 5 per player are kept.');">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="clear_sessions">
                                <button class="btn btn-sm btn-outline-dark">Clear old sessions</button>
                            </form>
                        </div>
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th class="ps-3">Player</th><th>Logged in</th><th>IP address</th></tr></thead>
                            <tbody>
                                <?php foreach ($sessions as $se): ?>
                                    <tr>
                                        <td class="ps-3"><?= e($se['username']) ?></td>
                                        <td><?= e(date('M j, g:i a', strtotime($se['logged_in_at']))) ?></td>
                                        <td class="text-muted"><?= e($se['ip_address']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$sessions): ?>
                                    <tr><td colspan="3" class="text-muted ps-3">No sessions recorded yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($admin): ?>
                    <div class="col-12">
                        <div id="players-table" class="table-card">
                            <div class="p-3 pb-2"><h2 class="h5 fw-bold mb-0">All players</h2></div>
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th class="ps-3">Player</th><th>Coins</th><th>Health</th><th>Speed</th><th>Fire rate</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($players as $pl): ?>
                                        <tr>
                                            <td class="ps-3"><?= e($pl['username']) ?></td>
                                            <td><?= e($pl['coins'] ?? 0) ?></td>
                                            <td>Lv <?= e($pl['hp_level'] ?? 0) ?></td>
                                            <td>Lv <?= e($pl['speed_level'] ?? 0) ?></td>
                                            <td>Lv <?= e($pl['fire_level'] ?? 0) ?></td>
                                            <td class="text-end pe-3">
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Reset this player\'s progress?');">
                                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="reset">
                                                    <input type="hidden" name="user_id" value="<?= e($pl['id']) ?>">
                                                    <button class="btn btn-sm btn-outline-danger">Reset</button>
                                                </form>
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
