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

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if (!$customer) {
    header("Location: read.php");
    exit();
}

// Get customer's orders
$orders_stmt = $conn->prepare("
    SELECT o.id, o.order_date, o.total_amount, o.payment_method, o.status, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE c.email = ?
    GROUP BY o.id
    ORDER BY o.order_date DESC
");
$orders_stmt->bind_param("s", $customer['email']);
$orders_stmt->execute();
$orders = $orders_stmt->get_result();

// Get statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        COUNT(CASE WHEN o.status = 'Pending' THEN 1 END) as pending_orders
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE c.email = ?
");
$stats_stmt->bind_param("s", $customer['email']);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Customer Details</h2>
        <a href="read.php" class="btn-secondary">← Back to List</a>
        <a href="update.php?id=<?= $id ?>" class="btn-primary">✏ Edit Customer</a>

        <!-- Customer Information Card -->
        <div style="background: var(--dark-light); padding: 2rem; border-radius: var(--radius-lg); margin: 2rem 0; border: 1px solid rgba(155, 77, 224, 0.2);">
            <h3 style="color: var(--primary-light); margin-bottom: 1.5rem;">📋 Customer Information</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div>
                    <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Customer ID</p>
                    <p style="color: var(--text-primary); font-weight: 600; font-size: 1.1rem;">#<?= str_pad($customer['id'], 6, '0', STR_PAD_LEFT) ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Username</p>
                    <p style="color: var(--text-primary); font-weight: 600; font-size: 1.1rem;"><?= htmlspecialchars($customer['username']) ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Email</p>
                    <p style="color: var(--text-primary); font-weight: 600; font-size: 1.1rem;"><?= htmlspecialchars($customer['email']) ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Phone</p>
                    <p style="color: var(--text-primary); font-weight: 600; font-size: 1.1rem;"><?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Address</p>
                    <p style="color: var(--text-primary); font-weight: 600; font-size: 1.1rem;"><?= htmlspecialchars($customer['address'] ?? 'N/A') ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Member Since</p>
                    <p style="color: var(--text-primary); font-weight: 600; font-size: 1.1rem;"><?= date('M d, Y', strtotime($customer['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-container" style="margin: 2rem 0;">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <p><?= $stats['total_orders'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Spent</h3>
                <p>₱<?= number_format($stats['total_spent'], 2) ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <p><?= $stats['pending_orders'] ?></p>
            </div>
        </div>

        <!-- Order History -->
        <h3 style="color: var(--primary-light); margin: 2rem 0 1rem 0;">📦 Order History</h3>
        <?php if ($orders->num_rows > 0): ?>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($order = $orders->fetch_assoc()): ?>
                <tr>
                    <td>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                    <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                    <td><?= $order['item_count'] ?> items</td>
                    <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                    <td><?= htmlspecialchars($order['payment_method']) ?></td>
                    <td>
                        <span style="padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600;
                                    background: <?= $order['status'] == 'Completed' ? 'rgba(0, 217, 165, 0.2)' : ($order['status'] == 'Pending' ? 'rgba(255, 176, 32, 0.2)' : 'rgba(155, 77, 224, 0.2)') ?>;
                                    color: <?= $order['status'] == 'Completed' ? '#00D9A5' : ($order['status'] == 'Pending' ? '#FFB020' : '#9b4de0') ?>;">
                            <?= htmlspecialchars($order['status']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="../transactions/view.php?id=<?= $order['id'] ?>" class="btn-view">View</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; padding: 2rem; color: var(--text-secondary);">No orders yet.</p>
        <?php endif; ?>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
