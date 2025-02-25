<?php
// Check if a role parameter is provided and set session name accordingly
if (isset($_GET['role']) && $_GET['role'] === 'admin') {
    session_name("admin_session");
} else {
    session_name("tenant_session");
}

session_start();
session_destroy();

// Ensure session cookies are also cleared
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Redirect to login page
header("Location: login.html");
exit();
?>
