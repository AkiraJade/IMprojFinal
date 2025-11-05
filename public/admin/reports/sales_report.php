<?php
include __DIR__ . '/../../includes/config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-t');

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $from = date('Y-m-01');
    $to = date('Y-m-t');
}

$stmt = $conn->prepare("SELECT o.id, o.total_amount, o.order_date, c.name AS customer_name
                        FROM orders o
                        JOIN customers c ON o.customer_id = c.id
                        WHERE DATE(o.order_date) BETWEEN ? AND ?
                        ORDER BY o.order_date DESC");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$result = $stmt->get_result();

$stmt2 = $conn->prepare("SELECT SUM(total_amount) AS total_sales
                         FROM orders WHERE DATE(order_date) BETWEEN ? AND ?");
$stmt2->bind_param("ss", $from, $to);
$stmt2->execute();
$total_result = $stmt2->get_result();
$total = $total_result->fetch_assoc()['total_sales'] ?? 0;
?>

<?php include '../../includes/header.php'; ?>

<h2>Sales Report</h2>

<form method="GET">
    From: <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
    To: <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
    <button type="submit">Filter</button>
</form>

<p><strong>Total Sales:</strong> ₱<?= number_format($total, 2) ?></p>

<table border="1" width="100%">
<tr>
    <th>Order ID</th>
    <th>Customer</th>
    <th>Total Amount</th>
    <th>Date</th>
</tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['id']) ?></td>
    <td><?= htmlspecialchars($row['customer_name']) ?></td>
    <td>₱<?= number_format($row['total_amount'],2) ?></td>
    <td><?= htmlspecialchars($row['order_date']) ?></td>
</tr>
<?php endwhile; ?>
</table>

<?php include '../../includes/footer.php'; ?>
