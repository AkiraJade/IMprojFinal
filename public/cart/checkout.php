<?php
session_start();
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

$stmt = $conn->prepare("SELECT c.product_id, c.quantity, p.price
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cart = $stmt->get_result();

if ($cart->num_rows == 0) {
    echo "<script>alert('Your cart is empty!'); window.location='cart.php';</script>";
    exit();
}

// Compute total
$total = 0;
while ($row = $cart->fetch_assoc()) {
    $total += $row['price'] * $row['quantity'];
}

// Insert transaction
$stmt = $conn->prepare("INSERT INTO transactions(customer_id, transaction_date, status, total) VALUES(?, NOW(), 'Pending', ?)");
$stmt->bind_param("id", $customer_id, $total);
$stmt->execute();

// Clear cart after processing
$stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();

echo "<script>alert('Checkout successful! Your order is Pending.'); window.location='../customer/orders.php';</script>";
exit();
?>
