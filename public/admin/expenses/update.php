<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$id = intval($_GET['id']);
$msg = "";

// Fetch expense data
$stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$expense = $result->fetch_assoc();

if (!$expense) {
    header("Location: read.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    $category = trim($_POST['category']);
    $expense_date = trim($_POST['expense_date']);

    if (empty($description) || $amount <= 0 || empty($expense_date)) {
        $msg = "❌ Please fill all required fields with valid data.";
    } else {
        $stmt = $conn->prepare("UPDATE expenses SET description = ?, amount = ?, category = ?, expense_date = ? WHERE id = ?");
        $stmt->bind_param("sdssi", $description, $amount, $category, $expense_date, $id);

        if ($stmt->execute()) {
            $msg = "✅ Expense Updated Successfully!";
            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $expense = $result->fetch_assoc();
        } else {
            $msg = "❌ Failed to update expense.";
        }
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php require_once __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Edit Expense</h2>

        <?php if ($msg): ?>
            <div class="alert alert-<?= strpos($msg, '❌') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <form class="form-box" method="POST" id="updateExpenseForm" novalidate>
            <div class="form-group">
                <label for="description">Description <span class="required">*</span></label>
                <input type="text" 
                       id="description" 
                       name="description" 
                       class="form-input"
                       value="<?= htmlspecialchars($expense['description']) ?>"
                       data-required="true"
                       data-min-length="3"
                       data-max-length="255"
                       data-pattern-message="Description must be between 3 and 255 characters">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="amount">Amount (₱) <span class="required">*</span></label>
                <input type="number" 
                       id="amount" 
                       name="amount" 
                       class="form-input"
                       step="0.01"
                       min="0.01"
                       value="<?= htmlspecialchars($expense['amount']) ?>"
                       data-required="true"
                       data-min="0.01"
                       data-pattern-message="Amount must be greater than 0">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" class="form-input">
                    <option value="Utilities" <?= $expense['category'] == 'Utilities' ? 'selected' : '' ?>>Utilities</option>
                    <option value="Rent" <?= $expense['category'] == 'Rent' ? 'selected' : '' ?>>Rent</option>
                    <option value="Supplies" <?= $expense['category'] == 'Supplies' ? 'selected' : '' ?>>Supplies</option>
                    <option value="Marketing" <?= $expense['category'] == 'Marketing' ? 'selected' : '' ?>>Marketing</option>
                    <option value="Salary" <?= $expense['category'] == 'Salary' ? 'selected' : '' ?>>Salary</option>
                    <option value="Other" <?= $expense['category'] == 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="expense_date">Expense Date <span class="required">*</span></label>
                <input type="date" 
                       id="expense_date" 
                       name="expense_date" 
                       class="form-input"
                       value="<?= htmlspecialchars($expense['expense_date']) ?>"
                       data-required="true"
                       data-pattern-message="Please select a valid date">
                <div class="error-message"></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    💾 Update Expense
                </button>
                <a href="read.php" class="btn-secondary">
                    ❌ Cancel
                </a>
            </div>
        </form>
    </main>
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
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .alert-error {
            background-color: rgba(229, 62, 62, 0.1);
            border: 1px solid rgba(229, 62, 62, 0.3);
            color: #e53e3e;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('updateExpenseForm');
            
            // Form submission handler
            form.addEventListener('submit', function(e) {
                if (validateForm() && confirm('Are you sure you want to update this expense?')) {
                    return true;
                }
                e.preventDefault();
                return false;
            });
            
            // Live validation on blur for all form inputs
            const formInputs = form.querySelectorAll('input, select, textarea');
            formInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                // Remove error class when user starts typing
                input.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid')) {
                        this.classList.remove('is-invalid');
                        const errorElement = this.closest('.form-group')?.querySelector('.error-message') || 
                                         this.parentElement.querySelector('.error-message');
                        if (errorElement) {
                            errorElement.textContent = '';
                        }
                    }
                });
            });
            
            // Initialize form validation
            function validateForm() {
                let isValid = true;
                formInputs.forEach(input => {
                    if (!validateField(input)) {
                        isValid = false;
                    }
                });
                return isValid;
            }
            
            // Validate a single field
            function validateField(field) {
                const value = field.value.trim();
                const errorElement = field.closest('.form-group')?.querySelector('.error-message') || 
                                   field.parentElement.querySelector('.error-message');
                
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
                
                // Min value validation
                const min = field.getAttribute('min') || field.getAttribute('data-min');
                if (min !== null && parseFloat(value) < parseFloat(min)) {
                    showError(field, `Value must be at least ${min}`);
                    return false;
                }
                
                // Date validation
                if (field.type === 'date') {
                    const selectedDate = new Date(value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (selectedDate > today) {
                        showError(field, 'Expense date cannot be in the future');
                        return false;
                    }
                }
                
                // If we got here, the field is valid
                field.classList.remove('is-invalid');
                if (errorElement) {
                    errorElement.textContent = '';
                }
                return true;
            }
            
            // Show error message
            function showError(field, message) {
                field.classList.add('is-invalid');
                const errorElement = field.closest('.form-group')?.querySelector('.error-message') || 
                                   field.parentElement.querySelector('.error-message');
                if (errorElement) {
                    errorElement.textContent = message;
                }
                field.focus();
            }
        });
        </script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
