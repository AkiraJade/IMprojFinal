<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Handle search and filter
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$query = "SELECT * FROM expenses WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND description LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($from_date)) {
    $query .= " AND expense_date >= ?";
    $params[] = $from_date;
    $types .= "s";
}

if (!empty($to_date)) {
    $query .= " AND expense_date <= ?";
    $params[] = $to_date;
    $types .= "s";
}

$query .= " ORDER BY expense_date DESC, id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Calculate total using prepared statement
$total_query = "SELECT SUM(amount) as total FROM expenses WHERE 1=1";
$total_params = [];
$total_types = "";

if (!empty($search)) {
    $total_query .= " AND description LIKE ?";
    $total_params[] = "%$search%";
    $total_types .= "s";
}

if (!empty($category)) {
    $total_query .= " AND category = ?";
    $total_params[] = $category;
    $total_types .= "s";
}

if (!empty($from_date)) {
    $total_query .= " AND expense_date >= ?";
    $total_params[] = $from_date;
    $total_types .= "s";
}

if (!empty($to_date)) {
    $total_query .= " AND expense_date <= ?";
    $total_params[] = $to_date;
    $total_types .= "s";
}

$stmt_total = $conn->prepare($total_query);
if (!empty($total_params)) {
    $stmt_total->bind_param($total_types, ...$total_params);
}
$stmt_total->execute();
$total_result = $stmt_total->get_result();
$total = $total_result->fetch_assoc()['total'] ?? 0;

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Expenses List</h2>
        <a href="create.php" class="btn-primary">➕ Add Expense</a>

        <!-- Search and Filter Form -->
        <form method="GET" class="form-box" style="margin: 20px 0;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                <div>
                    <label>Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Description...">
                </div>
                <div>
                    <label>Category</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        <option value="Utilities" <?= $category == 'Utilities' ? 'selected' : '' ?>>Utilities</option>
                        <option value="Rent" <?= $category == 'Rent' ? 'selected' : '' ?>>Rent</option>
                        <option value="Supplies" <?= $category == 'Supplies' ? 'selected' : '' ?>>Supplies</option>
                        <option value="Marketing" <?= $category == 'Marketing' ? 'selected' : '' ?>>Marketing</option>
                        <option value="Salary" <?= $category == 'Salary' ? 'selected' : '' ?>>Salary</option>
                        <option value="Other" <?= $category == 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label>From Date</label>
                    <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>">
                </div>
                <div>
                    <label>To Date</label>
                    <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
                </div>
            </div>
            <button type="submit" class="btn-primary">🔍 Filter</button>
            <a href="read.php" class="btn-secondary">Clear</a>
        </form>

        <p><strong>Total Expenses:</strong> ₱<?= number_format($total, 2) ?></p>

        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']); ?></td>
                        <td><?= htmlspecialchars($row['description']); ?></td>
                        <td><?= htmlspecialchars($row['category']); ?></td>
                        <td>₱<?= number_format($row['amount'], 2); ?></td>
                        <td><?= htmlspecialchars($row['expense_date']); ?></td>
                        <td>
                            <a class="btn-edit" href="update.php?id=<?= intval($row['id']); ?>">✏ Edit</a>
                            <a class="btn-delete" href="delete.php?id=<?= intval($row['id']); ?>" onclick="return confirm('Delete this expense?');">🗑 Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
