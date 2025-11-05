<?php
session_start();
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

$stmt = $conn->prepare("SELECT * FROM transactions WHERE customer_id = ? ORDER BY transaction_date DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$query = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>My Orders</title>
<style>
body { background:#0A0A0F; color:#fff; font-family:Arial; }
table { width:90%; margin:30px auto; border-collapse:collapse; background:#12121A; }
th, td { padding:12px; text-align:center; border-bottom:1px solid #24242E; }
th { background:#7B1FA2; }
.status { padding:5px 10px; border-radius:5px; }
.Pending { background:#D4A017; }
.Completed { background:#2FA84F; }
button { background:#9b4de0; padding:7px 14px; border:none; border-radius:6px; color:#fff; cursor:pointer; }
button:hover { background:#7e32bb; }
</style>
</head>
<body>

<h2 style="text-align:center;">📦 My Orders</h2>

<table>
<tr>
    <th>Transaction ID</th>
    <th>Date</th>
    <th>Status</th>
    <th>Total</th>
    <th>Receipt</th>
</tr>

<?php while($row = $query->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['id']) ?></td>
    <td><?= htmlspecialchars($row['transaction_date']) ?></td>
    <td><span class="status <?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
    <td>₱<?= number_format($row['total'],2) ?></td>
   <td>
    <a href="../admin/transactions/view.php?id=<?= intval($row['id']) ?>">
        <button>View Receipt</button>
    </a>
</td>

</tr>
<?php endwhile; ?>

</table>

</body>
</html>
