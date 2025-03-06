<?php
// logout.php

// Start the session using the default session name.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear session data and destroy the session.
$_SESSION = [];
session_destroy();

// Clear the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// Additionally, clear any leftover custom cookies.
setcookie("admin_session", '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
setcookie("tenant_session", '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);

echo json_encode(['success' => true]);
exit();
?>
