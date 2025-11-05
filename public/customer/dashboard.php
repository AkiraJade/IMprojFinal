<?php
include __DIR__ . '/../../includes/config.php';

// ✅ Prevent direct access if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: ../login.php");
    exit();
}

// Get username for greeting
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<?php include '../../includes/header.php'; ?>

<div class="dashboard-container">
    <h2>Welcome, <?php echo htmlspecialchars($user['username']); ?> 👋</h2>

    <div class="dash-cards">
        <a class="card" href="../index.php">
            🛍 Browse Shop
        </a>

        <a class="card" href="orders.php">
            📦 My Orders
        </a>

        <a class="card" href="profile.php">
            👤 Edit Profile
        </a>

        <a class="card logout" href="../logout.php">
            🚪 Logout
        </a>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
