<?php
// Determine the base path relative to current location
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$base_path = ($current_dir === 'admin') ? '.' : '..';
?>
<aside class="sidebar">
    <h3>Admin Panel</h3>
    <ul>
        <li><a href="<?= $base_path ?>/dashboard.php">📊 Dashboard</a></li>
        <li><a href="<?= $base_path ?>/products/read.php">👕 Products</a></li>
        <li><a href="<?= $base_path ?>/customers/read.php">👥 Customers</a></li>
        <li><a href="<?= $base_path ?>/suppliers/read.php">🚚 Suppliers</a></li>
        <li><a href="<?= $base_path ?>/expenses/read.php">💰 Expenses</a></li>
        <li><a href="<?= $base_path ?>/transactions/read.php">🧾 Sales</a></li>
        <li><a href="<?= $base_path ?>/reports/sales_report.php">📈 Reports</a></li>
        <li><a href="../../logout.php">🚪 Logout</a></li>
    </ul>
</aside>