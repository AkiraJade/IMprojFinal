<?php
include __DIR__ . '/../../includes/config.php';

// ✅ Admin-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
?>

<?php include '../../includes/header.php'; ?>

<div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <h3>Admin Panel</h3>
        <ul>
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="products/read.php">👕 Products</a></li>
            <li><a href="customers/read.php">👥 Customers</a></li>
            <li><a href="suppliers/read.php">🚚 Suppliers</a></li>
            <li><a href="expenses/read.php">💰 Expenses</a></li>
            <li><a href="transactions/read.php">🧾 Sales</a></li>
            <li><a href="reports/sales_report.php">📈 Reports</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <!-- Main Dashboard Content -->
    <main class="admin-content">
        <h2>Welcome Admin 👑</h2>
        <p>Manage UrbanThrift system here.</p>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Products</h3>
                <p>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as total FROM products");
                    $row = $result->fetch_assoc();
                    echo $row['total'];
                    ?>
                </p>
            </div>

            <div class="stat-card">
                <h3>Total Customers</h3>
                <p>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='customer'");
                    $row = $result->fetch_assoc();
                    echo $row['total'];
                    ?>
                </p>
            </div>

            <div class="stat-card">
                <h3>Total Sales</h3>
                <p>
                    ₱
                    <?php
                    $result = $conn->query("SELECT SUM(total_amount) as income FROM sales");
                    $row = $result->fetch_assoc();
                    echo $row['income'] ?? 0;
                    ?>
                </p>
            </div>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>
