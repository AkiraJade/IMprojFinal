<?php
session_start();
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (isset($_POST['update'])) {
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $email, $customer_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully!');</script>";
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    } else {
        echo "<script>alert('Update failed!');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>My Profile</title>
<style>
body { background:#0A0A0F; color:#fff; font-family:Arial; }
form {
    width:50%; margin:40px auto;
    background:#12121A; padding:20px;
    border-radius:10px;
}
input {
    width:100%; padding:10px; margin:10px 0;
    border:none; background:#24242E; color:#fff;
    border-radius:6px;
}
button {
    background:#9b4de0; padding:10px;
    width:100%; border:none; border-radius:6px;
    color:#fff; cursor:pointer;
}
button:hover { background:#7e32bb; }
h2 { text-align:center; }
</style>
</head>
<body>

<h2>👤 My Profile</h2>

<form method="POST">
    <label>Username</label>
    <input type="text" name="name" value="<?= htmlspecialchars($user['username']) ?>" required>

    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

    <button type="submit" name="update">Update Profile ✅</button>
</form>

</body>
</html>
