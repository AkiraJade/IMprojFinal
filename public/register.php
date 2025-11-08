<?php
require_once __DIR__ . '/../includes/config.php';

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $profile_photo = null;

    // Validation
    if ($password !== $confirm_password) {
        $message = "❌ Passwords do not match!";
        $message_type = "error";
    } elseif (strlen($password) < 6) {
        $message = "❌ Password must be at least 6 characters!";
        $message_type = "error";
    } elseif (empty($phone)) {
        $message = "❌ Phone number is required!";
        $message_type = "error";
    } elseif (empty($address)) {
        $message = "❌ Address is required!";
        $message_type = "error";
    } else {
        $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "❌ Email already registered!";
            $message_type = "error";
        } else {
            // Handle profile photo upload
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($_FILES['profile_photo']['type'], $allowed_types)) {
                    $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                    $profile_photo = uniqid() . '_' . time() . '.' . $file_extension;
                    $upload_dir = __DIR__ . '/uploads/profiles/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_dir . $profile_photo);
                }
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone, address, profile_photo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $email, $hashed_password, $phone, $address, $profile_photo);

            if ($stmt->execute()) {
                header("Location: login.php?registered=true");
                exit();
            } else {
                $message = "❌ Registration failed!";
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UrbanThrift</title>
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
        .register-container {
            position: relative;
            z-index: 2;
            width: 100%;
            display: flex;
            min-height: 100vh;
        }

        /* Left Section - Registration Form */
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
            max-width: 550px;
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

        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background: rgba(155, 77, 224, 0.2);
            border-radius: 2px;
            overflow: hidden;
            display: none;
        }

        .password-strength.active {
            display: block;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak { background: #FF4757; width: 33%; }
        .strength-medium { background: #FFB020; width: 66%; }
        .strength-strong { background: #00D9A5; width: 100%; }

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

        /* Right Section - Benefits */
        .right-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 4rem;
            position: relative;
        }

        .benefits-content {
            max-width: 600px;
        }

        .benefits-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .benefits-title {
            font-size: 3.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #E0AAFF 0%, #C77DFF 50%, #9b4de0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            letter-spacing: -2px;
        }

        .benefits-subtitle {
            font-size: 1.3rem;
            color: #B8B8C8;
            font-weight: 300;
        }

        .benefits-list {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .benefit-card {
            background: rgba(155, 77, 224, 0.05);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(155, 77, 224, 0.2);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
        }

        .benefit-card:hover {
            background: rgba(155, 77, 224, 0.1);
            transform: translateX(10px);
            border-color: rgba(155, 77, 224, 0.4);
        }

        .benefit-icon {
            font-size: 3rem;
            flex-shrink: 0;
        }

        .benefit-text h3 {
            color: #FFFFFF;
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .benefit-text p {
            color: #B8B8C8;
            line-height: 1.6;
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .register-container {
                flex-direction: column-reverse;
            }

            .left-section, .right-section {
                padding: 2rem;
            }

            .benefits-title {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 640px) {
            .form-title {
                font-size: 2rem;
            }

            .benefits-title {
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

    <div class="register-container">
        <!-- Left Section - Form -->
        <div class="left-section">
            <div class="form-wrapper">
                <div class="form-content">
                    <div class="form-header">
                        <h1 class="form-title">Join UrbanThrift</h1>
                        <p class="form-subtitle">Start your sustainable fashion journey</p>
                    </div>

                    <?php if($message): ?>
                        <div class="alert <?= $message_type ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="registerForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <div class="input-wrapper">
                                <input type="text" 
                                       name="username" 
                                       class="form-input" 
                                       placeholder="Enter your full name" 
                                       required>
                                <span class="input-icon">👤</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <div class="input-wrapper">
                                <input type="email" 
                                       name="email" 
                                       class="form-input" 
                                       placeholder="your.email@example.com" 
                                       required>
                                <span class="input-icon">📧</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <div class="input-wrapper">
                                <input type="tel" 
                                       name="phone" 
                                       class="form-input" 
                                       placeholder="+63 XXX XXX XXXX" 
                                       required>
                                <span class="input-icon">📱</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <div class="input-wrapper">
                                <input type="text" 
                                       name="address" 
                                       class="form-input" 
                                       placeholder="Your complete address" 
                                       required>
                                <span class="input-icon">📍</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Profile Photo (Optional)</label>
                            <div class="input-wrapper">
                                <input type="file" 
                                       name="profile_photo" 
                                       class="form-input" 
                                       accept="image/*"
                                       style="padding: 0.75rem;">
                                <span class="input-icon">📷</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <div class="input-wrapper">
                                <input type="password" 
                                       name="password" 
                                       id="password"
                                       class="form-input" 
                                       placeholder="Create a strong password" 
                                       required>
                                <span class="input-icon">🔒</span>
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <div class="input-wrapper">
                                <input type="password" 
                                       name="confirm_password" 
                                       class="form-input" 
                                       placeholder="Re-enter your password" 
                                       required>
                                <span class="input-icon">✓</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">
                            Create Account
                        </button>
                    </form>

                    <div class="form-divider">
                        <span class="divider-text">OR</span>
                    </div>

                    <div class="form-footer">
                        <p>
                            Already have an account? 
                            <a href="login.php">Sign In</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Section - Benefits -->
        <div class="right-section">
            <div class="benefits-content">
                <div class="benefits-header">
                    <h2 class="benefits-title">Why Join Us?</h2>
                    <p class="benefits-subtitle">Experience the future of thrift shopping</p>
                </div>

                <div class="benefits-list">
                    <div class="benefit-card">
                        <div class="benefit-icon">🌍</div>
                        <div class="benefit-text">
                            <h3>Make an Impact</h3>
                            <p>Every purchase helps reduce fashion waste and supports sustainable living</p>
                        </div>
                    </div>

                    <div class="benefit-card">
                        <div class="benefit-icon">💎</div>
                        <div class="benefit-text">
                            <h3>Exclusive Deals</h3>
                            <p>Get access to members-only discounts and early access to new arrivals</p>
                        </div>
                    </div>

                    <div class="benefit-card">
                        <div class="benefit-icon">🎯</div>
                        <div class="benefit-text">
                            <h3>Personalized Experience</h3>
                            <p>Receive curated recommendations based on your style preferences</p>
                        </div>
                    </div>

                    <div class="benefit-card">
                        <div class="benefit-icon">🚚</div>
                        <div class="benefit-text">
                            <h3>Fast & Free Shipping</h3>
                            <p>Enjoy quick delivery on all orders with no hidden fees</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthDiv = document.getElementById('passwordStrength');
        const strengthBar = document.getElementById('strengthBar');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password.length === 0) {
                strengthDiv.classList.remove('active');
                return;
            }

            strengthDiv.classList.add('active');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;

            strengthBar.className = 'strength-bar';
            
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });
    </script>
</body>
</html>