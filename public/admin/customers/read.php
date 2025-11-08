<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Show success message if any
$message = $_GET['msg'] ?? '';

$stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();
include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Users Management</h2>

        <?php if ($message): ?>
            <div style="padding: 1rem; margin-bottom: 1rem; background: #10b981; color: white; border-radius: 8px;">
                ✅ <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr style="<?= isset($row['is_active']) && !$row['is_active'] ? 'opacity: 0.5;' : '' ?>">
                    <td><?= htmlspecialchars($row['id']); ?></td>
                    <td><?= htmlspecialchars($row['username']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span style="padding: 0.25rem 0.75rem; border-radius: 12px; background: <?= $row['role'] == 'admin' ? '#9b4de0' : '#10b981' ?>; color: white; font-size: 0.85rem;">
                            <?= htmlspecialchars($row['role']); ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        $is_active = isset($row['is_active']) ? $row['is_active'] : 1;
                        $status_color = $is_active ? '#10b981' : '#ef4444';
                        $status_text = $is_active ? 'Active' : 'Inactive';
                        ?>
                        <span style="padding: 0.25rem 0.75rem; border-radius: 12px; background: <?= $status_color ?>; color: white; font-size: 0.85rem;">
                            <?= $status_text ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['created_at'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if ($row['role'] == 'customer'): ?>
                            <a class="btn-view" href="view.php?id=<?= intval($row['id']); ?>">👁 View</a>
                        <?php endif; ?>
                        <a class="btn-edit" href="update.php?id=<?= intval($row['id']); ?>">✏ Edit</a>
                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                            <a class="btn-<?= $is_active ? 'delete' : 'edit' ?>" 
                               href="toggle_status.php?id=<?= intval($row['id']); ?>" 
                               onclick="return confirm('<?= $is_active ? 'Deactivate' : 'Activate' ?> this user?');">
                                <?= $is_active ? '🚫 Deactivate' : '✅ Activate' ?>
                            </a>
                            <a class="btn-delete" href="delete.php?id=<?= intval($row['id']); ?>" 
                               onclick="return confirm('Delete this user permanently?');">🗑 Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
