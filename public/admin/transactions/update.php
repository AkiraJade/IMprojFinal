<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$order_id = intval($_GET['id']);

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, c.name as customer_name, c.email as customer_email
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: read.php");
    exit();
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image, p.stock
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);

$message = "";
$message_type = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = trim($_POST['payment_method']);
    $status = trim($_POST['status']);
    
    // Get current status before update
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $current_status = $stmt->get_result()->fetch_assoc()['status'];
    
    // Update order
    $stmt = $conn->prepare("UPDATE orders SET payment_method = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $payment_method, $status, $order_id);
    
    if ($stmt->execute()) {
        $message = "✅ Order updated successfully!";
        $message_type = "success";
        
        // Refresh order data
        $stmt = $conn->prepare("
            SELECT o.*, c.name as customer_name, c.email as customer_email
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        // Send status update email if status changed
        if ($current_status !== $status) {
            require_once __DIR__ . '/../../../includes/EmailService.php';
            $emailService = new EmailService();
            
            // Get order items for the email
            $stmt = $conn->prepare("
                SELECT oi.*, p.name as product_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $items_result = $stmt->get_result();
            $items = [];
            while ($row = $items_result->fetch_assoc()) {
                $items[] = $row;
            }
            
            // Prepare order data for email
            $order_data = [
                'id' => $order_id,
                'order_number' => 'ORD' . str_pad($order_id, 6, '0', STR_PAD_LEFT),
                'total_amount' => $order['total_amount'],
                'created_at' => $order['order_date'],
                'items' => $items,
                'payment_method' => $payment_method,
                'status' => $status,
                'tracking_number' => $order['tracking_number'] ?? null
            ];
            
            // Send status update email
            $emailService->sendOrderStatusUpdate(
                $order_data,
                [
                    'id' => $order['customer_id'],
                    'name' => $order['customer_name'],
                    'email' => $order['customer_email']
                ],
                $status
            );
        }
    } else {
        $message = "❌ Failed to update order.";
        $message_type = "error";
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php require_once __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Edit Transaction #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></h2>
        <a href="read.php" class="btn-secondary">← Back to Sales</a>
        <a href="view.php?id=<?= $order_id ?>" class="btn-view">👁 View Details</a>

        <?php if($message): ?>
            <div style="padding: 1rem; margin: 1rem 0; border-radius: 8px; 
                        background: <?= $message_type == 'success' ? 'rgba(0, 217, 165, 0.15)' : 'rgba(255, 71, 87, 0.15)' ?>;
                        color: <?= $message_type == 'success' ? '#00D9A5' : '#FF4757' ?>;
                        border: 1px solid <?= $message_type == 'success' ? 'rgba(0, 217, 165, 0.3)' : 'rgba(255, 71, 87, 0.3)' ?>;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Order Information -->
        <div style="background: var(--dark-light); padding: 1.5rem; border-radius: var(--radius-lg); margin: 2rem 0; border: 1px solid rgba(155, 77, 224, 0.2);">
            <h3 style="color: var(--primary-light); margin-bottom: 1rem;">Order Information</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">Order ID</p>
                    <p style="color: var(--text-primary); font-weight: 600;">#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">Customer</p>
                    <p style="color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars($order['customer_name'] ?? 'Unknown') ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">Order Date</p>
                    <p style="color: var(--text-primary); font-weight: 600;"><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">Total Amount</p>
                    <p style="color: var(--primary-light); font-weight: 700; font-size: 1.2rem;">₱<?= number_format($order['total_amount'], 2) ?></p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <h3 style="color: var(--primary-light); margin: 2rem 0 1rem 0;">📦 Order Items</h3>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr>
                    <td><img src="/IMprojFinal/public/uploads/<?= htmlspecialchars($item['image']) ?>" height="50" style="border-radius: 8px;" alt="<?= htmlspecialchars($item['product_name']) ?>"></td>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>₱<?= number_format($item['price'], 2) ?></td>
                    <td>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: rgba(155, 77, 224, 0.1); font-weight: 700;">
                    <td colspan="4" style="text-align: right;">TOTAL:</td>
                    <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Edit Form -->
        <h3 style="color: var(--primary-light); margin: 2rem 0 1rem 0;">✏ Edit Order Details</h3>
        <form method="POST" id="updateTransactionForm" class="form-box" style="max-width: 600px;" novalidate>
            <div class="form-group">
                <label for="payment_method">Payment Method <span class="required">*</span></label>
                <select id="payment_method" name="payment_method" class="form-input" data-required="true">
                    <option value="">-- Select Payment Method --</option>
                    <option value="Cash" <?= $order['payment_method'] == 'Cash' ? 'selected' : '' ?>>💵 Cash</option>
                    <option value="GCash" <?= $order['payment_method'] == 'GCash' ? 'selected' : '' ?>>📱 GCash</option>
                    <option value="Credit Card" <?= $order['payment_method'] == 'Credit Card' ? 'selected' : '' ?>>💳 Credit Card</option>
                    <option value="Bank Transfer" <?= $order['payment_method'] == 'Bank Transfer' ? 'selected' : '' ?>>🏦 Bank Transfer</option>
                </select>
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="status">Order Status <span class="required">*</span></label>
                <select id="status" name="status" class="form-input" data-required="true">
                    <option value="">-- Select Status --</option>
                    <option value="Pending" <?= $order['status'] == 'Pending' ? 'selected' : '' ?>>⏳ Pending</option>
                    <option value="Processing" <?= $order['status'] == 'Processing' ? 'selected' : '' ?>>🔄 Processing</option>
                    <option value="Completed" <?= $order['status'] == 'Completed' ? 'selected' : '' ?>>✅ Completed</option>
                    <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
                </select>
                <div class="error-message"></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    💾 Save Changes
                </button>
                <a href="read.php" class="btn-secondary">
                    ❌ Cancel
                </a>
            </div>
        </form>

        <div style="margin-top: 2rem; padding: 1rem; background: rgba(255, 176, 32, 0.1); border-radius: 8px; border: 1px solid rgba(255, 176, 32, 0.3);">
            <p style="color: #FFB020; margin: 0;">
                <strong>⚠️ Note:</strong> Changing the order status will not automatically update product stock. 
                Stock was already adjusted when the order was created.
            </p>
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
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('updateTransactionForm');
            
            // Form submission handler
            form.addEventListener('submit', function(e) {
                if (validateForm() && confirm('Are you sure you want to update this transaction?')) {
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
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
