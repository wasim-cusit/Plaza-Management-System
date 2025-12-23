<?php
/**
 * Setup Script for Plaza Management System
 * Run this file once after database installation to set up the admin user
 */

require_once 'config/database.php';

$conn = getDBConnection();

// Check if admin already exists
$result = $conn->query("SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1");

if ($result->num_rows > 0) {
    echo "<h2>Setup Already Completed</h2>";
    echo "<p>Admin user already exists. If you need to reset the password, please do it manually from the database.</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
    exit;
}

// Create admin user
$username = 'admin';
$email = 'admin@plaza.com';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$full_name = 'System Administrator';
$phone = '1234567890';
$user_type = 'admin';
$status = 'active';

$stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, user_type, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $username, $email, $password, $full_name, $phone, $user_type, $status);

if ($stmt->execute()) {
    echo "<h2>Setup Completed Successfully!</h2>";
    echo "<p>Admin user has been created with the following credentials:</p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    echo "<p style='color: red;'><strong>IMPORTANT:</strong> Please change the default password after first login!</p>";
    echo "<p><a href='login.php' style='display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
} else {
    echo "<h2>Setup Failed</h2>";
    echo "<p>Error: " . $conn->error . "</p>";
}

$stmt->close();
$conn->close();
?>

