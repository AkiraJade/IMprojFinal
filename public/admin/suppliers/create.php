<?php
include __DIR__ . '/../../includes/config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

if (isset($_POST['save'])) {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    $stmt = $conn->prepare("INSERT INTO suppliers(name, contact_person, contact_number, email, address) VALUES(?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $contact_person, $contact_number, $email, $address);
    $stmt->execute();
    
    header("Location: read.php");
    exit();
}

include '../../../includes/header.php';
?>

<h2>Add Supplier</h2>

<form method="POST">
    <label>Name:</label><br>
    <input type="text" name="name" required><br><br>

    <label>Contact Person:</label><br>
    <input type="text" name="contact_person" required><br><br>

    <label>Contact Number:</label><br>
    <input type="text" name="contact_number" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Address:</label><br>
    <textarea name="address" required></textarea><br><br>

    <button type="submit" name="save">Save Supplier</button>
</form>

<?php include '../../../includes/footer.php'; ?>
