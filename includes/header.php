<?php
if (!isset($_SESSION)) { session_start(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UrbanThrift</title>
    <link rel="stylesheet" href="/IMprojFinal/public/css/style.css">
    <link rel="stylesheet" href="/IMprojFinal/public/css/form-validation.css">
</head>
<body>

<header class="header">
    <div class="logo"><a href="/IMprojFinal/index.php" style="color: inherit; text-decoration: none;">UrbanThrift</a></div>
    <nav>
        <ul>
            <li><a href="/IMprojFinal/index.php">Home</a></li>
            <li><a href="/IMprojFinal/public/shop.php">Shop</a></li>
            <li><a href="/IMprojFinal/public/about.php">About</a></li>
            <li><a href="/IMprojFinal/public/contact.php">Contact</a></li>

            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === "customer"): ?>
                <li><a href="/IMprojFinal/public/customer/dashboard.php">My Dashboard</a></li>
                <li><a href="/IMprojFinal/public/cart/cart.php">Cart</a></li>
                <li><a href="/IMprojFinal/public/logout.php">Logout</a></li>
            <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === "admin"): ?>
                <li><a href="/IMprojFinal/public/admin/dashboard.php">Admin</a></li>
                <li><a href="/IMprojFinal/public/logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="/IMprojFinal/public/login.php">Login</a></li>
                <li><a href="/IMprojFinal/public/register.php">Register</a></li>
            <?php endif; ?>

        </ul>
    </nav>
</header>

<main class="main-container">
