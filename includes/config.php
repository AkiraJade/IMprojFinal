<?php
// includes/config.php

// Start session globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "urbanthrift_db";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Define base paths for consistent navigation
define('BASE_URL', '/IMprojFinal/public');
define('ADMIN_URL', BASE_URL . '/admin');

// Email Configuration
if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', 'noreply@urbanthrift.com');
    define('MAIL_FROM_NAME', 'UrbanThrift');
}

// Mailtrap Configuration (for development)
if (($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') && !defined('MAIL_DRIVER')) {
    define('MAIL_DRIVER', 'smtp');
    define('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
    define('MAIL_PORT', 2525); 
    define('MAIL_USERNAME', '634558ff2cfd2d');
    define('MAIL_PASSWORD', '051b5c3758eb67');
    define('MAIL_ENCRYPTION', 'tls');
}

// Load Composer's autoloader for PHPMailer if it exists
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Function: Check Login Redirect
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

// Function: Check Admin Access
function checkAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

// Function: Check Customer Access
function checkCustomer() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}
