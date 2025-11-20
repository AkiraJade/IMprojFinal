<?php
if (!isset($_SESSION)) { session_start(); }
require_once __DIR__ . '/config.php';
?>

</main>

<footer class="footer">
    <div class="footer-content">

        <div class="footer-section">
            <h3>UrbanThrift</h3>
            <p>Thrift Clothing Shop Management System</p>
        </div>

        <div class="footer-section">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>/index.php">Shop</a></li>
                <li><a href="<?= BASE_URL ?>/about.php">About</a></li>
                <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>

                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === "customer"): ?>
                    <li><a href="<?= BASE_URL ?>/customer/dashboard.php">My Account</a></li>
                    <li><a href="<?= BASE_URL ?>/cart/cart.php">Cart</a></li>

                <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === "admin"): ?>
                    <li><a href="<?= ADMIN_URL ?>/dashboard.php">Admin Panel</a></li>

                <?php else: ?>
                    <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
                    <li><a href="<?= BASE_URL ?>/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="footer-section">
            <h4>Help</h4>
            <ul>
                <li><a href="#">FAQs</a></li>
                <li><a href="#">Support</a></li>
                <li><a href="#">Policies</a></li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date("Y") ?> UrbanThrift — All Rights Reserved</p>
    </div>
</footer>

</body>
</html>
