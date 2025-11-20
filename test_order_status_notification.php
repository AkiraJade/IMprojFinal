<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/EmailService.php';

// Set test user data (override session for testing if needed)
$testUser = [
    'id' => $_SESSION['user_id'] ?? 'test_user_' . time(),
    'name' => $_SESSION['name'] ?? 'Test User',
    'email' => $_SESSION['email'] ?? 'test@example.com'
];

// Test order data with all required fields
$testOrder = [
    'order_number' => 'TEST-' . time(),
    'total_amount' => '99.99',
    'item_count' => 2,
    'created_at' => date('Y-m-d H:i:s'),
    'status' => 'shipped',
    'tracking_number' => 'TRK' . mt_rand(100000, 999999) // Random tracking number for testing
];

// Test status update
$testStatus = 'shipped';

echo "<h1>Order Status Notification Test</h1>";

try {
    // Initialize the email service
    $emailService = new EmailService();
    
    // Test 1: Send order status update
    echo "<h2>Test 1: Sending Order Status Update</h2>";
    $result1 = $emailService->sendOrderStatusUpdate($testOrder, $testUser, $testStatus);
    
    if ($result1) {
        echo "<p style='color: green;'>✓ Order status notification sent successfully to {$testUser['email']}</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to send order status notification. Check error logs for details.</p>";
    }
    
    // Test 2: Send shipped notification
    echo "<h2>Test 2: Sending Shipped Notification</h2>";
    $result2 = $emailService->sendShippedNotification($testOrder, $testUser, $testOrder['tracking_number']);
    
    if ($result2) {
        echo "<p style='color: green;'>✓ Shipped notification sent successfully to {$testUser['email']}</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to send shipped notification. Check error logs for details.</p>";
    }
    
    // Display test order details
    echo "<h2>Test Order Details</h2>";
    echo "<pre>" . htmlspecialchars(print_r([
        'user' => $testUser,
        'order' => $testOrder,
        'status' => $testStatus
    ], true)) . "</pre>";
    
    echo "<p>Check your Mailtrap inbox at <a href='https://mailtrap.io/' target='_blank'>https://mailtrap.io/</a></p>";
    
    // Test 3: Test error handling with invalid data
    echo "<h2>Test 3: Error Handling</h2>";
    try {
        $invalidResult = $emailService->sendOrderStatusUpdate([], $testUser, 'invalid');
        echo "<p style='color: red;'>✗ Expected exception not thrown for invalid order data</p>";
    } catch (InvalidArgumentException $e) {
        echo "<p style='color: green;'>✓ Caught expected exception: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>Test Failed</h2>";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
// Display session info for debugging
echo "<hr><h3>Debug Info:</h3>";
echo "<pre>Session Data: " . htmlspecialchars(print_r($_SESSION, true)) . "</pre>";

// Display any PHP errors
$errorLog = ini_get('error_log');
if (file_exists($errorLog)) {
    $errors = file_get_contents($errorLog);
    if (!empty($errors)) {
        echo "<h3>Recent PHP Errors:</h3>";
        echo "<pre>" . htmlspecialchars($errors) . "</pre>";
    } else {
        echo "<p>No recent PHP errors found in the log.</p>";
    }
}
echo "<h3>Error Log:</h3>";
$errorLog = ini_get('error_log');
if (file_exists($errorLog)) {
    echo "<pre>Last 5 error log entries:\n";
    $logContent = file($errorLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logEntries = array_slice($logContent, -5);
    echo implode("\n", array_map('htmlspecialchars', $logEntries));
    echo "</pre>";
} else {
    echo "<p>Error log file not found at: " . htmlspecialchars($errorLog) . "</p>";
}

// Add a closing HTML tag for better formatting
?>
</body>
</html>
