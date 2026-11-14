<?php
    $config = require __DIR__ . '/config.php';

    $parts = [];
    foreach (['host', 'port', 'dbname', 'user', 'password'] as $key) {
        $parts[] = $key . "='" . addcslashes($config[$key], "'\\") . "'";
    }
    if (!function_exists('pg_connect')) {
        exit('The PHP pgsql extension is not enabled. In XAMPP: Config > PHP (php.ini), remove the ; before extension=pgsql, then restart Apache.');
    }

    $conn = @pg_connect(implode(' ', $parts) . " sslmode='require' connect_timeout=10");

    if (!$conn) {
        $detail = error_get_last()['message'] ?? 'unknown error';
        error_log('Supabase connection failed: ' . $detail);
        // Show the reason only when running on your own machine.
        $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
        exit('Database connection failed. Check config.php.' . ($local ? '<br><br>Reason: ' . htmlspecialchars($detail) : ''));
    }

    // Runs a query with bound parameters. Write "?" placeholders; they are
    // converted to $1, $2... for pg_query_params(). $types is kept so the
    // calling code stays simple, Postgres does not need it.
    function run($conn, $sql, $types = '', $params = []) {
        $i = 0;
        $sql = preg_replace_callback('/\?/', function () use (&$i) {
            return '$' . ++$i;
        }, $sql);

        $result = @pg_query_params($conn, $sql, $params);
        if (!$result) {
            error_log(pg_last_error($conn));
            exit('Database error. Please try again.');
        }
        return $result;
    }

    // Returns every row of a SELECT as an associative array.
    function fetch_all($result) {
        return pg_fetch_all($result) ?: [];
    }
