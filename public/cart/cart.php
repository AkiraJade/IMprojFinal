<?php
session_start();
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT c.id, c.product_id, c.quantity, p.name as product_name, p.price, p.image, p.brand, p.stock
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$query = $stmt->get_result();

include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | UrbanThrift</title>
    <link rel="stylesheet" href="/IMProj/public/css/style.css">
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

    <?php
    $cart_items = [];
    while($row = mysqli_fetch_assoc($query)) {
        $cart_items[] = $row;
    }
    
    if (count($cart_items) > 0):
        $grandTotal = 0;
    ?>
    
    <div class="cart-content">
        <div class="cart-items">
            <?php foreach($cart_items as $item): 
                $total = $item['quantity'] * $item['price'];
                $grandTotal += $total;
            ?>
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
                    <div class="item-total">₱<?= number_format($total, 2) ?></div>
                    <button class="btn-remove" onclick="confirmDelete(<?= intval($item['id']) ?>)">
                        🗑️ Remove
                    </button>
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

            <div class="summary-row total">
                <span>Total</span>
                <span>₱<?= number_format($grandTotal, 2) ?></span>
            </div>

            <form method="POST" action="checkout.php">
                <div class="payment-method">
                    <label for="payment_method">Payment Method</label>
                    <select name="payment_method" id="payment_method" required>
                        <option value="Cash">💵 Cash</option>
                        <option value="GCash">📱 GCash</option>
                        <option value="Credit Card">💳 Credit Card</option>
                        <option value="Debit Card">💳 Debit Card</option>
                        <option value="Bank Transfer">🏦 Bank Transfer</option>
                    </select>
                </div>

                <button type="submit" class="btn-checkout">
                    Proceed to Checkout ✅
                </button>
            </form>

            <a href="../shop.php" class="btn-continue">Continue Shopping</a>
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

<script>
function confirmDelete(id) {
    if (confirm("Remove this item from your cart?")) {
        window.location.href = "remove.php?id=" + id;
    }
}
</script>

<?php include '../../includes/footer.php'; ?>

</body>
</html>
