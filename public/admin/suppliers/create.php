<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
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

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Add Supplier</h2>

        <form class="form-box" method="POST">
            <label>Name</label>
            <input type="text" name="name" required>

            <label>Contact Person</label>
            <input type="text" name="contact_person" required>

            <label>Contact Number</label>
            <input type="text" name="contact_number" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Address</label>
            <textarea name="address" required></textarea>

            <button type="submit" name="save" class="btn-primary">Save Supplier</button>
            <a href="read.php" class="btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
