<?php
session_start();
require_once __DIR__ . '/../includes/config.php';


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: shop.php");
    exit();
}

// Calculate condition badge color
$condition_class = '';
switch($product['condition_type']) {
    case 'Like New':
        $condition_class = 'badge-success';
        break;
    case 'Good':
        $condition_class = 'badge-info';
        break;
    case 'Slightly Used':
        $condition_class = 'badge-warning';
        break;
}

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | UrbanThrift</title>
    <link rel="stylesheet" href="/IMProj/public/css/style.css">
    <style>
        .product-detail-container {
            max-width: 1200px;
            margin: 3rem auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            padding: 2rem;
        }

        .product-image-section {
            position: relative;
        }

        .product-main-image {
            width: 100%;
            height: 600px;
            object-fit: cover;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 2px solid rgba(155, 77, 224, 0.2);
            transition: var(--transition);
        }

        .product-main-image:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-glow);
        }

        .product-info-section {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .product-breadcrumb {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .product-breadcrumb a {
            color: var(--primary-light);
            text-decoration: none;
        }

        .product-title {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .product-brand {
            font-size: 1.25rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .product-price {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-light);
            margin: 1rem 0;
        }

        .product-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: var(--dark-light);
            border-radius: var(--radius-md);
            border: 1px solid rgba(155, 77, 224, 0.1);
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .detail-value {
            font-size: 1.1rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .condition-badge {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.95rem;
            width: fit-content;
        }

        .badge-success {
            background: rgba(0, 217, 165, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .badge-info {
            background: rgba(78, 159, 255, 0.2);
            color: var(--info);
            border: 1px solid var(--info);
        }

        .badge-warning {
            background: rgba(255, 176, 32, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .stock-status {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: rgba(0, 217, 165, 0.1);
            border-radius: var(--radius-md);
            border: 1px solid rgba(0, 217, 165, 0.3);
        }

        .stock-indicator {
            width: 12px;
            height: 12px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-add-cart {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: var(--text-primary);
            padding: 1.25rem 2rem;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-add-cart:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-glow);
        }

        .btn-back {
            background: var(--gray);
            padding: 1.25rem 2rem;
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-back:hover {
            background: var(--gray-light);
            transform: translateY(-2px);
        }

        .product-description {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--dark-light);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary);
        }

        .product-description h3 {
            color: var(--primary-light);
            margin-bottom: 1rem;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .product-detail-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .product-main-image {
                height: 400px;
            }

            .product-title {
                font-size: 2rem;
            }

            .product-price {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>

<div class="product-detail-container">
    <!-- Product Image Section -->
    <div class="product-image-section">
        <img src="uploads/<?= htmlspecialchars($product['image']) ?>" 
             alt="<?= htmlspecialchars($product['name']) ?>" 
             class="product-main-image">
    </div>

    <!-- Product Info Section -->
    <div class="product-info-section">
        <div class="product-breadcrumb">
            <a href="shop.php">Shop</a> / 
            <?= htmlspecialchars($product['category']) ?> / 
            <?= htmlspecialchars($product['name']) ?>
        </div>

        <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
        <p class="product-brand"><?= htmlspecialchars($product['brand']) ?></p>

        <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>

        <!-- Product Details Grid -->
        <div class="product-details-grid">
            <div class="detail-item">
                <span class="detail-label">Category</span>
                <span class="detail-value"><?= htmlspecialchars($product['category']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Size</span>
                <span class="detail-value"><?= htmlspecialchars($product['size']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Condition</span>
                <span class="condition-badge <?= $condition_class ?>">
                    <?= htmlspecialchars($product['condition_type']) ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Stock</span>
                <span class="detail-value"><?= htmlspecialchars($product['stock']) ?> available</span>
            </div>
        </div>

        <!-- Stock Status -->
        <?php if ($product['stock'] > 0): ?>
        <div class="stock-status">
            <span class="stock-indicator"></span>
            <span style="color: var(--success); font-weight: 600;">In Stock - Ready to Ship</span>
        </div>
        <?php else: ?>
        <div class="stock-status" style="background: rgba(255, 71, 87, 0.1); border-color: rgba(255, 71, 87, 0.3);">
            <span style="color: var(--error); font-weight: 600;">❌ Out of Stock</span>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="shop.php" class="btn-back">
                ← Back to Shop
            </a>
            
            <?php if ($product['stock'] > 0): ?>
                <?php if (isset($_SESSION['customer_id'])): ?>
                    <a href="cart/add.php?id=<?= intval($product['id']) ?>" class="btn-add-cart">
                        🛒 Add to Cart
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-add-cart">
                        🔒 Login to Purchase
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Product Description -->
        <div class="product-description">
            <h3>About This Item</h3>
            <p>
                Premium quality <?= htmlspecialchars($product['condition_type']) ?> 
                <?= htmlspecialchars($product['category']) ?> from 
                <?= htmlspecialchars($product['brand']) ?>. 
                This carefully curated thrift piece offers excellent value and sustainable fashion choice.
                Perfect for those looking for authentic style at affordable prices.
            </p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>