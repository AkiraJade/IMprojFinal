<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    $_SESSION['error'] = 'Please login to view your cart';
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

// Handle remove from cart action
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $cart_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $cart_id, $customer_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Item removed from cart';
    } else {
        $_SESSION['error'] = 'Failed to remove item from cart';
    }
    header("Location: " . BASE_URL . "/cart/cart.php");
    exit();
}

// Fetch cart items
$stmt = $conn->prepare("SELECT c.id, c.product_id, c.quantity, 
                               p.name as product_name, p.price, p.image, 
                               p.brand, p.stock
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cart_items_result = $stmt->get_result();
$cart_items = [];
$grandTotal = 0;

// Process cart items
while ($item = $cart_items_result->fetch_assoc()) {
    $item['total'] = $item['quantity'] * $item['price'];
    $grandTotal += $item['total'];
    $cart_items[] = $item;
}

// Include header after setting all variables
include '../../includes/header.php';

// Display success/error messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | UrbanThrift</title>
    <link rel="stylesheet" href="/projectIManagement/public/css/style.css">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 2rem;
        }

        .cart-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .cart-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .cart-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .cart-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            align-items: start;
        }

        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 1.5rem;
            background: var(--dark-light);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(155, 77, 224, 0.2);
            transition: var(--transition);
            align-items: center;
        }

        .cart-item:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-glow);
        }

        .cart-item-image {
            width: 120px;
            height: 120px;
            border-radius: var(--radius-md);
            object-fit: cover;
            border: 2px solid rgba(155, 77, 224, 0.1);
        }

        .cart-item-details {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .cart-item-details h3 {
            font-size: 1.3rem;
            color: var(--text-primary);
            margin: 0;
        }

        .cart-item-details .brand {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .cart-item-details .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-light);
            margin-top: 0.5rem;
        }

        .cart-item-details .quantity {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
        }

        .cart-item-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: flex-end;
        }

        .item-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-light);
        }

        .btn-remove {
            background: var(--error);
            color: white;
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .btn-remove:hover {
            background: #d63447;
            transform: translateY(-2px);
        }

        .cart-summary {
            background: var(--dark-light);
            padding: 2rem;
            border-radius: var(--radius-lg);
            border: 2px solid rgba(155, 77, 224, 0.2);
            position: sticky;
            top: 2rem;
        }

        .cart-summary h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            border-bottom: 2px solid rgba(155, 77, 224, 0.2);
            padding-bottom: 1rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .summary-row.total {
            margin-top: 1rem;
            padding-top: 1.5rem;
            border-top: 2px solid rgba(155, 77, 224, 0.2);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-light);
        }

        .payment-method {
            margin: 1.5rem 0;
        }

        .payment-method label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .payment-method select {
            width: 100%;
            padding: 1rem;
            background: var(--dark);
            border: 2px solid rgba(155, 77, 224, 0.2);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .payment-method select:focus {
            border-color: var(--primary);
            outline: none;
        }

        .btn-checkout {
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
            margin-top: 1rem;
            box-shadow: var(--shadow-md);
        }

        .btn-checkout:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-glow);
        }

        .btn-continue {
            width: 100%;
            padding: 1rem;
            background: rgba(155, 77, 224, 0.1);
            border: 2px solid var(--primary);
            color: var(--primary-light);
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 0.75rem;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .btn-continue:hover {
            background: rgba(155, 77, 224, 0.2);
        }

        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--dark-light);
            border-radius: var(--radius-xl);
            border: 2px dashed rgba(155, 77, 224, 0.2);
        }

        .empty-cart-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
        }

        .empty-cart h3 {
            font-size: 1.8rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .empty-cart p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 968px) {
            .cart-content {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }

            .cart-item {
                grid-template-columns: 100px 1fr;
                gap: 1rem;
            }

            .cart-item-actions {
                grid-column: 1 / -1;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding-top: 1rem;
                border-top: 1px solid rgba(155, 77, 224, 0.2);
            }
        }
    </style>
</head>
<body>

<div class="cart-container">
    <div class="cart-header">
        <h1>🛒 Shopping Cart</h1>
        <p>Review your items before checkout</p>
    </div>

    <?php if (count($cart_items) > 0): ?>
    <div class="cart-content">
        <div class="cart-items">
            <?php foreach($cart_items as $item): ?>
            <div class="cart-item">
                <img src="../uploads/<?= htmlspecialchars($item['image']) ?>" 
                     alt="<?= htmlspecialchars($item['product_name']) ?>" 
                     class="cart-item-image">
                
                <div class="cart-item-details">
                    <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                    <p class="brand"><?= htmlspecialchars($item['brand']) ?></p>
                    <p class="price">₱<?= number_format($item['price'], 2) ?></p>
                    <p class="quantity">Quantity: <strong><?= htmlspecialchars($item['quantity']) ?></strong></p>
                    <?php if ($item['stock'] < $item['quantity']): ?>
                        <p style="color: var(--error); font-size: 0.9rem;">⚠️ Only <?= $item['stock'] ?> in stock</p>
                    <?php endif; ?>
                </div>

                <div class="cart-item-actions">
                    <div class="item-total">₱<?= number_format($item['total'], 2) ?></div>
                    <a href="?action=remove&id=<?= $item['id'] ?>" class="btn-remove" 
                       onclick="return confirm('Are you sure you want to remove this item from your cart?')">
                        🗑️ Remove
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <h2>Order Summary</h2>
            
            <div class="summary-row">
                <span>Subtotal (<?= count($cart_items) ?> items)</span>
                <span>₱<?= number_format($grandTotal, 2) ?></span>
            </div>

            <div class="summary-row">
                <span>Shipping</span>
                <span>₱0.00</span>
            </div>

            <form method="POST" action="checkout.php" id="checkoutForm" class="needs-validation" novalidate>
                <div class="payment-method">
                    <label for="payment_method">Payment Method</label>
                    <select name="payment_method" 
                            id="payment_method" 
                            class="form-input"
                            data-required="true"
                            data-pattern-message="Please select a payment method">
                        <option value="">Select a payment method</option>
                        <option value="Cash">💵 Cash</option>
                        <option value="GCash">📱 GCash</option>
                        <option value="Credit Card">💳 Credit Card</option>
                        <option value="Debit Card">💳 Debit Card</option>
                        <option value="Bank Transfer">🏦 Bank Transfer</option>
                    </select>
                </div>

                <!-- Additional Fields for GCash -->
                <div id="gcashFields" class="additional-fields" style="display: none; margin-top: 1rem;">
                    <div class="form-group">
                        <label for="gcash_number">GCash Number</label>
                        <input type="tel" 
                               id="gcash_number" 
                               name="gcash_number" 
                               class="form-input" 
                               placeholder="09XX XXX XXXX"
                               data-pattern="^[0-9]{11}$"
                               data-pattern-message="Please enter a valid 11-digit GCash number"
                               data-required="false">
                    </div>
                </div>

                <!-- Additional Fields for Credit/Debit Card -->
                <div id="cardFields" class="additional-fields" style="display: none; margin-top: 1rem;">
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" 
                               id="card_number" 
                               name="card_number" 
                               class="form-input" 
                               placeholder="1234 5678 9012 3456"
                               data-pattern="^[0-9\s]{13,19}$"
                               data-pattern-message="Please enter a valid card number"
                               data-required="false">
                    </div>
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.5rem;">
                        <div class="form-group">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="text" 
                                   id="expiry_date" 
                                   name="expiry_date" 
                                   class="form-input" 
                                   placeholder="MM/YY"
                                   data-pattern="^(0[1-9]|1[0-2])\/([0-9]{2})$"
                                   data-pattern-message="Please enter a valid expiry date (MM/YY)"
                                   data-required="false">
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" 
                                   id="cvv" 
                                   name="cvv" 
                                   class="form-input" 
                                   placeholder="123"
                                   data-pattern="^[0-9]{3,4}$"
                                   data-pattern-message="Please enter a valid CVV"
                                   data-required="false">
                        </div>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 2rem;">
                    <button type="submit" class="btn-checkout" name="proceed_to_checkout">
                        Proceed to Checkout
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
        <div class="empty-cart">
            <div class="empty-cart-icon">🛒</div>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any items to your cart yet.</p>
            <a href="../shop.php" class="btn-primary" style="display: inline-block; padding: 1rem 2rem; text-decoration: none;">
                Start Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

    <script src="<?= BASE_URL ?>/public/js/form-validation.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle additional fields based on payment method
        const paymentMethod = document.getElementById('payment_method');
        const gcashFields = document.getElementById('gcashFields');
        const cardFields = document.getElementById('cardFields');
        
        if (paymentMethod) {
            paymentMethod.addEventListener('change', function() {
                // Hide all additional fields first
                gcashFields.style.display = 'none';
                cardFields.style.display = 'none';
                
                // Show relevant fields based on selection
                if (this.value === 'GCash') {
                    gcashFields.style.display = 'block';
                    // Make GCash number required when GCash is selected
                    document.getElementById('gcash_number').setAttribute('data-required', 'true');
                    // Make card fields not required
                    document.getElementById('card_number').removeAttribute('data-required');
                    document.getElementById('expiry_date').removeAttribute('data-required');
                    document.getElementById('cvv').removeAttribute('data-required');
                } else if (['Credit Card', 'Debit Card'].includes(this.value)) {
                    cardFields.style.display = 'block';
                    // Make card fields required when card is selected
                    document.getElementById('card_number').setAttribute('data-required', 'true');
                    document.getElementById('expiry_date').setAttribute('data-required', 'true');
                    document.getElementById('cvv').setAttribute('data-required', 'true');
                    // Make GCash number not required
                    document.getElementById('gcash_number').removeAttribute('data-required');
                } else {
                    // For other payment methods, ensure no additional fields are required
                    document.getElementById('gcash_number').removeAttribute('data-required');
                    document.getElementById('card_number').removeAttribute('data-required');
                    document.getElementById('expiry_date').removeAttribute('data-required');
                    document.getElementById('cvv').removeAttribute('data-required');
                }
            });
        }

        // Format card number
        const cardNumber = document.getElementById('card_number');
        if (cardNumber) {
            cardNumber.addEventListener('input', function(e) {
                // Remove all non-digits
                let value = this.value.replace(/\D/g, '');
                // Add space after every 4 digits
                value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
                this.value = value.trim();
            });
        }

        // Format expiry date
        const expiryDate = document.getElementById('expiry_date');
        if (expiryDate) {
            expiryDate.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                this.value = value;
            });
        }

        // Format CVV to only allow numbers and max 4 digits
        const cvv = document.getElementById('cvv');
        if (cvv) {
            cvv.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '').substring(0, 4);
            });
        }
    });

    function confirmDelete(id) {
        if (confirm("Remove this item from your cart?")) {
            window.location.href = "remove.php?id=" + id;
        }
    }
    </script>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>
