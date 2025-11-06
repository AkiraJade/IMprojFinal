<?php
include __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
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

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Income Report</h2>

        <form class="form-box" method="GET">
            <label>From Date</label>
            <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">

            <label>To Date</label>
            <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">

            <button type="submit" class="btn-primary">🔍 Filter</button>
            <a href="income_report.php" class="btn-secondary">Clear</a>
        </form>

        <table class="styled-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>Sales</th>
                    <td>₱<?= number_format($sales,2) ?></td>
                </tr>
                <tr>
                    <th>Expenses</th>
                    <td>₱<?= number_format($expenses,2) ?></td>
                </tr>
                <tr style="font-weight: bold; background: #7B1FA2;">
                    <th>Net Income</th>
                    <td>₱<?= number_format($income,2) ?></td>
                </tr>
            </tbody>
        </table>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
