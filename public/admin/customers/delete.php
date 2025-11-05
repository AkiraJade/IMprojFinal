<?php
include __DIR__ . '/../../includes/config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: read.php");
exit();
?>
