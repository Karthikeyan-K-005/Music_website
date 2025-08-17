<?php
// auth.php
function is_logged_in(): bool { return isset($_SESSION['user']); }
function current_user() { return $_SESSION['user'] ?? null; }

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    if (!is_logged_in() || ($_SESSION['user']['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo "Forbidden: Admins only.";
        exit;
    }
}
