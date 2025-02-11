<?php
require 'config.php';

$password = 'admin123'; // The password you want to hash
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
$stmt->execute(['admin', 'admin@example.com', '1234567890', $hashedPassword, 'admin']);

echo "Admin user created successfully!";
?>
