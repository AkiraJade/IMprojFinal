<?php 
require_once __DIR__ . '/../includes/config.php';

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT * FROM products WHERE stock > 0";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR brand LIKE ? OR category LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

// Sorting
switch($sort) {
    case 'price_low':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY price DESC";
        break;
    case 'name':
        $sql .= " ORDER BY name ASC";
        break;
    default:
        $sql .= " ORDER BY created_at DESC";
}

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Get categories for filter
$categories_query = $conn->query("SELECT DISTINCT category FROM products WHERE stock > 0 ORDER BY category");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Thrift Clothing | UrbanThrift</title>
    <link rel="stylesheet" href="/projectIManagement/public/css/style.css">
    <style>
        .shop-header {
            text-align: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, var(--dark-light) 0%, var(--dark) 100%);
            border-radius: var(--radius-xl);
            margin-bottom: 2rem;
            border: 1px solid rgba(155, 77, 224, 0.2);
        }

        .shop-header h1 {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .shop-header p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .shop-controls {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: center;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: var(--dark-light);
            border: 2px solid var(--gray);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(155, 77, 224, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.2rem;
        }

        .filter-select, .sort-select {
            padding: 1rem 1.5rem;
            background: var(--dark-light);
            border: 2px solid var(--gray);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            min-width: 200px;
        }

        .filter-select:hover, .sort-select:hover {
            border-color: var(--primary);
        }

        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--dark-light);
            border-radius: var(--radius-md);
            border: 1px solid rgba(155, 77, 224, 0.1);
        }

        .results-count {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .results-count strong {
            color: var(--primary-light);
            font-size: 1.2rem;
        }

        .clear-filters {
            background: var(--error);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .clear-filters:hover {
            background: #d63447;
            transform: translateY(-2px);
        }

        .no-products {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--dark-light);
            border-radius: var(--radius-xl);
            border: 2px dashed var(--gray);
        }

        .no-products-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .no-products h3 {
            font-size: 1.5rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            z-index: 10;
        }

        /* Enhanced product card for this page */
        .grid .product-card {
            position: relative;
        }

        .product-card-content {
            padding: 1.25rem;
        }

        .product-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1.25rem 1.25rem;
            gap: 1rem;
        }

        .quick-view-btn {
            background: var(--primary);
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: var(--transition);
            flex: 1;
            text-align: center;
        }

        .quick-view-btn:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        @media (max-width: 968px) {
            .shop-controls {
                grid-template-columns: 1fr;
            }

            .shop-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>

<?php include '../includes/header.php'; ?>

<div class="main-container">
    <!-- Shop Header -->
    <div class="shop-header">
        <h1>🛍️ Shop Thrift Clothing</h1>
        <p>Discover unique, sustainable fashion pieces at unbeatable prices. Every purchase helps reduce waste and supports circular fashion.</p>
    </div>

    <!-- Search & Filter Controls -->
    <div class="shop-controls">
        <form method="GET" class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" 
                   name="search" 
                   placeholder="Search products, brands, or categories..." 
                   value="<?= htmlspecialchars($search) ?>">
            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
        </form>

        <form method="GET" style="display: contents;">
            <select name="category" class="filter-select" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php while($cat = $categories_query->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($cat['category']) ?>" 
                            <?= $category == $cat['category'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
        </form>

        <form method="GET" style="display: contents;">
            <select name="sort" class="sort-select" onchange="this.form.submit()">
                <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>Name: A to Z</option>
            </select>
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
        </form>
    </div>

    <!-- Results Info -->
    <?php 
    $total_products = $result->num_rows;
    if (!empty($search) || !empty($category)): 
    ?>
    <div class="results-info">
        <div class="results-count">
            Found <strong><?= $total_products ?></strong> product<?= $total_products != 1 ? 's' : '' ?>
            <?php if (!empty($search)): ?>
                matching "<strong><?= htmlspecialchars($search) ?></strong>"
            <?php endif; ?>
            <?php if (!empty($category)): ?>
                in <strong><?= htmlspecialchars($category) ?></strong>
            <?php endif; ?>
        </div>
        <a href="shop.php" class="clear-filters">Clear Filters ✕</a>
    </div>
    <?php else: ?>
    <div class="results-info">
        <div class="results-count">
            Showing <strong><?= $total_products ?></strong> product<?= $total_products != 1 ? 's' : '' ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Products Grid -->
    <?php if ($result->num_rows > 0): ?>
    <div class="grid">
        <?php while($row = $result->fetch_assoc()): ?>
        <div class="product-card" onclick="location.href='product_details.php?id=<?= intval($row['id']); ?>'">
            <?php if ($row['stock'] <= 5): ?>
                <span class="product-badge">Only <?= $row['stock'] ?> Left!</span>
            <?php endif; ?>
            
            <img src="uploads/<?= htmlspecialchars($row['image']); ?>" 
                 alt="<?= htmlspecialchars($row['name']); ?>">
            
            <div class="product-card-content">
                <h3><?= htmlspecialchars($row['name']); ?></h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    <?= htmlspecialchars($row['brand']); ?> • <?= htmlspecialchars($row['size']); ?>
                </p>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.5rem;">
                    <?= htmlspecialchars($row['condition_type']); ?>
                </p>
            </div>
            
            <div class="product-card-footer">
                <strong style="font-size: 1.5rem; color: var(--primary-light);">
                    ₱<?= number_format($row['price'],2); ?>
                </strong>
                <a href="product_details.php?id=<?= intval($row['id']); ?>" 
                   class="quick-view-btn"
                   onclick="event.stopPropagation();">
                    View Details
                </a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="no-products">
        <div class="no-products-icon">😔</div>
        <h3>No products found</h3>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">
            Try adjusting your filters or search terms
        </p>
        <a href="shop.php" style="margin-top: 1rem; display: inline-block;" class="btn-primary">
            Browse All Products
        </a>
    </div>
    <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?>

</body>
</html>
