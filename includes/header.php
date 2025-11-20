<?php
if (!isset($_SESSION)) { session_start(); }
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UrbanThrift</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>

<header class="header">
    <div class="logo"><a href="<?= BASE_URL ?>/../index.php" style="color: inherit; text-decoration: none;">UrbanThrift</a></div>
    <nav>
        <ul>
            <li><a href="<?= BASE_URL ?>/../index.php">Home</a></li>
            <li><a href="<?= BASE_URL ?>/shop.php">Shop</a></li>
            <li><a href="<?= BASE_URL ?>/about.php">About</a></li>
            <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>

            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === "customer"): ?>
                <li><a href="<?= BASE_URL ?>/customer/dashboard.php">My Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/cart/cart.php">Cart</a></li>
                <li><a href="<?= BASE_URL ?>/logout.php">Logout</a></li>
            <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === "admin"): ?>
                <li><a href="<?= ADMIN_URL ?>/dashboard.php">Admin</a></li>
                <li><a href="<?= BASE_URL ?>/logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
                <li><a href="<?= BASE_URL ?>/register.php">Register</a></li>
            <?php endif; ?>

        </ul>
    </nav>
</header>

<main class="main-container">
