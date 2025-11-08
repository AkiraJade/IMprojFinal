<?php
include __DIR__ . '/../../../includes/config.php';

// Check admin access
checkAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$user_id = intval($_GET['id']);

// Get current status
$stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: read.php");
    exit();
}

// Toggle status
$new_status = $user['is_active'] ? 0 : 1;
$stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
$stmt->bind_param("ii", $new_status, $user_id);
$stmt->execute();

$message = $new_status ? "activated" : "deactivated";
header("Location: read.php?msg=User successfully $message");
exit();
?>
