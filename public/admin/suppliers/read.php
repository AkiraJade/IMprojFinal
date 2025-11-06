<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Handle search and filter (FR3.3)
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM suppliers WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR contact_person LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

$query .= " ORDER BY id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Suppliers List</h2>
        <a href="create.php" class="btn-primary">➕ Add Supplier</a>

        <!-- Search Form -->
        <form method="GET" class="form-box" style="margin: 20px 0;">
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or contact person...">
                <div>
                    <button type="submit" class="btn-primary">🔍 Search</button>
                    <a href="read.php" class="btn-secondary">Clear</a>
                </div>
            </div>
        </form>

        <table class="styled-table">
            <thead>
                <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Contact Person</th>
    <th>Contact Number</th>
    <th>Email</th>
    <th>Address</th>
    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['id']); ?></td>
    <td><?= htmlspecialchars($row['name']); ?></td>
    <td><?= htmlspecialchars($row['contact_person']); ?></td>
    <td><?= htmlspecialchars($row['contact_number']); ?></td>
    <td><?= htmlspecialchars($row['email']); ?></td>
    <td><?= htmlspecialchars($row['address']); ?></td>
    <td>
        <a class="btn-view" href="deliveries.php?supplier_id=<?= intval($row['id']); ?>">📦 Deliveries</a>
        <a class="btn-edit" href="update.php?id=<?= intval($row['id']); ?>">✏ Edit</a>
        <a class="btn-delete" href="delete.php?id=<?= intval($row['id']); ?>" 
           onclick="return confirm('Delete this supplier?');">🗑 Delete</a>
    </td>
</tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
