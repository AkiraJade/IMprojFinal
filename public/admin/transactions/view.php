<?php
include __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Validate order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$order_id = intval($_GET['id']);

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, c.name AS customer_name, c.contact_number, c.email, c.address
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_q = $stmt->get_result();
$order = $order_q->fetch_assoc();

if (!$order) {
    header("Location: read.php");
    exit();
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name AS product_name
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_q = $stmt->get_result();
?>

<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Transaction Receipt</h2>
        <a href="receipt_print.php?id=<?= $order_id ?>" target="_blank" class="btn-primary" style="margin-bottom: 15px; display: inline-block;">🖨️ Print Receipt</a>
<p><strong>Order ID:</strong> <?= htmlspecialchars($order['id']); ?></p>
<p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']); ?></p>
<p><strong>Contact:</strong> <?= htmlspecialchars($order['contact_number']); ?></p>
<p><strong>Date:</strong> <?= htmlspecialchars($order['order_date']); ?></p>
<p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']); ?></p>

<hr>

<table class="styled-table">
            <thead>
                <tr>
    <th>Product</th>
    <th>Qty</th>
    <th>Unit Price</th>
    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items_q->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($item['product_name']); ?></td>
    <td><?= htmlspecialchars($item['quantity']); ?></td>
    <td>₱<?= number_format($item['price'], 2); ?></td>
    <td>₱<?= number_format($item['quantity'] * $item['price'], 2); ?></td>
</tr>
                <?php endwhile; ?>
            </tbody>
        </table>

<h3>Total: ₱<?= number_format($order['total_amount'], 2); ?></h3>

        <a href="read.php" class="btn-secondary">⬅ Back to Transactions</a>
    </main>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
