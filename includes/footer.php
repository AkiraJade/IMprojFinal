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
                <li><a href="/IMprojFinal/public/index.php">Shop</a></li>
                <li><a href="/IMprojFinal/public/about.php">About</a></li>
                <li><a href="/IMprojFinal/public/contact.php">Contact</a></li>

                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === "customer"): ?>
                    <li><a href="/IMprojFinal/public/customer/dashboard.php">My Account</a></li>
                    <li><a href="/IMprojFinal/public/cart/cart.php">Cart</a></li>

                <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === "admin"): ?>
                    <li><a href="/IMprojFinal/public/admin/dashboard.php">Admin Panel</a></li>

                <?php else: ?>
                    <li><a href="/IMprojFinal/public/login.php">Login</a></li>
                    <li><a href="/IMprojFinal/public/register.php">Register</a></li>
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
    <p class="footer-bottom">&copy; <?= date("Y") ?> UrbanThrift — All Rights Reserved</p>
</footer>

<script src="/IMprojFinal/public/js/form-validation.js"></script>
</body>
</html>
