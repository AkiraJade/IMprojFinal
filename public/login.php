<?php
require_once __DIR__ . '/../includes/config.php';

$message = "";
$message_type = "";

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: customer/dashboard.php");
    }
    exit();
}

// Handle registration success message
if (isset($_GET['registered']) && $_GET['registered'] === 'true') {
    $message = "✅ Registration successful! Please login.";
    $message_type = "success";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = "❌ Please fill in all fields!";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password, role, is_active FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if account is active
            $is_active = isset($user['is_active']) ? $user['is_active'] : 1;
            if (!$is_active) {
                $message = "❌ Your account has been deactivated. Please contact admin.";
                $message_type = "error";
            } elseif (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: customer/dashboard.php");
                }
                exit();
            } else {
                $message = "❌ Invalid email or password!";
                $message_type = "error";
            }
        } else {
            $message = "❌ Invalid email or password!";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UrbanThrift</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #0A0A0F;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .gradient-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            animation: float 20s infinite ease-in-out;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #E0AAFF 0%, transparent 70%);
            top: -10%;
            right: -10%;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, #9b4de0 0%, transparent 70%);
            bottom: -10%;
            left: -10%;
            animation-delay: 7s;
        }

        .orb-3 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, #C77DFF 0%, transparent 70%);
            top: 40%;
            left: 20%;
            animation-delay: 14s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(-50px, 50px) scale(1.1); }
            66% { transform: translate(30px, -30px) scale(0.9); }
        }

        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(155, 77, 224, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(155, 77, 224, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 1;
            pointer-events: none;
        }

        /* Main Container */
        .login-container {
            position: relative;
            z-index: 2;
            width: 100%;
            display: flex;
            min-height: 100vh;
        }

        /* Left Section - Login Form */
        .left-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 4rem;
            position: relative;
        }

        .form-wrapper {
            width: 100%;
            max-width: 500px;
            background: rgba(18, 18, 26, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            padding: 3rem;
            border: 1px solid rgba(155, 77, 224, 0.2);
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.5),
                0 0 100px rgba(155, 77, 224, 0.1);
            position: relative;
            overflow: hidden;
        }

        .form-wrapper::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(155, 77, 224, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .form-content {
            position: relative;
            z-index: 1;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .form-title {
            font-size: 3rem;
            font-weight: 800;
            color: #FFFFFF;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #FFFFFF 0%, #E0AAFF 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-subtitle {
            color: #858596;
            font-size: 1.1rem;
            font-weight: 400;
        }

        .alert {
            padding: 1.25rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            font-weight: 600;
            text-align: center;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert.error {
            background: rgba(255, 71, 87, 0.15);
            color: #FF4757;
            border: 1px solid rgba(255, 71, 87, 0.3);
        }

        .alert.success {
            background: rgba(0, 217, 165, 0.15);
            color: #00D9A5;
            border: 1px solid rgba(0, 217, 165, 0.3);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            color: #B8B8C8;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.25rem;
            color: #858596;
            transition: all 0.3s ease;
        }

        .form-input {
            width: 100%;
            padding: 1.25rem 1.25rem 1.25rem 3.5rem;
            background: rgba(26, 26, 36, 0.6);
            border: 2px solid rgba(155, 77, 224, 0.2);
            border-radius: 16px;
            color: #FFFFFF;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #9b4de0;
            background: rgba(26, 26, 36, 0.9);
            box-shadow: 0 0 0 4px rgba(155, 77, 224, 0.1);
        }

        .form-input:focus + .input-icon {
            color: #9b4de0;
        }

        .form-input::placeholder {
            color: #4A4A54;
        }

        .forgot-password {
            text-align: right;
            margin-top: 0.5rem;
        }

        .forgot-password a {
            color: #9b4de0;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .forgot-password a:hover {
            color: #C77DFF;
            text-decoration: underline;
        }

        .btn-submit {
            width: 100%;
            padding: 1.5rem;
            background: linear-gradient(135deg, #9b4de0 0%, #7c3cc1 100%);
            border: none;
            border-radius: 16px;
            color: #FFFFFF;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 
                0 10px 30px rgba(155, 77, 224, 0.3),
                0 0 0 0 rgba(155, 77, 224, 0.4);
            position: relative;
            overflow: hidden;
            margin-top: 1rem;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 15px 40px rgba(155, 77, 224, 0.4),
                0 0 0 4px rgba(155, 77, 224, 0.2);
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .form-divider {
            text-align: center;
            margin: 2rem 0;
            position: relative;
        }

        .form-divider::before,
        .form-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: rgba(155, 77, 224, 0.2);
        }

        .form-divider::before { left: 0; }
        .form-divider::after { right: 0; }

        .divider-text {
            color: #858596;
            font-size: 0.9rem;
            background: rgba(18, 18, 26, 0.8);
            padding: 0 1rem;
            position: relative;
        }

        .form-footer {
            text-align: center;
            margin-top: 2rem;
        }

        .form-footer p {
            color: #858596;
            font-size: 1rem;
        }

        .form-footer a {
            color: #9b4de0;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .form-footer a:hover {
            color: #C77DFF;
            text-decoration: underline;
        }

        /* Right Section - Welcome */
        .right-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 4rem;
            position: relative;
        }

        .welcome-content {
            max-width: 600px;
            text-align: center;
        }

        .welcome-logo {
            font-size: 5rem;
            margin-bottom: 2rem;
        }

        .welcome-title {
            font-size: 3.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #E0AAFF 0%, #C77DFF 50%, #9b4de0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            letter-spacing: -2px;
        }

        .welcome-subtitle {
            font-size: 1.3rem;
            color: #B8B8C8;
            font-weight: 300;
            margin-bottom: 3rem;
            line-height: 1.6;
        }

        .features-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            text-align: left;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(155, 77, 224, 0.05);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid rgba(155, 77, 224, 0.2);
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            background: rgba(155, 77, 224, 0.1);
            transform: translateX(10px);
            border-color: rgba(155, 77, 224, 0.4);
        }

        .feature-icon {
            font-size: 2rem;
            flex-shrink: 0;
        }

        .feature-text {
            color: #B8B8C8;
            font-size: 1rem;
            line-height: 1.5;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .login-container {
                flex-direction: column-reverse;
            }

            .left-section, .right-section {
                padding: 2rem;
            }

            .welcome-title {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 640px) {
            .form-title {
                font-size: 2rem;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .form-wrapper {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="gradient-orb orb-1"></div>
        <div class="gradient-orb orb-2"></div>
        <div class="gradient-orb orb-3"></div>
    </div>
    <div class="grid-overlay"></div>

    <div class="login-container">
        <!-- Left Section - Form -->
        <div class="left-section">
            <div class="form-wrapper">
                <div class="form-content">
                    <div class="form-header">
                        <h1 class="form-title">Welcome Back</h1>
                        <p class="form-subtitle">Sign in to continue your journey</p>
                    </div>

                    <?php if($message): ?>
                        <div class="alert <?= $message_type ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="loginForm" novalidate>
                        <div class="form-group">
                            <label class="form-label" for="login-email">Email Address</label>
                            <div class="input-wrapper">
                                <input type="email" 
                                       id="login-email"
                                       name="email" 
                                       class="form-input" 
                                       placeholder="your.email@example.com"
                                       data-required="true"
                                       data-pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"
                                       data-pattern-message="Please enter a valid email address"
                                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                                <span class="input-icon">📧</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="login-password">Password</label>
                            <div class="input-wrapper">
                                <input type="password" 
                                       id="login-password"
                                       name="password" 
                                       class="form-input" 
                                       placeholder="Enter your password"
                                       data-required="true"
                                       data-min-length="6"
                                       data-pattern-message="Password must be at least 6 characters">
                                <span class="input-icon">🔒</span>
                            </div>
                            <div class="forgot-password">
                                <a href="#">Forgot Password?</a>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">
                            Sign In
                        </button>
                    </form>

                    <div class="form-divider">
                        <span class="divider-text">OR</span>
                    </div>

                    <div class="form-footer">
                        <p>
                            Don't have an account? 
                            <a href="register.php">Create Account</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Section - Welcome -->
        <div class="right-section">
            <div class="welcome-content">
                <div class="welcome-logo">🛍️</div>
                <h2 class="welcome-title">UrbanThrift</h2>
                <p class="welcome-subtitle">
                    Your gateway to sustainable fashion and conscious shopping
                </p>

                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon">🌱</div>
                        <div class="feature-text">
                            <strong>Eco-Friendly Shopping</strong> - Every purchase makes a difference
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">💎</div>
                        <div class="feature-text">
                            <strong>Premium Quality</strong> - Curated collection of the best thrift finds
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">🚚</div>
                        <div class="feature-text">
                            <strong>Fast Delivery</strong> - Get your items delivered quickly and safely
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">🔐</div>
                        <div class="feature-text">
                            <strong>Secure Platform</strong> - Your data and transactions are protected
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>