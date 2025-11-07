<?php
// Generate password hash for admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hash: $hash\n\n";

// Verify it works
if (password_verify($password, $hash)) {
    echo "✅ Password verification: SUCCESS\n";
} else {
    echo "❌ Password verification: FAILED\n";
}

// Also check the hash from the database
$db_hash = '$2y$10$E2ZRqfH5j0KxPkhSGHdJ6u6jjPZQ5Nwo4WRZ4MfYUp1MZPX7C8cNy';
echo "\nChecking database hash:\n";
if (password_verify($password, $db_hash)) {
    echo "✅ Database hash verification: SUCCESS\n";
} else {
    echo "❌ Database hash verification: FAILED\n";
}

// Generate SQL update statement
echo "\n\n--- SQL UPDATE STATEMENT ---\n";
echo "UPDATE users SET password = '$hash' WHERE email = 'admin@urbanthrift.com';\n";
?>
