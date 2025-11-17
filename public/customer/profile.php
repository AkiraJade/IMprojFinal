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
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Log the POST data
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $profile_photo = $user['profile_photo']; // Keep existing photo by default

    // Validate
    if (empty($username) || empty($email) || empty($phone) || empty($address)) {
        $message = "All fields are required!";
        $message_type = "error";
    } else {
        // Handle profile photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($_FILES['profile_photo']['type'], $allowed_types)) {
                $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                $profile_photo = uniqid() . '_' . time() . '.' . $file_extension;
                $upload_dir = __DIR__ . '/../uploads/profiles/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Delete old photo if exists
                if ($user['profile_photo'] && file_exists($upload_dir . $user['profile_photo'])) {
                    unlink($upload_dir . $user['profile_photo']);
                }
                
                move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_dir . $profile_photo);
            }
        }

        // Log the values being updated
        error_log("Updating profile with values - Username: $username, Email: $email, Phone: $phone, Address: $address, Photo: $profile_photo, UserID: $customer_id");
        
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ?, profile_photo = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            $message = "Database error. Please try again later.";
            $message_type = "error";
        } else {
            $stmt->bind_param("sssssi", $username, $email, $phone, $address, $profile_photo, $customer_id);
            
            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->affected_rows > 0) {
                    // Send profile update notification
                    require_once __DIR__ . '/../../includes/EmailService.php';
                    $emailService = new EmailService();
                    
                    // Get updated user data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->bind_param("i", $customer_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    
                    // Send notification email
                    $emailService->sendProfileUpdateNotification([
                        'id' => $user['id'],
                        'name' => $user['username'],
                        'email' => $user['email'],
                        'phone' => $user['phone'],
                        'address' => $user['address']
                    ]);
                    
                    $message = "Profile updated successfully! A confirmation has been sent to your email.";
                    $message_type = "success";
                    
                    // Clear any POST data to prevent resubmission
                    $_POST = array();
                } else {
                    $message = "No changes were made to your profile.";
                    $message_type = "info";
                }
            } else {
                $error_message = $stmt->error ?: $conn->error;
                $message = "Update failed: " . $error_message;
                $message_type = "error";
                
                // Log the error for debugging
                error_log("Profile update error: " . $error_message);
                error_log("POST data: " . print_r($_POST, true));
                error_log("SQL: UPDATE users SET username = '$username', email = '$email', phone = '$phone', address = '$address', profile_photo = '$profile_photo' WHERE id = $customer_id");
            }
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
        
        .error-message {
            color: #ff4444;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            display: none;
        }
        
        input.error, textarea.error {
            border-color: #ff4444 !important;
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

            <form method="POST" id="profileForm" enctype="multipart/form-data" onsubmit="return validateForm()">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Profile Photo</label>
                        <?php if ($user['profile_photo']): ?>
                            <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($user['profile_photo']) ?>" 
                                 style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem; border: 3px solid var(--primary);"
                                 id="profileImage">
                        <?php endif; ?>
                        <input type="file" 
                               name="profile_photo" 
                               class="form-input" 
                               accept="image/*"
                               id="photoInput"
                               data-pattern="\.(jpg|jpeg|png|gif|webp)$"
                               data-pattern-message="Please upload a valid image file (JPG, PNG, GIF, or WebP)"
                               onchange="previewImage(this)"
                               disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="usernameInput">Full Name</label>
                        <input type="text" 
                               name="username" 
                               class="form-input" 
                               value="<?= htmlspecialchars($user['username']) ?>" 
                               id="usernameInput"
                               data-required="true"
                               data-min-length="2"
                               data-max-length="100"
                               data-pattern="^[a-zA-Z\s]+"
                               data-pattern-message="Please enter a valid name (letters and spaces only)"
                               disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="emailInput">Email Address</label>
                        <input type="email" 
                               name="email" 
                               class="form-input" 
                               value="<?= htmlspecialchars($user['email']) ?>" 
                               id="emailInput"
                               data-required="true"
                               data-pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"
                               data-pattern-message="Please enter a valid email address"
                               disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phoneInput">Phone Number</label>
                        <input type="tel" 
                               name="phone" 
                               class="form-input" 
                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                               id="phoneInput"
                               data-required="true"
                               data-pattern="^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$"
                               data-pattern-message="Please enter a valid phone number"
                               placeholder="+63 XXX XXX XXXX"
                               disabled>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label" for="addressInput">Address</label>
                        <textarea name="address" 
                                  class="form-textarea" 
                                  id="addressInput"
                                  data-required="true"
                                  data-min-length="10"
                                  data-max-length="500"
                                  data-pattern-message="Please enter a valid address"
                                  placeholder="Your complete address"
                                  disabled><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
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
                    <button type="submit" name="update" class="btn btn-primary" onclick="return validateForm()">
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
    const form = document.getElementById('profileForm');
    const inputs = ['usernameInput', 'emailInput', 'phoneInput', 'addressInput', 'photoInput'];
    
    // Function to validate a single input
    function validateInput(input) {
        const value = input.value.trim();
        const required = input.getAttribute('data-required') === 'true';
        const minLength = input.getAttribute('data-min-length');
        const maxLength = input.getAttribute('data-max-length');
        const pattern = input.getAttribute('data-pattern');
        const errorMessage = input.getAttribute('data-pattern-message') || 'Invalid input';
        
        // Check required field
        if (required && !value) {
            showError(input, 'This field is required');
            return false;
        }
        
        // Check min length
        if (minLength && value.length < parseInt(minLength)) {
            showError(input, `Must be at least ${minLength} characters`);
            return false;
        }
        
        // Check max length
        if (maxLength && value.length > parseInt(maxLength)) {
            showError(input, `Must be less than ${maxLength} characters`);
            return false;
        }
        
        // Check pattern
        if (pattern && value) {
            const regex = new RegExp(pattern);
            if (!regex.test(value)) {
                showError(input, errorMessage);
                return false;
            }
        }
        
        // If all validations pass
        clearError(input);
        return true;
    }
    
    // Function to show error message
    function showError(input, message) {
        const formGroup = input.closest('.form-group') || input.closest('.form-textarea').closest('.form-group');
        let errorElement = formGroup.querySelector('.error-message');
        
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            formGroup.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        input.style.borderColor = '#ff4444';
    }
    
    // Function to clear error message
    function clearError(input) {
        const formGroup = input.closest('.form-group') || input.closest('.form-textarea').closest('.form-group');
        const errorElement = formGroup?.querySelector('.error-message');
        
        if (errorElement) {
            errorElement.style.display = 'none';
        }
        
        if (input) {
            input.style.borderColor = '';
        }
    }
    
    // Function to validate all inputs
    function validateForm() {
        // Prevent the default form submission
        event.preventDefault();
        
        let isValid = true;
        
        inputs.forEach(id => {
            const input = document.getElementById(id);
            if (input && !input.disabled) {
                if (!validateInput(input)) {
                    isValid = false;
                }
            }
        });
        
        if (isValid) {
            // If form is valid, submit it programmatically
            const form = document.getElementById('profileForm');
            const formData = new FormData(form);
            
            // Add a hidden field to indicate form submission
            formData.append('update', '1');
            
            // Submit the form using fetch API
            fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            })
            .then(html => {
                // If we get here, the form submission failed
                document.documentElement.innerHTML = html;
                // Try to show any error messages
                const errorDiv = document.querySelector('.alert.error');
                if (errorDiv) {
                    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving your profile. Please try again.');
            });
        }
        
        return false; // Always return false to prevent default form submission
    }
    
    function enableEdit() {
        // Enable all inputs
        inputs.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.disabled = false;
                // Add event listeners for validation
                input.addEventListener('blur', () => validateInput(input));
            }
        });
        
        // Toggle buttons
        document.getElementById('viewMode').style.display = 'none';
        document.getElementById('editMode').style.display = 'flex';
        
        // Focus on first input
        document.getElementById('usernameInput').focus();
    }
    
    function cancelEdit() {
        // Disable all inputs and clear errors
        inputs.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.disabled = true;
                clearError(input);
            }
        });
        
        // Reset form to original values
        const form = document.getElementById('profileForm');
        form.reset();
        
        // Toggle buttons
        document.getElementById('viewMode').style.display = 'flex';
        document.getElementById('editMode').style.display = 'none';
        
        // Reset any validation errors
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(error => error.style.display = 'none');
        
        // Reset image preview if exists
        const profileImage = document.getElementById('profileImage');
        if (profileImage) {
            profileImage.src = '<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($user['profile_photo'] ?? '') ?>';
        }
    }
    
    function previewImage(input) {
        if (input.files && input.files[0]) {
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(input.files[0].type)) {
                showError(input, 'Please upload a valid image file (JPG, PNG, GIF, or WebP)');
                input.value = ''; // Clear the file input
                return;
            }
            
            // Validate file size (max 2MB)
            const maxSize = 2 * 1024 * 1024; // 2MB
            if (input.files[0].size > maxSize) {
                showError(input, 'Image size should be less than 2MB');
                input.value = ''; // Clear the file input
                return;
            }
            
            // Clear any previous errors
            clearError(input);
            
            // Preview the image
            const reader = new FileReader();
            reader.onload = function(e) {
                const profileImage = document.getElementById('profileImage');
                if (profileImage) {
                    profileImage.src = e.target.result;
                } else {
                    // If no image element exists, create one
                    const img = document.createElement('img');
                    img.id = 'profileImage';
                    img.src = e.target.result;
                    img.style.width = '100px';
                    img.style.height = '100px';
                    img.style.borderRadius = '50%';
                    img.style.objectFit = 'cover';
                    img.style.marginBottom = '1rem';
                    img.style.border = '3px solid var(--primary)';
                    input.parentNode.insertBefore(img, input);
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Initialize form validation
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('profileForm');
        
        // Add input event listeners for real-time validation
        inputs.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('blur', function() {
                    if (!input.disabled) {
                        // Trigger validation for this field
                        validateInput(input);
                    }
                });
            }
        });
    });
</script>

<script>
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
