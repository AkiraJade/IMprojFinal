<?php
require_once __DIR__ . '/includes/config.php';

// Generate new password hash for 'admin123'
$new_password = 'admin123';
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update admin password
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@urbanthrift.com'");
$stmt->bind_param("s", $new_hash);

if ($stmt->execute()) {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Password Reset</title></head><body>";
    echo "<h2>✅ Admin Password Reset Successful!</h2>";
    echo "<p>The admin password has been reset to: <strong>admin123</strong></p>";
    echo "<p>Email: <strong>admin@urbanthrift.com</strong></p>";
    echo "<p>New password hash: <code>$new_hash</code></p>";
    echo "<hr>";
    echo "<a href='public/login.php' style='display: inline-block; padding: 10px 20px; background: #9b4de0; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
    echo "</body></html>";
} else {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Password Reset</title></head><body>";
    echo "<h2>❌ Password Reset Failed</h2>";
    echo "<p>Error: " . $conn->error . "</p>";
    echo "<a href='debug_login.php'>Back to Debug Page</a>";
    echo "</body></html>";
}
?>
