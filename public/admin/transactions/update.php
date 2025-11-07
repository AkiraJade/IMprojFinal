<?php
include __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$order_id = intval($_GET['id']);

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, c.name as customer_name, c.email as customer_email
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: read.php");
    exit();
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image, p.stock
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result();

$message = "";
$message_type = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = trim($_POST['payment_method']);
    $status = trim($_POST['status']);
    
    // Update order
    $stmt = $conn->prepare("UPDATE orders SET payment_method = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $payment_method, $status, $order_id);
    
    if ($stmt->execute()) {
        $message = "✅ Order updated successfully!";
        $message_type = "success";
        
        // Refresh order data
        $stmt = $conn->prepare("
            SELECT o.*, c.name as customer_name, c.email as customer_email
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
    } else {
        $message = "❌ Failed to update order.";
        $message_type = "error";
    }
}

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Edit Transaction #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></h2>
        <a href="read.php" class="btn-secondary">← Back to Sales</a>
        <a href="view.php?id=<?= $order_id ?>" class="btn-view">👁 View Details</a>

        <?php if($message): ?>
            <div style="padding: 1rem; margin: 1rem 0; border-radius: 8px; 
                        background: <?= $message_type == 'success' ? 'rgba(0, 217, 165, 0.15)' : 'rgba(255, 71, 87, 0.15)' ?>;
                        color: <?= $message_type == 'success' ? '#00D9A5' : '#FF4757' ?>;
                        border: 1px solid <?= $message_type == 'success' ? 'rgba(0, 217, 165, 0.3)' : 'rgba(255, 71, 87, 0.3)' ?>;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Order Information -->
        <div style="background: var(--dark-light); padding: 1.5rem; border-radius: var(--radius-lg); margin: 2rem 0; border: 1px solid rgba(155, 77, 224, 0.2);">
            <h3 style="color: var(--primary-light); margin-bottom: 1rem;">Order Information</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">Order ID</p>
                    <p style="color: var(--text-primary); font-weight: 600;">#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">Customer</p>
                    <p style="color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars($order['customer_name'] ?? 'Unknown') ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">Order Date</p>
                    <p style="color: var(--text-primary); font-weight: 600;"><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">Total Amount</p>
                    <p style="color: var(--primary-light); font-weight: 700; font-size: 1.2rem;">₱<?= number_format($order['total_amount'], 2) ?></p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <h3 style="color: var(--primary-light); margin: 2rem 0 1rem 0;">📦 Order Items</h3>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = $items->fetch_assoc()): ?>
                <tr>
                    <td><img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($item['image']) ?>" height="50" style="border-radius: 8px;"></td>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>₱<?= number_format($item['price'], 2) ?></td>
                    <td>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr style="background: rgba(155, 77, 224, 0.1); font-weight: 700;">
                    <td colspan="4" style="text-align: right;">TOTAL:</td>
                    <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Edit Form -->
        <h3 style="color: var(--primary-light); margin: 2rem 0 1rem 0;">✏ Edit Order Details</h3>
        <form method="POST" class="form-box" style="max-width: 600px;">
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" required>
                    <option value="Cash" <?= $order['payment_method'] == 'Cash' ? 'selected' : '' ?>>💵 Cash</option>
                    <option value="GCash" <?= $order['payment_method'] == 'GCash' ? 'selected' : '' ?>>📱 GCash</option>
                    <option value="Credit Card" <?= $order['payment_method'] == 'Credit Card' ? 'selected' : '' ?>>💳 Credit Card</option>
                    <option value="Bank Transfer" <?= $order['payment_method'] == 'Bank Transfer' ? 'selected' : '' ?>>🏦 Bank Transfer</option>
                </select>
            </div>

            <div class="form-group">
                <label>Order Status</label>
                <select name="status" required>
                    <option value="Pending" <?= $order['status'] == 'Pending' ? 'selected' : '' ?>>⏳ Pending</option>
                    <option value="Processing" <?= $order['status'] == 'Processing' ? 'selected' : '' ?>>🔄 Processing</option>
                    <option value="Completed" <?= $order['status'] == 'Completed' ? 'selected' : '' ?>>✅ Completed</option>
                    <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
                </select>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn-primary">💾 Save Changes</button>
                <a href="read.php" class="btn-secondary">Cancel</a>
            </div>
        </form>

        <div style="margin-top: 2rem; padding: 1rem; background: rgba(255, 176, 32, 0.1); border-radius: 8px; border: 1px solid rgba(255, 176, 32, 0.3);">
            <p style="color: #FFB020; margin: 0;">
                <strong>⚠️ Note:</strong> Changing the order status will not automatically update product stock. 
                Stock was already adjusted when the order was created.
            </p>
        </div>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
