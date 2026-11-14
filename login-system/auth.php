<?php
    session_start();

    function e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    function csrf_token() {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    function csrf_check($token) {
        return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token);
    }

    // No session -> always back to the login page.
    function require_login() {
        if (empty($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }
    }

    function is_admin() {
        return ($_SESSION['role'] ?? '') === 'admin';
    }

    function flash($type = null, $message = null) {
        if ($type !== null) {
            $_SESSION['flash'] = ['type' => $type, 'message' => $message];
            return null;
        }
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }
