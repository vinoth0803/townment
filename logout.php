<?php
// logout.php

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

// Clear the session cookie (using "/" as the path)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, "/", $params["domain"], $params["secure"], $params["httponly"]);
}

// Return JSON response instead of redirecting
echo json_encode(['success' => true]);
exit();
?>
