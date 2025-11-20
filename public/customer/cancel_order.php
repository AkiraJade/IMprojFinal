<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
    $_SESSION['error'] = "Invalid order ID";
    header("Location: orders.php");
    exit();
}

$order_id = intval($_POST['order_id']);
$customer_id = $_SESSION['user_id'];

// Verify the order belongs to the customer and is pending
$stmt = $conn->prepare("
    SELECT o.*, u.email as user_email 
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN users u ON u.id = c.user_id
    WHERE o.id = ? AND u.id = ? AND o.status = 'Pending'
    LIMIT 1
");
$stmt->bind_param("ii", $order_id, $customer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['error'] = "Order not found, already processed, or cannot be cancelled";
    header("Location: orders.php");
    exit();
}

// Update order status to Cancelled
$stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?");
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    // Send cancellation notification email
    require_once __DIR__ . '/../../includes/EmailService.php';
    $emailService = new EmailService();
    
    // Get user details
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        // Get order items for the email
        $stmt = $conn->prepare("
            SELECT oi.*, p.name as product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        $items = [];
        while ($row = $items_result->fetch_assoc()) {
            $items[] = $row;
        }
        
        // Prepare order data for email
        $order_data = [
            'id' => $order_id,
            'order_number' => 'ORD' . str_pad($order_id, 6, '0', STR_PAD_LEFT),
            'total_amount' => $order['total_amount'],
            'created_at' => $order['order_date'],
            'items' => $items,
            'payment_method' => $order['payment_method'],
            'status' => 'cancelled',
            'cancellation_reason' => $_POST['reason'] ?? 'Customer requested cancellation'
        ];
        
        // Send status update email
        $emailService->sendOrderStatusUpdate(
            $order_data,
            [
                'id' => $customer_id,
                'name' => $user['username'],
                'email' => $user['email']
            ],
            'cancelled'
        );
    }
    
    $_SESSION['success'] = "Order #" . $order_id . " has been cancelled successfully.";
} else {
    $_SESSION['error'] = "Failed to cancel order. Please try again or contact support.";
}

header("Location: orders.php");
exit();
