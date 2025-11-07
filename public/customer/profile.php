<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (isset($_POST['update'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Validate
    if (empty($username) || empty($email) || empty($phone) || empty($address)) {
        $message = "All fields are required!";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $username, $email, $phone, $address, $customer_id);
        
        if ($stmt->execute()) {
            $message = "Profile updated successfully!";
            $message_type = "success";
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $message = "Update failed!";
            $message_type = "error";
        }
    }
}

include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | UrbanThrift</title>
    <link rel="stylesheet" href="/projectIManagement/public/css/style.css">
    <style>
        .profile-container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 2rem;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .profile-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .profile-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .alert {
            padding: 1.25rem;
            border-radius: var(--radius-md);
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

        .alert.success {
            background: rgba(0, 217, 165, 0.15);
            color: var(--success);
            border: 1px solid rgba(0, 217, 165, 0.3);
        }

        .alert.error {
            background: rgba(255, 71, 87, 0.15);
            color: var(--error);
            border: 1px solid rgba(255, 71, 87, 0.3);
        }

        .profile-card {
            background: var(--dark-light);
            padding: 3rem;
            border-radius: var(--radius-xl);
            border: 1px solid rgba(155, 77, 224, 0.2);
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(155, 77, 224, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .profile-content {
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            box-shadow: 0 10px 30px rgba(155, 77, 224, 0.4);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 1rem;
            background: var(--dark);
            border: 2px solid rgba(155, 77, 224, 0.2);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 1rem;
            font-family: inherit;
            transition: var(--transition);
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(26, 26, 36, 0.9);
            box-shadow: 0 0 0 4px rgba(155, 77, 224, 0.1);
        }

        .form-input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            padding: 1.25rem 3rem;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(155, 77, 224, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(155, 77, 224, 0.4);
        }

        .btn-secondary {
            background: rgba(155, 77, 224, 0.1);
            border: 2px solid var(--primary);
            color: var(--primary-light);
        }

        .btn-secondary:hover {
            background: rgba(155, 77, 224, 0.2);
        }

        .info-text {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 2rem;
        }

        #editMode {
            display: none;
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="profile-container">
    <div class="profile-header">
        <h1>👤 My Profile</h1>
        <p>Manage your account information</p>
    </div>

    <?php if($message): ?>
        <div class="alert <?= $message_type ?>">
            <?= $message_type === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="profile-card">
        <div class="profile-content">
            <div class="profile-avatar">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>

            <form method="POST" id="profileForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" 
                               name="username" 
                               class="form-input" 
                               value="<?= htmlspecialchars($user['username']) ?>" 
                               id="usernameInput"
                               disabled
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" 
                               name="email" 
                               class="form-input" 
                               value="<?= htmlspecialchars($user['email']) ?>" 
                               id="emailInput"
                               disabled
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" 
                               name="phone" 
                               class="form-input" 
                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                               id="phoneInput"
                               disabled
                               placeholder="+63 XXX XXX XXXX"
                               required>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Address</label>
                        <textarea name="address" 
                                  class="form-textarea" 
                                  id="addressInput"
                                  disabled
                                  placeholder="Your complete address"
                                  required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-actions" id="viewMode">
                    <button type="button" class="btn btn-primary" onclick="enableEdit()">
                        ✏️ Edit Profile
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        ← Back to Dashboard
                    </a>
                </div>

                <div class="form-actions" id="editMode">
                    <button type="submit" name="update" class="btn btn-primary">
                        ✅ Save Changes
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="cancelEdit()">
                        ❌ Cancel
                    </button>
                </div>
            </form>

            <p class="info-text">
                💡 Keep your information up to date for better service
            </p>
        </div>
    </div>
</div>

<script>
    const inputs = ['usernameInput', 'emailInput', 'phoneInput', 'addressInput'];
    
    function enableEdit() {
        // Enable all inputs
        inputs.forEach(id => {
            document.getElementById(id).disabled = false;
        });
        
        // Toggle button visibility
        document.getElementById('viewMode').style.display = 'none';
        document.getElementById('editMode').style.display = 'flex';
    }
    
    function cancelEdit() {
        // Reload the page to reset form
        window.location.reload();
    }
    
    // Auto-format phone number
    const phoneInput = document.getElementById('phoneInput');
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);
        
        if (value.length >= 10) {
            value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})/, '+$1 $2 $3 $4');
        } else if (value.length >= 6) {
            value = value.replace(/(\d{2})(\d{3})(\d{3})/, '+$1 $2 $3');
        } else if (value.length >= 3) {
            value = value.replace(/(\d{2})(\d{3})/, '+$1 $2');
        }
        
        e.target.value = value;
    });
</script>

<?php include '../../includes/footer.php'; ?>

</body>
</html>
