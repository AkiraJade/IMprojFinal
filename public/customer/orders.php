<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

// Get customer record ID from users table
$stmt = $conn->prepare("SELECT id FROM customers WHERE email = (SELECT email FROM users WHERE id = ?)");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer_result = $stmt->get_result();
$customer_record = $customer_result->fetch_assoc();

// Get filter
$filter = $_GET['filter'] ?? 'all';

if (!$customer_record) {
    $orders = [];
    $total_orders = 0;
    $total_spent = 0;
} else {
    $customer_record_id = $customer_record['id'];
    
    // Build query based on filter
    $query = "SELECT o.*, COUNT(oi.id) as item_count 
              FROM orders o 
              LEFT JOIN order_items oi ON o.id = oi.order_id
              WHERE o.customer_id = ?";
    
    if ($filter === 'pending') {
        $query .= " AND o.status = 'Pending'";
    } elseif ($filter === 'completed') {
        $query .= " AND o.status = 'Completed'";
    }
    
    $query .= " GROUP BY o.id ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    // Get stats
    $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_record_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $total_orders = $stats['count'];
    $total_spent = $stats['total'] ?? 0;
}

include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | UrbanThrift</title>
    <link rel="stylesheet" href="/projectIManagement/public/css/style.css">
    <style>
        .orders-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .orders-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .orders-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .orders-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--dark-light);
            padding: 2rem;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(155, 77, 224, 0.2);
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-glow);
            border-color: var(--primary);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-light);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filters-section {
            background: var(--dark-light);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(155, 77, 224, 0.2);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .filter-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            background: rgba(155, 77, 224, 0.1);
            border: 2px solid rgba(155, 77, 224, 0.2);
            color: var(--text-primary);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .orders-grid {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .order-card {
            background: var(--dark-light);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(155, 77, 224, 0.2);
            overflow: hidden;
            transition: var(--transition);
        }

        .order-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }

        .order-header {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 2rem;
            padding: 1.5rem;
            background: rgba(155, 77, 224, 0.05);
            align-items: center;
        }

        .order-id {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-light);
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .order-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .order-items-count {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .order-status {
            padding: 0.625rem 1.25rem;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(255, 176, 32, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .status-completed {
            background: rgba(0, 217, 165, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .status-cancelled {
            background: rgba(255, 71, 87, 0.2);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .order-total {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .order-body {
            padding: 1.5rem;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 600;
        }

        .order-actions {
            display: flex;
            gap: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(155, 77, 224, 0.1);
        }

        .btn-action {
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-view {
            background: rgba(155, 77, 224, 0.1);
            border: 2px solid var(--primary);
            color: var(--primary-light);
        }

        .btn-view:hover {
            background: var(--primary);
            color: white;
        }

        .btn-print {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: var(--dark-light);
            border-radius: var(--radius-xl);
            border: 2px dashed rgba(155, 77, 224, 0.2);
        }

        .empty-state-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            font-size: 1.8rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 968px) {
            .order-header {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .order-total {
                text-align: left;
            }

            .filters-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="orders-container">
    <div class="orders-header">
        <h1>📦 My Orders</h1>
        <p>Track and manage your order history</p>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value"><?= $total_orders ?></div>
            <div class="stat-label">Total Orders</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value">₱<?= number_format($total_spent, 2) ?></div>
            <div class="stat-label">Total Spent</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🛍️</div>
            <div class="stat-value"><?= count($orders) ?></div>
            <div class="stat-label">Showing</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-buttons">
            <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                All Orders
            </a>
            <a href="?filter=pending" class="filter-btn <?= $filter === 'pending' ? 'active' : '' ?>">
                Pending
            </a>
            <a href="?filter=completed" class="filter-btn <?= $filter === 'completed' ? 'active' : '' ?>">
                Completed
            </a>
        </div>
        <a href="dashboard.php" class="btn-action btn-view">
            ← Back to Dashboard
        </a>
    </div>

    <!-- Orders List -->
    <?php if (count($orders) > 0): ?>
        <div class="orders-grid">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></div>
                        <div class="order-info">
                            <div class="order-date">
                                <?= date('F d, Y', strtotime($order['order_date'])) ?> at 
                                <?= date('h:i A', strtotime($order['order_date'])) ?>
                            </div>
                            <div class="order-items-count"><?= $order['item_count'] ?> item(s)</div>
                        </div>
                        <div class="order-status status-<?= strtolower($order['status']) ?>">
                            <?= htmlspecialchars($order['status']) ?>
                        </div>
                        <div class="order-total">₱<?= number_format($order['total_amount'], 2) ?></div>
                    </div>

                    <div class="order-body">
                        <div class="order-details">
                            <div class="detail-item">
                                <span class="detail-label">Payment Method</span>
                                <span class="detail-value"><?= htmlspecialchars($order['payment_method']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Order Date</span>
                                <span class="detail-value"><?= date('M d, Y', strtotime($order['order_date'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="detail-value"><?= htmlspecialchars($order['status']) ?></span>
                            </div>
                        </div>

                        <div class="order-actions">
                            <a href="../admin/transactions/view.php?id=<?= $order['id'] ?>" class="btn-action btn-view">
                                👁️ View Details
                            </a>
                            <a href="../admin/transactions/receipt_print.php?id=<?= $order['id'] ?>" target="_blank" class="btn-action btn-print">
                                🖨️ Print Receipt
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <h3>No Orders Found</h3>
            <p>
                <?php if ($filter !== 'all'): ?>
                    No <?= $filter ?> orders at the moment.
                <?php else: ?>
                    You haven't placed any orders yet. Start shopping to see your orders here!
                <?php endif; ?>
            </p>
            <a href="../shop.php" class="btn-primary" style="display: inline-block; padding: 1rem 2rem; text-decoration: none;">
                🛍️ Start Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>

</body>
</html>
