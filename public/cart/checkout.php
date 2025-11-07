<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$payment_method = $_POST['payment_method'] ?? 'Cash';

// Get cart items
$stmt = $conn->prepare("SELECT c.product_id, c.quantity, p.price, p.stock, p.name
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

$cart_items = [];
$total = 0;

// Validate stock and compute total
while ($row = $cart->fetch_assoc()) {
    if ($row['quantity'] > $row['stock']) {
        echo "<script>alert('Sorry, {$row['name']} has insufficient stock!'); window.location='cart.php';</script>";
        exit();
    }
    $cart_items[] = $row;
    $total += $row['price'] * $row['quantity'];
}

// Start transaction
$conn->begin_transaction();

try {
    // Get or create customer record
    $stmt = $conn->prepare("SELECT id FROM customers WHERE email = (SELECT email FROM users WHERE id = ?)");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $customer_result = $stmt->get_result();
    
    if ($customer_result->num_rows > 0) {
        $customer_record_id = $customer_result->fetch_assoc()['id'];
    } else {
        // Create customer record from user data
        $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        $stmt = $conn->prepare("INSERT INTO customers (name, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $user['username'], $user['email']);
        $stmt->execute();
        $customer_record_id = $conn->insert_id;
    }
    
    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, order_date, payment_method, total_amount, status) 
                           VALUES (?, NOW(), ?, ?, 'Pending')");
    $stmt->bind_param("isd", $customer_record_id, $payment_method, $total);
    $stmt->execute();
    $order_id = $conn->insert_id;
    
    // Insert order items and update stock
    foreach ($cart_items as $item) {
        // Insert order item
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $stmt->execute();
        
        // Update product stock (FR4.5 - Automatic stock adjustment)
        $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        $stmt->execute();
    }
    
    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo "<script>alert('✅ Checkout successful! Order ID: {$order_id}'); window.location='../customer/orders.php';</script>";
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo "<script>alert('❌ Checkout failed: " . $e->getMessage() . "'); window.location='cart.php';</script>";
    exit();
}
?>
