<?php
require_once __DIR__ . '/includes/config.php';

echo "<h2>Admin Login Debug</h2>";

// Get admin user from database
$stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
$email = 'admin@urbanthrift.com';
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    echo "<h3>User Found:</h3>";
    echo "<pre>";
    echo "ID: " . $user['id'] . "\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Role: " . $user['role'] . "\n";
    echo "Password Hash: " . $user['password'] . "\n";
    echo "</pre>";
    
    // Test password verification
    $test_password = 'admin123';
    echo "<h3>Password Verification Test:</h3>";
    echo "Testing password: <strong>$test_password</strong><br>";
    
    if (password_verify($test_password, $user['password'])) {
        echo "✅ <span style='color: green;'>Password verification: SUCCESS</span><br>";
    } else {
        echo "❌ <span style='color: red;'>Password verification: FAILED</span><br>";
        echo "<p style='color: red;'>The password hash in the database does NOT match 'admin123'</p>";
        
        // Generate correct hash
        $correct_hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo "<h3>Solution:</h3>";
        echo "<p>Run this SQL query to fix the password:</p>";
        echo "<pre style='background: #f0f0f0; padding: 10px;'>";
        echo "UPDATE users \n";
        echo "SET password = '$correct_hash' \n";
        echo "WHERE email = 'admin@urbanthrift.com';";
        echo "</pre>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Admin user not found in database!</p>";
    echo "<p>Run this SQL query to create the admin user:</p>";
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    echo "<pre style='background: #f0f0f0; padding: 10px;'>";
    echo "INSERT INTO users (username, email, password, role) \n";
    echo "VALUES ('admin', 'admin@urbanthrift.com', '$hash', 'admin');";
    echo "</pre>";
}

echo "<hr>";
echo "<h3>Alternative: Reset Password Directly</h3>";
echo "<form method='POST' action='reset_admin_password.php'>";
echo "<p>Click this button to automatically reset admin password to 'admin123':</p>";
echo "<button type='submit' style='padding: 10px 20px; background: #9b4de0; color: white; border: none; border-radius: 5px; cursor: pointer;'>Reset Admin Password</button>";
echo "</form>";
?>
