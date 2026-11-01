<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_role() {
    return $_SESSION['role'] ?? null;
}

function is_owner() {
    return current_role() === 'owner';
}

function is_customer() {
    return current_role() === 'customer';
}

function is_cashier() {
    return current_role() === 'kasir';
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function require_owner() {
    if (!is_owner()) {
        http_response_code(403);
        die("403 - Access denied. Owner only.");
    }
}

function require_cashier() {
    if (!is_cashier()) {
        http_response_code(403);
        die("403 - Access denied. Cashier only.");
    }
}

function require_customer() {
    if (!is_customer()) {
        http_response_code(403);
        die("403 - Access denied. Customer only.");
    }
}
?>