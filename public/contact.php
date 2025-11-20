<?php 
require_once __DIR__ . '/../includes/config.php';
include '../includes/header.php';

$message = '';
$message_type = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_content = trim($_POST['message'] ?? '');
    
    // Basic validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    }
    
    if (empty($message_content)) {
        $errors[] = 'Message is required';
    }
    
    // If no validation errors, save to database
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message_content);
            
            if ($stmt->execute()) {
                $message = 'Thank you for your message! We will get back to you soon.';
                $message_type = 'success';
                
                // Clear form
                $_POST = [];
            } else {
                throw new Exception('Failed to save your message. Please try again.');
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
            error_log('Contact form error: ' . $e->getMessage());
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | UrbanThrift</title>
    <link rel="stylesheet" href="/IMprojFinal/public/css/style.css">
    <style>
        .contact-container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 2rem;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .contact-header h1 {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .contact-header p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        .contact-info {
            background: var(--dark-light);
            padding: 2.5rem;
            border-radius: var(--radius-xl);
            border: 1px solid rgba(155, 77, 224, 0.2);
        }

        .contact-info h2 {
            font-size: 1.8rem;
            color: var(--primary-light);
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            padding: 1.5rem;
            background: var(--dark);
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(155, 77, 224, 0.1);
            transition: var(--transition);
        }

        .info-item:hover {
            border-color: var(--primary);
            transform: translateX(5px);
        }

        .info-icon {
            font-size: 2rem;
            min-width: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-details h3 {
            font-size: 1.1rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .info-details p {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.6;
        }

        .contact-form {
            background: var(--dark-light);
            padding: 2.5rem;
            border-radius: var(--radius-xl);
            border: 1px solid rgba(155, 77, 224, 0.2);
        }

        .contact-form h2 {
            font-size: 1.8rem;
            color: var(--primary-light);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            background: var(--dark);
            border: 2px solid rgba(155, 77, 224, 0.2);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(155, 77, 224, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .btn-submit {
            width: 100%;
            padding: 1.25rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-glow);
        }

        .map-container {
            grid-column: 1 / -1;
            background: var(--dark-light);
            padding: 2rem;
            border-radius: var(--radius-xl);
            border: 1px solid rgba(155, 77, 224, 0.2);
            margin-top: 2rem;
        }

        .map-container h2 {
            font-size: 1.8rem;
            color: var(--primary-light);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
        }

        .social-link {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--dark);
            border: 2px solid rgba(155, 77, 224, 0.2);
            border-radius: 50%;
            color: var(--primary-light);
            font-size: 1.5rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .social-link:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: translateY(-5px);
        }

        @media (max-width: 968px) {
            .contact-content {
                grid-template-columns: 1fr;
            }

            .contact-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<div class="contact-container">
    <div class="contact-header">
        <h1>📧 Get In Touch</h1>
        <p>Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
    </div>

    <div class="contact-content">
        <!-- Contact Information -->
        <div class="contact-info">
            <h2>Contact Information</h2>
            
            <div class="info-item">
                <div class="info-icon">📍</div>
                <div class="info-details">
                    <h3>Address</h3>
                    <p>123 Fashion Street, Quezon City<br>Metro Manila, Philippines 1100</p>
                </div>
            </div>

            <div class="info-item">
                <div class="info-icon">📞</div>
                <div class="info-details">
                    <h3>Phone</h3>
                    <p>+63 912 345 6789<br>+63 917 123 4567</p>
                </div>
            </div>

            <div class="info-item">
                <div class="info-icon">✉️</div>
                <div class="info-details">
                    <h3>Email</h3>
                    <p>support@urbanthrift.com<br>info@urbanthrift.com</p>
                </div>
            </div>

            <div class="info-item">
                <div class="info-icon">🕒</div>
                <div class="info-details">
                    <h3>Business Hours</h3>
                    <p>Monday - Sunday<br>9:00 AM - 7:00 PM</p>
                </div>
            </div>

            <div class="social-links">
                <a href="#" class="social-link" title="Facebook">📘</a>
                <a href="#" class="social-link" title="Instagram">📷</a>
                <a href="#" class="social-link" title="Twitter">🐦</a>
                <a href="#" class="social-link" title="TikTok">🎵</a>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form">
            <h2>Send us a Message</h2>
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?>" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius-md); background: <?= $message_type === 'success' ? '#10b981' : '#ef4444' ?>; color: white;">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            <form action="" method="POST" id="contactForm" novalidate>
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-input"
                           placeholder="Enter your full name"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                           required
                           minlength="2"
                           maxlength="100"
                           pattern="^[a-zA-Z\s]+"
                           data-pattern-message="Please enter a valid name (letters and spaces only)">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-input"
                           placeholder="your.email@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required
                           pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                           data-pattern-message="Please enter a valid email address">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number (Optional)</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           class="form-input"
                           placeholder="+63 XXX XXX XXXX"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                           pattern="^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$"
                           data-pattern-message="Please enter a valid phone number">
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" 
                           id="subject" 
                           name="subject" 
                           class="form-input"
                           placeholder="What is this about?"
                           value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                           required
                           minlength="5"
                           maxlength="100">
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" 
                              name="message" 
                              class="form-input"
                              placeholder="Write your message here..."
                              required
                              minlength="10"
                              maxlength="1000"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn-submit">Send Message 📨</button>
            </form>
        </div>
    </div>

    <!-- Map Section -->
    <div class="map-container">
        <h2>📍 Find Us</h2>
        <div style="background: var(--dark); padding: 3rem; border-radius: var(--radius-md); text-align: center; border: 2px dashed rgba(155, 77, 224, 0.2);">
            <p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 1rem;">
                🗺️ Visit us at our location in Quezon City
            </p>
            <p style="color: var(--text-muted);">
                [Map integration placeholder - Google Maps or similar can be embedded here]
            </p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>
