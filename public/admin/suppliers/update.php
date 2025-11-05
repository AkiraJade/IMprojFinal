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

<h2>Edit Supplier</h2>

<form method="POST">
    <label>Name:</label><br>
    <input type="text" name="name" value="<?= $supplier['name']; ?>" required><br><br>

    <label>Contact Person:</label><br>
    <input type="text" name="contact_person" value="<?= $supplier['contact_person']; ?>" required><br><br>

    <label>Contact Number:</label><br>
    <input type="text" name="contact_number" value="<?= $supplier['contact_number']; ?>" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" value="<?= $supplier['email']; ?>" required><br><br>

    <label>Address:</label><br>
    <textarea name="address" required><?= $supplier['address']; ?></textarea><br><br>

    <button type="submit" name="update">Update Supplier</button>
</form>

<?php include '../../../includes/footer.php'; ?>
