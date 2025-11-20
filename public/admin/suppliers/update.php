<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

// Initialize variables
$msg = '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Validate ID
if (!$id) {
    header("Location: read.php");
    exit();
}

// Fetch existing data
$stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$supplier = $stmt->get_result()->fetch_assoc();

if (!$supplier) {
    header("Location: read.php");
    exit();
}

// Update processing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    // Validate inputs
    if (empty($name) || empty($contact_person) || empty($email)) {
        $msg = "❌ Please fill all required fields.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "❌ Please enter a valid email address.";
    } else {
        // Check for duplicate supplier (name or email)
        $checkStmt = $conn->prepare("SELECT id FROM suppliers WHERE (name = ? OR email = ?) AND id != ?");
        $checkStmt->bind_param("ssi", $name, $email, $id);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            $msg = "❌ A supplier with this name or email already exists.";
        } else {
            if (empty($msg)) {
                $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact_person=?, contact_number=?, email=?, address=? WHERE id=?");
                $stmt->bind_param("sssssi", $name, $contact_person, $contact_number, $email, $address, $id);

                if ($stmt->execute()) {
                    $_SESSION['success'] = '✅ Supplier updated successfully';
                    header("refresh:1; url=read.php");
                    exit();
                } else {
                    $msg = "❌ Update failed: " . $conn->error;
                }
            }
        }
    }
    
    // Refresh supplier data
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $supplier = $stmt->get_result()->fetch_assoc();
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php
 require_once '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Edit Supplier</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form class="form-box" method="POST" id="supplierForm" novalidate>
            <div class="form-group">
                <label for="name">Supplier Name <span class="required">*</span></label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       class="form-input"
                       value="<?= htmlspecialchars($supplier['name'] ?? '') ?>"
                       data-required="true"
                       data-min-length="2"
                       data-max-length="100"
                       data-pattern="^[\w\s\-\.]{2,100}$"
                       data-pattern-message="Supplier name must be 2-100 characters and can only contain letters, numbers, spaces, hyphens, and periods">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="contact_person">Contact Person <span class="required">*</span></label>
                <input type="text" 
                       id="contact_person" 
                       name="contact_person"
                       class="form-input"
                       value="<?= htmlspecialchars($supplier['contact_person'] ?? '') ?>"
                       data-required="true"
                       data-min-length="2"
                       data-max-length="100"
                       data-pattern-message="Contact person name must be 2-100 characters">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="tel" 
                       id="contact_number" 
                       name="contact_number"
                       class="form-input"
                       value="<?= htmlspecialchars($supplier['contact_number'] ?? '') ?>"
                       data-pattern="^[\d\s\-+()]{10,20}$"
                       data-pattern-message="Please enter a valid phone number (10-20 digits, may include spaces, hyphens, +, and parentheses)">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" 
                       id="email" 
                       name="email"
                       class="form-input"
                       value="<?= htmlspecialchars($supplier['email'] ?? '') ?>"
                       data-required="true"
                       data-pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"
                       data-pattern-message="Please enter a valid email address">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" 
                          name="address" 
                          class="form-input"
                          rows="3"
                          data-max-length="255"
                          data-pattern-message="Address cannot exceed 255 characters"><?= 
                    htmlspecialchars($supplier['address'] ?? '') 
                ?></textarea>
                <div class="error-message"></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    💾 Update Supplier
                </button>
                <a href="read.php" class="btn-secondary">
                    ❌ Cancel
                </a>
            </div>
            
            <style>
            .form-group {
                margin-bottom: 1.5rem;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 500;
                color: var(--text-primary);
            }
            
            .form-input {
                width: 100%;
                padding: 0.75rem;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 1rem;
                transition: border-color 0.3s ease, box-shadow 0.3s ease;
            }
            
            textarea.form-input {
                min-height: 100px;
                resize: vertical;
            }
            
            .form-input:focus {
                border-color: var(--primary);
                box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.2);
                outline: none;
            }
            
            .form-input.is-invalid {
                border-color: #e53e3e;
                padding-right: 2.5rem;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='%23e53e3e' viewBox='-2 -2 7 7'%3e%3cpath stroke='%23e53e3e' d='M0 0l3 3m0-3L0 3'/%3e%3ccircle r='.5'/%3e%3ccircle cx='3' r='.5'/%3e%3ccircle cy='3' r='.5'/%3e%3ccircle cx='3' cy='3' r='.5'/%3e%3c/svg%3E");
                background-repeat: no-repeat;
                background-position: right 0.75rem center;
                background-size: 1.25rem 1.25rem;
            }
            
            .error-message {
                color: #e53e3e;
                font-size: 0.875rem;
                margin-top: 0.25rem;
                min-height: 1.25rem;
            }
            
            .form-actions {
                display: flex;
                gap: 1rem;
                margin-top: 2rem;
                padding-top: 1rem;
                border-top: 1px solid #eee;
            }
            
            .btn-primary, .btn-secondary {
                padding: 0.75rem 1.5rem;
                border: none;
                border-radius: 4px;
                font-size: 1rem;
                font-weight: 500;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                transition: all 0.2s ease;
                text-decoration: none;
            }
            
            .btn-primary {
                background-color: var(--primary);
                color: white;
            }
            
            .btn-primary:hover {
                background-color: var(--primary-dark);
                transform: translateY(-1px);
            }
            
            .btn-secondary {
                background-color: #f0f0f0;
                color: #333;
                border: 1px solid #ddd;
            }
            
            .btn-secondary:hover {
                background-color: #e0e0e0;
                transform: translateY(-1px);
            }
            
            .required {
                color: #e53e3e;
                font-weight: bold;
            }
            </style>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('supplierForm');
                
                // Form submission handler
                form.addEventListener('submit', function(e) {
                    if (validateForm() && confirm('Are you sure you want to update this supplier?')) {
                        return true;
                    }
                    e.preventDefault();
                    return false;
                });
                
                // Live validation on blur
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        validateField(this);
                    });
                    
                    // Remove error class when user starts typing
                    input.addEventListener('input', function() {
                        if (this.classList.contains('is-invalid')) {
                            this.classList.remove('is-invalid');
                            const errorElement = this.nextElementSibling;
                            if (errorElement && errorElement.classList.contains('error-message')) {
                                errorElement.textContent = '';
                            }
                        }
                    });
                });
                
                // Initialize validation for all fields
                function validateForm() {
                    let isValid = true;
                    inputs.forEach(input => {
                        if (!validateField(input)) {
                            isValid = false;
                        }
                    });
                    return isValid;
                }
                
                // Validate a single field
                function validateField(field) {
                    const value = field.value.trim();
                    const errorElement = field.nextElementSibling;
                    
                    // Skip validation for hidden fields
                    if (field.type === 'hidden') return true;
                    
                    // Required validation
                    if (field.getAttribute('data-required') === 'true' && !value) {
                        showError(field, 'This field is required');
                        return false;
                    }
                    
                    // Skip further validation if the field is empty and not required
                    if (!value) return true;
                    
                    // Min length validation
                    const minLength = field.getAttribute('data-min-length');
                    if (minLength && value.length < parseInt(minLength)) {
                        showError(field, `Must be at least ${minLength} characters`);
                        return false;
                    }
                    
                    // Max length validation
                    const maxLength = field.getAttribute('data-max-length');
                    if (maxLength && value.length > parseInt(maxLength)) {
                        showError(field, `Cannot exceed ${maxLength} characters`);
                        return false;
                    }
                    
                    // Pattern validation
                    const pattern = field.getAttribute('data-pattern');
                    if (pattern) {
                        const regex = new RegExp(pattern);
                        if (!regex.test(value)) {
                            const customMessage = field.getAttribute('data-pattern-message') || 'Invalid format';
                            showError(field, customMessage);
                            return false;
                        }
                    }
                    
                    // Email validation (for email fields)
                    if (field.type === 'email') {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(value)) {
                            showError(field, 'Please enter a valid email address');
                            return false;
                        }
                    }
                    
                    // If we got here, the field is valid
                    field.classList.remove('is-invalid');
                    if (errorElement && errorElement.classList.contains('error-message')) {
                        errorElement.textContent = '';
                    }
                    return true;
                }
                
                // Show error message
                function showError(field, message) {
                    field.classList.add('is-invalid');
                    const errorElement = field.nextElementSibling;
                    if (errorElement && errorElement.classList.contains('error-message')) {
                        errorElement.textContent = message;
                    }
                    field.focus();
                }
            });
            </script>
        </form>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
