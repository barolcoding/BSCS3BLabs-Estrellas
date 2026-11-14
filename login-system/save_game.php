<?php
    require 'auth.php';
    require 'db.php';
    require 'player.php';

    header('Content-Type: application/json');

    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        exit(json_encode(['ok' => false, 'message' => 'Not logged in.']));
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit(json_encode(['ok' => false, 'message' => 'Invalid request.']));
    }

    $uid   = (int)$_SESSION['user_id'];
    $score = min(max((int)($_POST['score'] ?? 0), 0), 100000);
    $coins = min(max((int)($_POST['coins'] ?? 0), 0), 500);

    $before = (int)get_stats($conn, $uid)['high_score'];

    // CREATE: save this run to the game log.
    run($conn, "INSERT INTO game_logs (user_id, score, coins_earned) VALUES (?, ?, ?)", 'iii', [$uid, $score, $coins]);

    // UPDATE: add the coins earned, and CREATE/raise the saved high score.
    run($conn, "UPDATE player_stats SET coins = coins + ?, high_score = GREATEST(high_score, ?), updated_at = CURRENT_TIMESTAMP WHERE user_id = ?", 'iii', [$coins, $score, $uid]);

    // CREATE: unlock any achievements this run earned.
    $checks = [
        'first_flight' => true,
        'score_100'    => $score >= 100,
        'score_500'    => $score >= 500,
        'coin_hoard'   => $coins >= 10,
    ];
    $unlocked = [];
    foreach ($checks as $code => $earned) {
        if ($earned && award($conn, $uid, $code)) {
            $unlocked[] = ACHIEVEMENTS[$code][0];
        }
    }

    $message = "Run saved. +$coins coins.";
    if ($score > $before) {
        $message .= ' New high score!';
    }
    if ($unlocked) {
        $message .= ' Achievement unlocked: ' . implode(', ', $unlocked) . '.';
    }
    echo json_encode(['ok' => true, 'message' => $message]);
