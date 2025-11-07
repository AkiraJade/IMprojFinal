<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'customer' ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();
include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Customers List</h2>

        <table class="styled-table">
            <thead>
                <tr>
    <th>ID</th>
    <th>Username</th>
    <th>Email</th>
    <th>Role</th>
    <th>Created At</th>
    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['id']); ?></td>
    <td><?= htmlspecialchars($row['username']); ?></td>
    <td><?= htmlspecialchars($row['email']); ?></td>
    <td><?= htmlspecialchars($row['role']); ?></td>
    <td><?= htmlspecialchars($row['created_at'] ?? 'N/A'); ?></td>
    <td>
        <a class="btn-view" href="view.php?id=<?= intval($row['id']); ?>">👁 View</a>
        <a class="btn-edit" href="update.php?id=<?= intval($row['id']); ?>">✏ Edit</a>
        <a class="btn-delete" href="delete.php?id=<?= intval($row['id']); ?>" 
           onclick="return confirm('Delete this customer?');">🗑 Delete</a>
    </td>
</tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
