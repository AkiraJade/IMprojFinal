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

// Enhanced supplier report with deliveries (FR3.4)
$stmt = $conn->prepare("SELECT s.id, s.name AS supplier_name, s.contact_person, s.contact_number,
                        COUNT(sd.id) AS deliveries,
                        SUM(sd.quantity) AS total_quantity, 
                        SUM(sd.cost) AS total_cost
                        FROM suppliers s
                        LEFT JOIN supplier_deliveries sd ON s.id = sd.supplier_id 
                            AND DATE(sd.delivery_date) BETWEEN ? AND ?
                        GROUP BY s.id
                        ORDER BY total_cost DESC");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include '../../../includes/header.php'; ?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Supplier Summary Report</h2>

        <form method="GET" class="form-box" style="margin: 20px 0;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                <div>
                    <label>From Date</label>
                    <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
                </div>
                <div>
                    <label>To Date</label>
                    <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
                </div>
            </div>
            <button type="submit" class="btn-primary">Filter</button>
        </form>

        <table class="styled-table">
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Contact Person</th>
                    <th>Contact Number</th>
                    <th>Number of Deliveries</th>
                    <th>Total Quantity Supplied</th>
                    <th>Total Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grand_total_qty = 0;
                $grand_total_cost = 0;
                while($row = $result->fetch_assoc()): 
                    $grand_total_qty += $row['total_quantity'] ?? 0;
                    $grand_total_cost += $row['total_cost'] ?? 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                    <td><?= htmlspecialchars($row['contact_person']) ?></td>
                    <td><?= htmlspecialchars($row['contact_number']) ?></td>
                    <td><?= htmlspecialchars($row['deliveries'] ?? 0) ?></td>
                    <td><?= htmlspecialchars($row['total_quantity'] ?? 0) ?></td>
                    <td>₱<?= number_format($row['total_cost'] ?? 0, 2) ?></td>
                    <td>
                        <a class="btn-view" href="../suppliers/deliveries.php?supplier_id=<?= intval($row['id']) ?>">View Deliveries</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; background: #7B1FA2;">
                    <td colspan="4">TOTALS</td>
                    <td><?= $grand_total_qty ?></td>
                    <td>₱<?= number_format($grand_total_cost, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
