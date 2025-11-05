<?php
include __DIR__ . '/../../includes/config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM suppliers ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();
include '../../../includes/header.php';
?>

<h2>Suppliers List</h2>
<a href="create.php">➕ Add Supplier</a>
<br><br>

<table border="1" width="100%">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Contact Person</th>
    <th>Contact Number</th>
    <th>Email</th>
    <th>Address</th>
    <th>Actions</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['id']); ?></td>
    <td><?= htmlspecialchars($row['name']); ?></td>
    <td><?= htmlspecialchars($row['contact_person']); ?></td>
    <td><?= htmlspecialchars($row['contact_number']); ?></td>
    <td><?= htmlspecialchars($row['email']); ?></td>
    <td><?= htmlspecialchars($row['address']); ?></td>
    <td>
        <a href="update.php?id=<?= intval($row['id']); ?>">✏ Edit</a> |
        <a href="delete.php?id=<?= intval($row['id']); ?>" 
           onclick="return confirm('Delete this supplier?');">🗑 Delete</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

<?php include '../../../includes/footer.php'; ?>
