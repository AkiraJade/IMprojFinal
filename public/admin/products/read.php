<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

include '../../../includes/header.php';

// Get all products
$result = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Products List</h2>
        <a href="create.php" class="btn-primary">➕ Add Product</a>

        <table class="styled-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Size</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Condition</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><img src="../../uploads/<?= htmlspecialchars($row['image']); ?>" height="50"></td>
                        <td><?= htmlspecialchars($row['name']); ?></td>
                        <td><?= htmlspecialchars($row['brand']); ?></td>
                        <td><?= htmlspecialchars($row['category']); ?></td>
                        <td><?= htmlspecialchars($row['size']); ?></td>
                        <td>₱<?= number_format($row['price'], 2); ?></td>
                        <td><?= htmlspecialchars($row['stock']); ?></td>
                        <td><?= htmlspecialchars($row['condition_type']); ?></td>
                        <td>
                            <a class="btn-edit" href="update.php?id=<?= intval($row['id']); ?>">✏ Edit</a>
                            <a class="btn-delete" href="delete.php?id=<?= intval($row['id']); ?>" onclick="return confirm('Delete this product?');">🗑 Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
