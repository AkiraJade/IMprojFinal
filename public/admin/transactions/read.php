<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$stmt = $conn->prepare("
SELECT o.id, o.order_date, o.total_amount, o.payment_method, 
       c.name AS customer_name
FROM orders o
JOIN customers c ON o.customer_id = c.id
ORDER BY o.order_date DESC");
$stmt->execute();
$result = $stmt->get_result();

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Sales Transactions</h2>
        <a href="create.php" class="btn-primary">➕ Create Transaction</a>

        <table class="styled-table">
            <thead>
                <tr>
    <th>ID</th>
    <th>Customer</th>
    <th>Payment Method</th>
    <th>Total Amount</th>
    <th>Date</th>
    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['id']); ?></td>
    <td><?= htmlspecialchars($row['customer_name']); ?></td>
    <td><?= htmlspecialchars($row['payment_method']); ?></td>
    <td>₱<?= number_format($row['total_amount'], 2); ?></td>
    <td><?= htmlspecialchars($row['order_date']); ?></td>
    <td>
        <a class="btn-view" href="view.php?id=<?= intval($row['id']); ?>">👁 View</a>
        <a class="btn-primary" href="receipt_print.php?id=<?= intval($row['id']); ?>" target="_blank">🖨️ Print</a>
        <a class="btn-delete" href="delete.php?id=<?= intval($row['id']); ?>" 
           onclick="return confirm('Delete this transaction?');">🗑 Delete</a>
    </td>
</tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
