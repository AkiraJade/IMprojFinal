<?php
include __DIR__ . '/../../includes/config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'customer' ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();
include '../../../includes/header.php';
?>

<h2>Customers List</h2>
<br>

<table border="1" width="100%">
<tr>
    <th>ID</th>
    <th>Username</th>
    <th>Email</th>
    <th>Role</th>
    <th>Created At</th>
    <th>Actions</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['id']); ?></td>
    <td><?= htmlspecialchars($row['username']); ?></td>
    <td><?= htmlspecialchars($row['email']); ?></td>
    <td><?= htmlspecialchars($row['role']); ?></td>
    <td><?= htmlspecialchars($row['created_at'] ?? 'N/A'); ?></td>
    <td>
        <a href="update.php?id=<?= intval($row['id']); ?>">✏ Edit</a> |
        <a href="delete.php?id=<?= intval($row['id']); ?>" 
           onclick="return confirm('Delete this customer?');">🗑 Delete</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

<?php include '../../../includes/footer.php'; ?>
