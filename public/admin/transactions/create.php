<?php
// Include config and check admin access
require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../login.php");
    exit();
}

$msg = "";

// Debug customer query
$customers = $conn->query("SELECT * FROM customers ORDER BY name");
if (!$customers) {
    die("Error fetching customers: " . $conn->error);
}

// Debug: Check number of customers
$customer_count = $customers->num_rows;
error_log("Number of customers found: " . $customer_count);

// Debug: Fetch all customers and log them
$all_customers = [];
while ($row = $customers->fetch_assoc()) {
    $all_customers[] = $row;
}
error_log("Customers data: " . print_r($all_customers, true));

// Reset pointer back to start for the actual display
$customers->data_seek(0);

// Debug products query
$products = $conn->query("SELECT id, name, price, stock, image FROM products WHERE stock > 0 ORDER BY name");
if (!$products) {
    die("Error fetching products: " . $conn->error);
}

// Debug: Check number of products
$product_count = $products->num_rows;
error_log("Number of products found: " . $product_count);

// Debug: Fetch all products and log them
$all_products = [];
while ($row = $products->fetch_assoc()) {
    $all_products[] = $row;
}
error_log("Products data: " . print_r($all_products, true));

// Reset pointer back to start for the actual display
$products->data_seek(0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = intval($_POST['customer_id']);
    $payment_method = trim($_POST['payment_method']);
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    
    if (empty($customer_id) || empty($product_ids)) {
        $msg = "❌ Please select customer and at least one product.";
    } else {
        $conn->begin_transaction();
        
        try {
            $total = 0;
            $order_items = [];
            
            // Validate products and calculate total
            foreach ($product_ids as $index => $product_id) {
                $qty = intval($quantities[$index]);
                if ($qty <= 0) continue;
                
                $stmt = $conn->prepare("SELECT price, stock FROM products WHERE id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                
                if (!$product || $product['stock'] < $qty) {
                    throw new Exception("Insufficient stock for product ID: $product_id");
                }
                
                $order_items[] = [
                    'product_id' => $product_id,
                    'quantity' => $qty,
                    'price' => $product['price']
                ];
                
                $total += $product['price'] * $qty;
            }
            
            if (empty($order_items)) {
                throw new Exception("No valid products selected");
            }
            
            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (customer_id, order_date, payment_method, total_amount, status) 
                                   VALUES (?, NOW(), ?, ?, 'Completed')");
            $stmt->bind_param("isd", $customer_id, $payment_method, $total);
            $stmt->execute();
            $order_id = $conn->insert_id;
            
            // Insert order items and update stock
            foreach ($order_items as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $stmt->execute();
                
                // Update stock
                $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stmt->execute();
            }
            
            $conn->commit();
            $msg = "✅ Transaction created successfully! Order ID: $order_id";
            
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "❌ Failed: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php
    // Config already included at the top
    require_once '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Create Manual Transaction</h2>

        <p class="msg"><?= $msg ?></p>

        <form class="form-box" method="POST" id="transactionForm" novalidate>
            <div class="form-group">
                <label for="customer_id">Customer <span class="required">*</span></label>
                <select id="customer_id" name="customer_id" class="form-input" data-required="true">
                    <option value="">Select Customer</option>
                    <?php 
                    if ($customer_count > 0): 
                        $customers->data_seek(0); // Reset pointer to start
                        while($customer = $customers->fetch_assoc()): 
                    ?>
                        <option value="<?= htmlspecialchars($customer['id']) ?>">
                            <?= htmlspecialchars($customer['name']) ?> - 
                            <?= htmlspecialchars($customer['email']) ?>
                        </option>
                    <?php 
                        endwhile;
                        $customers->data_seek(0); // Reset pointer again for any future use
                    else: 
                    ?>
                        <option value="">No customers found in database</option>
                    <?php endif; ?>
                </select>
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method" class="form-input">
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
                <div class="error-message"></div>
            </div>

            <h3>Products</h3>
            <div id="products-container">
                <div class="product-row" style="display: grid; grid-template-columns: 2fr 1fr 50px; gap: 10px; margin-bottom: 10px;">
                    <div style="position: relative;">
                        <select name="product_id[]" class="form-input product-select" data-required="true" data-product-select>
                            <option value="">Select Product</option>
                            <?php 
                            if ($products && $products->num_rows > 0) {
                                $products->data_seek(0); // Reset pointer to start
                                while($product = $products->fetch_assoc()): 
                            ?>
                                <option value="<?= $product['id'] ?>" 
                                        data-stock="<?= $product['stock'] ?>"
                                        data-price="<?= $product['price'] ?>">
                                    <?= htmlspecialchars($product['name']) ?> - 
                                    ₱<?= number_format($product['price'], 2) ?> 
                                    (Stock: <?= $product['stock'] ?>)
                                </option>
                            <?php 
                                endwhile;
                                $products->data_seek(0); // Reset pointer again for any future use
                            } else {
                                echo '<option value="">No products available</option>';
                            }
                            ?>
                        </select>
                        <div class="error-message"></div>
                    </div>
                    <div style="position: relative;">
                        <input type="number" 
                               name="quantity[]" 
                               class="form-input quantity-input" 
                               placeholder="Qty" 
                               min="1" 
                               value="1"
                               data-required="true"
                               data-min="1">
                        <div class="error-message"></div>
                    </div>
                    <button type="button" onclick="removeProduct(this)" class="btn-delete" style="background: #e53e3e; color: white; border: none; border-radius: 4px; cursor: pointer; height: 100%;">
                        ✖
                    </button>
                </div>
            </div>

            <button type="button" onclick="addProduct()" class="btn-secondary" style="margin-bottom: 1.5rem;">
                ➕ Add Product
            </button>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    💳 Create Transaction
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

.product-row {
    margin-bottom: 1rem;
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 4px;
}

.btn-delete {
    background: #e53e3e;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
}

.btn-delete:hover {
    background: #c53030;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('transactionForm');
    
    // Form submission handler
    form.addEventListener('submit', function(e) {
        if (validateForm() && confirm('Are you sure you want to create this transaction?')) {
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
            
            // If this is a product select, update the max quantity
            if (this.hasAttribute('data-product-select')) {
                updateQuantityMax(this);
            }
            
            // If this is a quantity input, validate against max stock
            if (this.classList.contains('quantity-input')) {
                validateQuantity(this);
            }
        });
    });
    
    // Initialize quantity max values for existing product selects
    document.querySelectorAll('[data-product-select]').forEach(select => {
        updateQuantityMax(select);
    });
});

function addProduct() {
    const container = document.getElementById('products-container');
    const firstRow = container.querySelector('.product-row');
    const newRow = firstRow.cloneNode(true);
    
    // Clear the values in the new row
    const selects = newRow.querySelectorAll('select');
    const inputs = newRow.querySelectorAll('input');
    const errorMessages = newRow.querySelectorAll('.error-message');
    
    selects.forEach(select => {
        select.selectedIndex = 0;
        select.classList.remove('is-invalid');
    });
    
    inputs.forEach(input => {
        if (input.type === 'number') {
            input.value = '1';
        }
        input.classList.remove('is-invalid');
    });
    
    errorMessages.forEach(div => {
        div.textContent = '';
    });
    
    // Add event listeners to the new row
    newRow.querySelector('select').addEventListener('change', function() {
        updateQuantityMax(this);
    });
    
    newRow.querySelector('input[type="number"]').addEventListener('input', function() {
        validateQuantity(this);
    });
    
    container.appendChild(newRow);
}

function removeProduct(btn) {
    const container = document.getElementById('products-container');
    const rows = container.querySelectorAll('.product-row');
    
    if (rows.length > 1) {
        btn.closest('.product-row').remove();
    } else {
        showError(rows[0].querySelector('select'), 'At least one product is required');
    }
}

function updateQuantityMax(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const quantityInput = selectElement.closest('.product-row').querySelector('.quantity-input');
    
    if (selectedOption && selectedOption.value) {
        const maxStock = parseInt(selectedOption.getAttribute('data-stock') || '0');
        quantityInput.setAttribute('data-max', maxStock);
        quantityInput.max = maxStock;
        
        // If current value exceeds max, adjust it
        if (parseInt(quantityInput.value) > maxStock) {
            quantityInput.value = maxStock > 0 ? maxStock : 1;
        }
    } else {
        quantityInput.removeAttribute('data-max');
        quantityInput.removeAttribute('max');
    }
    
    // Validate the quantity after updating max
    validateQuantity(quantityInput);
}

function validateQuantity(inputElement) {
    const row = inputElement.closest('.product-row');
    const select = row.querySelector('select[data-product-select]');
    const selectedOption = select.options[select.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        showError(select, 'Please select a product first');
        return false;
    }
    
    const maxStock = parseInt(selectedOption.getAttribute('data-stock') || '0');
    const quantity = parseInt(inputElement.value) || 0;
    
    if (quantity < 1) {
        showError(inputElement, 'Quantity must be at least 1');
        return false;
    }
    
    if (quantity > maxStock) {
        showError(inputElement, `Only ${maxStock} available in stock`);
        return false;
    }
    
    // Clear any existing errors
    inputElement.classList.remove('is-invalid');
    const errorElement = inputElement.nextElementSibling;
    if (errorElement && errorElement.classList.contains('error-message')) {
        errorElement.textContent = '';
    }
    
    return true;
}

// Initialize validation for all fields
function validateForm() {
    let isValid = true;
    const form = document.getElementById('transactionForm');
    const formInputs = form.querySelectorAll('input, select, textarea');
    
    // First, validate all fields
    formInputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    // Then, validate product rows specifically
    const productRows = document.querySelectorAll('.product-row');
    let hasValidProduct = false;
    
    productRows.forEach(row => {
        const select = row.querySelector('select[data-product-select]');
        const quantityInput = row.querySelector('.quantity-input');
        
        // Validate product selection
        if (!select.value) {
            showError(select, 'Please select a product');
            isValid = false;
        }
        
        // Validate quantity
        if (select.value && !validateQuantity(quantityInput)) {
            isValid = false;
        }
        
        if (select.value) {
            hasValidProduct = true;
        }
    });
    
    // Ensure at least one product is selected
    if (!hasValidProduct) {
        const firstSelect = document.querySelector('select[data-product-select]');
        showError(firstSelect, 'Please add at least one product');
        isValid = false;
    }
    
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
    
    // Min validation
    const min = field.getAttribute('data-min');
    if (min && parseInt(value) < parseInt(min)) {
        showError(field, `Value must be at least ${min}`);
        return false;
    }
    
    // Max validation
    const max = field.getAttribute('data-max');
    if (max && parseInt(value) > parseInt(max)) {
        showError(field, `Value cannot exceed ${max}`);
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
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
