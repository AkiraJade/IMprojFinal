<?php
include __DIR__ . '/../../includes/config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
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

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $username, $email, $id);
    $stmt->execute();
    
    header("Location: read.php");
    exit();
}

include '../../../includes/header.php';
?>

<h2>Edit Customer</h2>

<form method="POST">
    <label>Username:</label><br>
    <input type="text" name="username" value="<?= htmlspecialchars($customer['username']) ?>" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" required><br><br>

    <button type="submit" name="update">Update Customer</button>
</form>

<?php include '../../../includes/footer.php'; ?>
