<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/EmailService.php';

// Check admin access
checkAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$user_id = intval($_GET['id']);

// Get user details including email
$stmt = $conn->prepare("SELECT id, username, email, is_active FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: read.php?error=User not found");
    exit();
}

// Toggle status
$new_status = $user['is_active'] ? 0 : 1;
$stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
$stmt->bind_param("ii", $new_status, $user_id);

if ($stmt->execute()) {
    // Send notification email
    try {
        error_log("Attempting to send account status email for user ID: " . $user_id . ", New status: " . ($new_status ? 'Active' : 'Inactive'));
        
        $emailService = new EmailService();
        $emailSent = $emailService->sendAccountStatusNotification(
            [
                'id' => $user['id'],
                'name' => $user['username'],
                'email' => $user['email']
            ],
            (bool)$new_status
        );
        
        if ($emailSent) {
            error_log("Successfully sent account status email to: " . $user['email']);
        } else {
            error_log("Failed to send account status email to user ID: " . $user_id . ". EmailService returned false.");
        }
    } catch (Exception $e) {
        $error = "Error sending account status email to " . $user['email'] . ": " . $e->getMessage();
        error_log($error);
        // Also log the full backtrace for debugging
        error_log("Stack trace: " . $e->getTraceAsString());
    }
    
    $message = $new_status ? "activated" : "deactivated";
    header("Location: read.php?msg=User successfully $message");
} else {
    header("Location: read.php?error=Failed to update user status");
}
exit();
?>
