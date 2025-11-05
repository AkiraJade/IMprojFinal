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

$stmt = $conn->prepare("SELECT s.name AS supplier_name, COUNT(ps.id) AS deliveries,
                        SUM(ps.quantity) AS total_quantity, SUM(ps.cost) AS total_cost
                        FROM product_suppliers ps
                        JOIN suppliers s ON ps.supplier_id = s.id
                        WHERE DATE(ps.delivery_date) BETWEEN ? AND ?
                        GROUP BY ps.supplier_id");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include '../../includes/header.php'; ?>

<h2>Supplier Summary Report</h2>

<form method="GET">
    From: <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
    To: <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
    <button type="submit">Filter</button>
</form>

<table border="1" width="100%">
<tr>
    <th>Supplier</th>
    <th>Deliveries</th>
    <th>Total Quantity</th>
    <th>Total Cost</th>
</tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['supplier_name']) ?></td>
    <td><?= htmlspecialchars($row['deliveries']) ?></td>
    <td><?= htmlspecialchars($row['total_quantity']) ?></td>
    <td>₱<?= number_format($row['total_cost'],2) ?></td>
</tr>
<?php endwhile; ?>
</table>

<?php include '../../includes/footer.php'; ?>
