<?php
require_once 'db.php';

// Helpers
function is_logged_in() {
    return isset($_SESSION['user_id']); //
}
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}
function current_user() {
    global $mysqli;
    if(!is_logged_in()) return null;
    $uid = current_user_id();
    $stmt = $mysqli->prepare("SELECT id, username, email, role, phone, address FROM users WHERE id = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}
function is_admin() { 
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
function generate_invoice_number() {
    return 'INV' . time() . rand(100,999);
}
function redirect($url) {
    header("Location: $url");
    exit;
}
function redirect_with_msg($url, $msg) {
    $sep = strpos($url, '?') === false ? '?' : '&';
    header("Location: {$url}{$sep}msg=" . urlencode($msg));
    exit;
}
?>