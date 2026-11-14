<?php
    // Shared helpers for the game's player profile (inventory, upgrades).
    const MAX_LEVEL = 5;

    // Achievement list: code => [title, description]
    const ACHIEVEMENTS = [
        'first_flight'  => ['First Flight',    'Finish your first run.'],
        'score_100'     => ['Rising Mage',     'Score 100 points in a run.'],
        'score_500'     => ['Rune Master',     'Score 500 points in a run.'],
        'coin_hoard'    => ['Coin Collector',  'Earn 10 coins in a single run.'],
        'first_upgrade' => ['Powered Up',      'Buy your first upgrade.'],
        'max_upgrade'   => ['Maxed Out',       'Reach level 5 on any upgrade.'],
    ];

    function upgrade_cost($level) {
        return 20 + 20 * (int)$level;
    }

    // CREATE: every account gets one player profile row (safe to call many times).
    function ensure_stats($conn, $uid) {
        run($conn, "INSERT INTO player_stats (user_id) VALUES (?) ON CONFLICT (user_id) DO NOTHING", 'i', [$uid]);
    }

    // READ: coins and upgrade levels of one player.
    function get_stats($conn, $uid) {
        ensure_stats($conn, $uid);
        return fetch_all(run($conn, "SELECT coins, high_score, hp_level, speed_level, fire_level FROM player_stats WHERE user_id = ?", 'i', [$uid]))[0];
    }

    // CREATE: unlock an achievement. Returns true only the first time it is earned.
    function award($conn, $uid, $code) {
        $stmt = run($conn, "INSERT INTO achievements (user_id, code) VALUES (?, ?) ON CONFLICT (user_id, code) DO NOTHING", 'is', [$uid, $code]);
        return pg_affected_rows($stmt) > 0;
    }
