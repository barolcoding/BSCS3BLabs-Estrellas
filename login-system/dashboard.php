<?php
    require 'auth.php';
    require 'db.php';
    require 'player.php';
    require_login();

    $uid = (int)$_SESSION['user_id'];

    // Re-check that the account still exists (an admin may have deleted it).
    $me = fetch_all(run($conn, "SELECT id, username, role, created_at FROM users WHERE id = ?", 'i', [$uid]))[0] ?? null;
    if (!$me) {
        header("Location: logout.php");
        exit();
    }
    $_SESSION['role'] = $me['role'];
    $admin = is_admin();

    // Only admins get the player statistics.
    if ($admin) {
        $totalPlayers = fetch_all(run($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'user'"))[0]['c'];
        $totalAdmins  = fetch_all(run($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'admin'"))[0]['c'];
    }
    $stats = get_stats($conn, $uid);
    $flash = flash();
?>
<html>
    <head>
        <title>Tarbibi | Play</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="style.css" rel="stylesheet">
    </head>
    <body>
        <?php include 'nav.php'; ?>

        <div id="dashboard" class="container pb-5">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> py-2"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-end mb-3">
                <div>
                    <h1 id="game-title" class="page-title h3 mb-0">Rune Banner</h1>
                    <small class="text-muted">Placeholder build &middot; welcome, <?= e($me['username']) ?></small>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div id="game-wrap">
                        <canvas id="game-canvas" width="480" height="640"></canvas>
                    </div>
                    <div class="d-flex gap-2 mt-3 align-items-center">
                        <button id="start-btn" class="btn btn-primary">Start</button>
                        <button id="restart-btn" class="btn btn-outline-dark">Restart</button>
                        <span id="save-status" class="small text-muted ms-2"></span>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="panel-card card-body mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Health</span><span id="hp-text">100</span>
                        </div>
                        <div id="hp-track" class="mb-3"><div id="hp-bar"></div></div>
                        <div class="row text-center">
                            <div class="col"><div id="score" class="stat-number">0</div><div class="text-muted small">Score</div></div>
                            <div class="col"><div id="coins" class="stat-number">0</div><div class="text-muted small">Coins</div></div>
                        </div>
                    </div>

                    <div class="panel-card card-body mb-3">
                        <h2 class="h6 fw-bold">Controls</h2>
                        <p class="small text-muted mb-0">Arrow keys or WASD to move. The mage fires automatically. On touch screens, drag inside the game. Finished runs are saved, and coins can be spent on the Progress page.</p>
                    </div>

                    <div class="panel-card card-body mb-3">
                        <h2 class="h6 fw-bold">Build status</h2>
                        <ul class="list-unstyled small mb-0">
                            <li><i class="bi bi-check-square me-2"></i>Parallax scrolling</li>
                            <li><i class="bi bi-check-square me-2"></i>Collisions</li>
                            <li><i class="bi bi-check-square me-2"></i>Health bar</li>
                            <li><i class="bi bi-square me-2"></i>Boss combat (state machine)</li>
                            <li><i class="bi bi-square me-2"></i>Upgrades and progression</li>
                            <li><i class="bi bi-square me-2"></i>Power-ups</li>
                        </ul>
                    </div>

                    <?php if ($admin): ?>
                        <div id="admin-panel" class="panel-card card-body">
                            <h2 class="h6 fw-bold">Admin overview</h2>
                            <div class="row text-center mb-2">
                                <div class="col"><div class="stat-number"><?= e($totalPlayers) ?></div><div class="text-muted small">Players</div></div>
                                <div class="col"><div class="stat-number"><?= e($totalAdmins) ?></div><div class="text-muted small">Admins</div></div>
                            </div>
                            <a href="users.php" class="btn btn-sm btn-outline-dark w-100">Manage users</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            // Placeholder vertical shooter: parallax stars, auto-fire, enemies, collisions, health.
            (function () {
                var canvas = document.getElementById('game-canvas');
                var ctx = canvas.getContext('2d');
                var W = canvas.width, H = canvas.height;
                var keys = {}, state, running = false, last = 0;
                var P = <?= json_encode(['hpLevel' => (int)$stats['hp_level'], 'speedLevel' => (int)$stats['speed_level'], 'fireLevel' => (int)$stats['fire_level']]) ?>;
                var CSRF = <?= json_encode(csrf_token()) ?>;
                var maxHp = 100 + 20 * P.hpLevel;

                function reset() {
                    state = {
                        p: {x: W / 2, y: H - 80, w: 26, h: 30, hp: maxHp, cool: 0},
                        bullets: [], enemies: [], spawn: 0, score: 0, coins: 0, over: false, stars: []
                    };
                    for (var i = 0; i < 70; i++) {
                        state.stars.push({x: Math.random() * W, y: Math.random() * H, layer: 1 + Math.floor(Math.random() * 3)});
                    }
                    hud();
                    draw();
                }

                function hit(a, b) {
                    return Math.abs(a.x - b.x) * 2 < a.w + b.w && Math.abs(a.y - b.y) * 2 < a.h + b.h;
                }

                function hud() {
                    document.getElementById('hp-bar').style.width = Math.max(state.p.hp, 0) / maxHp * 100 + '%';
                    document.getElementById('hp-text').textContent = Math.max(state.p.hp, 0);
                    document.getElementById('score').textContent = state.score;
                    document.getElementById('coins').textContent = state.coins;
                }

                function update(dt) {
                    var s = state, p = s.p, speed = 260 * (1 + 0.1 * P.speedLevel);
                    if (keys.ArrowLeft || keys.a)  p.x -= speed * dt;
                    if (keys.ArrowRight || keys.d) p.x += speed * dt;
                    if (keys.ArrowUp || keys.w)    p.y -= speed * dt;
                    if (keys.ArrowDown || keys.s)  p.y += speed * dt;
                    p.x = Math.min(W - 14, Math.max(14, p.x));
                    p.y = Math.min(H - 16, Math.max(16, p.y));

                    s.stars.forEach(function (st) {
                        st.y += st.layer * 40 * dt;
                        if (st.y > H) { st.y = 0; st.x = Math.random() * W; }
                    });

                    p.cool -= dt;
                    if (p.cool <= 0) {
                        s.bullets.push({x: p.x, y: p.y - 18, w: 4, h: 14});
                        p.cool = 0.22 * (1 - 0.08 * P.fireLevel);
                    }
                    s.bullets.forEach(function (b) { b.y -= 520 * dt; });
                    s.bullets = s.bullets.filter(function (b) { return b.y > -20; });

                    s.spawn -= dt;
                    if (s.spawn <= 0) {
                        s.enemies.push({x: 30 + Math.random() * (W - 60), y: -20, w: 26, h: 26, vy: 70 + Math.random() * 60, t: Math.random() * 6});
                        s.spawn = 0.8;
                    }
                    s.enemies.forEach(function (en) {
                        en.t += dt;
                        en.y += en.vy * dt;
                        en.x += Math.sin(en.t * 2) * 40 * dt;
                    });

                    s.enemies.forEach(function (en) {
                        s.bullets.forEach(function (b) {
                            if (!en.dead && !b.dead && hit(en, b)) {
                                en.dead = b.dead = true;
                                s.score += 10;
                                if (Math.random() < 0.3) s.coins++;
                            }
                        });
                        if (!en.dead && hit(en, p)) {
                            en.dead = true;
                            p.hp -= 20;
                        }
                    });
                    s.enemies = s.enemies.filter(function (en) { return !en.dead && en.y < H + 30; });
                    s.bullets = s.bullets.filter(function (b) { return !b.dead; });

                    if (p.hp <= 0) { s.over = true; running = false; setBtn(); saveRun(); }
                    hud();
                }

                function draw() {
                    var s = state, p = s.p;
                    ctx.fillStyle = '#0b0b0b';
                    ctx.fillRect(0, 0, W, H);
                    s.stars.forEach(function (st) {
                        ctx.fillStyle = st.layer === 3 ? '#ddd' : st.layer === 2 ? '#888' : '#555';
                        ctx.fillRect(st.x, st.y, st.layer, st.layer);
                    });
                    ctx.fillStyle = '#eee';
                    s.bullets.forEach(function (b) { ctx.fillRect(b.x - 2, b.y - 7, b.w, b.h); });
                    ctx.fillStyle = '#9a9a9a';
                    s.enemies.forEach(function (en) { ctx.fillRect(en.x - 13, en.y - 13, en.w, en.h); });
                    ctx.fillStyle = '#fff';
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y - 16);
                    ctx.lineTo(p.x + 14, p.y + 16);
                    ctx.lineTo(p.x - 14, p.y + 16);
                    ctx.closePath();
                    ctx.fill();

                    if (!running) {
                        ctx.fillStyle = 'rgba(0,0,0,.6)';
                        ctx.fillRect(0, 0, W, H);
                        ctx.fillStyle = '#fff';
                        ctx.textAlign = 'center';
                        ctx.font = 'bold 28px Inter, sans-serif';
                        ctx.fillText(s.over ? 'Game over' : 'Rune Banner', W / 2, H / 2 - 10);
                        ctx.font = '15px Inter, sans-serif';
                        ctx.fillText(s.over ? 'Score: ' + s.score : 'Press Start', W / 2, H / 2 + 20);
                    }
                }

                function loop(now) {
                    if (!running) return;
                    var dt = Math.min((now - last) / 1000, 0.05);
                    last = now;
                    update(dt);
                    draw();
                    if (running) requestAnimationFrame(loop);
                }

                // Saves the finished run (game log + coins earned) through save_game.php.
                function saveRun() {
                    var body = new URLSearchParams({csrf: CSRF, score: state.score, coins: state.coins});
                    fetch('save_game.php', {method: 'POST', body: body})
                        .then(function (r) { return r.json(); })
                        .then(function (j) { document.getElementById('save-status').textContent = j.message; })
                        .catch(function () { document.getElementById('save-status').textContent = 'Could not save this run.'; });
                }

                function setBtn() {
                    document.getElementById('start-btn').textContent = running ? 'Pause' : 'Start';
                    draw();
                }

                function toggle() {
                    if (state.over) reset();
                    running = !running;
                    setBtn();
                    if (running) { last = performance.now(); requestAnimationFrame(loop); }
                }

                document.getElementById('start-btn').addEventListener('click', toggle);
                document.getElementById('restart-btn').addEventListener('click', function () {
                    running = false;
                    reset();
                    setBtn();
                });

                window.addEventListener('keydown', function (e) {
                    var k = e.key.length === 1 ? e.key.toLowerCase() : e.key;
                    keys[k] = true;
                    if (running && k.indexOf('Arrow') === 0) e.preventDefault();
                });
                window.addEventListener('keyup', function (e) {
                    keys[e.key.length === 1 ? e.key.toLowerCase() : e.key] = false;
                });

                canvas.addEventListener('pointermove', function (e) {
                    if (!running || e.buttons === 0) return;
                    var r = canvas.getBoundingClientRect();
                    state.p.x = (e.clientX - r.left) * W / r.width;
                    state.p.y = (e.clientY - r.top) * H / r.height;
                });

                reset();
            })();
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
