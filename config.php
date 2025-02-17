<?php
// config.php
$host = "localhost";
$db_name = "townment2";
$db_user = "root";  // change as needed
$db_pass = "";      // change as needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
