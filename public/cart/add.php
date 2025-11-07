<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) { 
    $product_id = intval($_GET['id']);
    $customer_id = $_SESSION['user_id'];

    // Check if product already exists in cart
    $stmt = $conn->prepare("SELECT id FROM cart WHERE product_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $product_id, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE product_id = ? AND customer_id = ?");
        $stmt->bind_param("ii", $product_id, $customer_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, 1)");
        $stmt->bind_param("ii", $customer_id, $product_id);
        $stmt->execute();
    }

    header("Location: cart.php");
    exit();
}
?>
