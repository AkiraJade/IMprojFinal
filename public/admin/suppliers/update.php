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
$stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$supplier = $result->fetch_assoc();

if (!$supplier) {
    header("Location: read.php");
    exit();
}

if (isset($_POST['update'])) {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    $stmt = $conn->prepare("UPDATE suppliers SET name = ?, contact_person = ?, contact_number = ?, email = ?, address = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $name, $contact_person, $contact_number, $email, $address, $id);
    $stmt->execute();
    
    header("Location: read.php");
    exit();
}

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Edit Supplier</h2>

        <form class="form-box" method="POST">
            <label>Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($supplier['name']); ?>" required>

            <label>Contact Person</label>
            <input type="text" name="contact_person" value="<?= htmlspecialchars($supplier['contact_person']); ?>" required>

            <label>Contact Number</label>
            <input type="text" name="contact_number" value="<?= htmlspecialchars($supplier['contact_number']); ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($supplier['email']); ?>" required>

            <label>Address</label>
            <textarea name="address" required><?= htmlspecialchars($supplier['address']); ?></textarea>

            <button type="submit" name="update" class="btn-primary">Update Supplier</button>
            <a href="read.php" class="btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
