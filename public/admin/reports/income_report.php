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

$stmt = $conn->prepare("SELECT SUM(total_amount) AS sales_total
                        FROM orders 
                        WHERE DATE(order_date) BETWEEN ? AND ?");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$result = $stmt->get_result();
$sales = $result->fetch_assoc()['sales_total'] ?? 0;

$stmt = $conn->prepare("SELECT SUM(amount) AS expense_total
                        FROM expenses
                        WHERE DATE(expense_date) BETWEEN ? AND ?");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$result = $stmt->get_result();
$expenses = $result->fetch_assoc()['expense_total'] ?? 0;

$income = $sales - $expenses;
?>

<?php include '../../includes/header.php'; ?>

<h2>Income Report</h2>

<form method="GET">
    From: <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
    To: <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
    <button type="submit">Filter</button>
</form>

<table border="1" width="100%">
    <tr><th>Sales</th><td>₱<?= number_format($sales,2) ?></td></tr>
    <tr><th>Expenses</th><td>₱<?= number_format($expenses,2) ?></td></tr>
    <tr><th><strong>Income</strong></th><td><strong>₱<?= number_format($income,2) ?></strong></td></tr>
</table>

<?php include '../../includes/footer.php'; ?>
