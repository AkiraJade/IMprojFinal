<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if (!$customer) {
    header("Location: read.php");
    exit();
}

if (isset($_POST['update'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $email, $role, $id);
    $stmt->execute();
    
    header("Location: read.php?msg=User updated successfully");
    exit();
}

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Edit User</h2>

        <form class="form-box" method="POST">
            <label>Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($customer['username']) ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" required>

            <label>Role</label>
            <select name="role" required>
                <option value="customer" <?= $customer['role'] == 'customer' ? 'selected' : '' ?>>Customer</option>
                <option value="admin" <?= $customer['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>

            <button type="submit" name="update" class="btn-primary">Update User</button>
            <a href="read.php" class="btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
