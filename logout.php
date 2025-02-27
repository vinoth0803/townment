<?php
// logout.php

// Determine session name based on cookie presence, but we'll clear both cookies
if (isset($_COOKIE['admin_session'])) {
    session_name("admin_session");
} elseif (isset($_COOKIE['tenant_session'])) {
    session_name("tenant_session");
} else {
    session_name("tenant_session");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear session data and destroy the session
$_SESSION = [];
session_destroy();

// Clear the session cookie for the current session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, "/", $params["domain"], $params["secure"], $params["httponly"]);
}

// Additionally, clear both admin and tenant cookies if they exist
if (isset($_COOKIE['admin_session'])) {
    setcookie("admin_session", '', time() - 42000, "/", $params["domain"], $params["secure"], $params["httponly"]);
}
if (isset($_COOKIE['tenant_session'])) {
    setcookie("tenant_session", '', time() - 42000, "/", $params["domain"], $params["secure"], $params["httponly"]);
}

echo json_encode(['success' => true]);
exit();
?>
