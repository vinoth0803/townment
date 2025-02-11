<?php
session_start();
session_unset(); // Clear all session variables
session_destroy(); // Destroy the session

// Optional: Clear authentication cookie if used
if (isset($_COOKIE['auth_token'])) {
    setcookie('auth_token', '', time() - 3600, "/"); // Expire the cookie
}

// Send JSON response
echo json_encode(["success" => true, "message" => "Logged out successfully"]);
header("Location: index.php");
exit;
?>
