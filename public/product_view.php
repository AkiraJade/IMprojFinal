<?php
require_once __DIR__ . '/../includes/config.php';


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

// Get product details
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

// Debug: Log the product ID
$debug_product_id = $id;

// Get all images for this product
$query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC";
$imageStmt = $conn->prepare($query);
$imageStmt->bind_param("i", $id);
$imageStmt->execute();
$images = $imageStmt->get_result();

// Debug: Check the query and results
error_log("Product ID: " . $debug_product_id);
error_log("SQL Query: " . $query);

if (!$images) {
    error_log("Query failed: " . $conn->error);
} else {
    $image_count = $images->num_rows;
    error_log("Found $image_count images for product ID: $debug_product_id");
    
    // Log all image paths
    $images->data_seek(0);
    $image_paths = [];
    while ($img = $images->fetch_assoc()) {
        $image_paths[] = $img['image_path'];
        error_log("Image found - ID: " . $img['id'] . ", Path: " . $img['image_path']);
    }
    $images->data_seek(0); // Reset pointer
    
    // Also check the files in the uploads directory
    $upload_dir = __DIR__ . '/../uploads/';
    error_log("Checking files in directory: $upload_dir");
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        $image_files = array_filter($files, function($file) {
            return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
        });
        error_log("Found " . count($image_files) . " image files in uploads directory");
        error_log("Files: " . implode(", ", $image_files));
    } else {
        error_log("Upload directory not found: $upload_dir");
    }
}

// Debug: Check if we got any results
if (!$images) {
    error_log("No images result set for product ID: " . $id);
} else {
    $imageCount = $images->num_rows;
    error_log("Found $imageCount images for product ID: " . $id);
    
    // Debug: Log all image paths
    $images->data_seek(0);
    while ($img = $images->fetch_assoc()) {
        error_log("Image found - ID: " . $img['id'] . ", Path: " . $img['image_path']);
    }
    $images->data_seek(0); // Reset pointer after debugging
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
    <link rel="stylesheet" href="/projectIManagement/public/css/style.css">
    <style>
        .product-gallery {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }
        
        .product-slider {
            position: relative;
            width: 100%;
        }
        
        .slider-container {
            position: relative;
            width: 100%;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .slider-track {
            display: flex;
            transition: transform 0.5s ease;
            height: 500px;
        }
        
        .slide {
            min-width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }
        
        .slide img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            padding: 20px;
        }
        
        .slider-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.8);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .slider-arrow:hover {
            background: white;
            transform: translateY(-50%) scale(1.1);
        }
        
        .prev {
            left: 10px;
        }
        
        .next {
            right: 10px;
        }
        
        .slider-dots {
            position: absolute;
            bottom: 15px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            z-index: 5;
        }
        
        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            border: none;
            cursor: pointer;
            padding: 0;
            transition: all 0.3s ease;
        }
        
        .dot.active {
            background: white;
            transform: scale(1.2);
        }
        
        .slider-thumbnails {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding: 5px;
            overflow-x: auto;
            scrollbar-width: thin;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            border: 2px solid transparent;
            border-radius: 4px;
            overflow: hidden;
            cursor: pointer;
            opacity: 0.7;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        .thumbnail:hover {
            opacity: 1;
            border-color: #007bff;
        }
        
        .thumbnail.active {
            opacity: 1;
            border-color: #007bff;
        }
        
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .primary-image-container {
            width: 100%;
            overflow: hidden;
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .slider-track {
            display: flex;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            height: 500px;
            will-change: transform;
        }
        
        .slide {
            min-width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--dark-light);
        }
        
        .product-main-image {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            padding: 1rem;
        }
        
        .slider-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 10;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            color: #333;
        }
        
        .slider-arrow:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }
        
        .slider-arrow:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-50%) scale(1.1);
        }
        
        .prev {
            left: 10px;
        }
        
        .next {
            right: 10px;
        }
        
        .slider-dots {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            z-index: 5;
        }
        
        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }
        
        .dot.active {
            background-color: white;
            transform: scale(1.2);
            border-color: var(--primary);
        }
        
        .single-image-container {
            width: 100%;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--dark-light);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .product-main-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: var(--radius-lg);
        }
        
        .thumbnail-container {
            display: flex;
            gap: 0.75rem;
            overflow-x: auto;
            padding: 1rem 0.5rem;
            scrollbar-width: thin;
            scrollbar-color: #ccc transparent;
            margin-top: 0.5rem;
        }
        
        .thumbnail-container::-webkit-scrollbar {
            height: 6px;
        }
        
        .thumbnail-container::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 3px;
        }
        
        .thumbnail-container::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .thumbnail {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            opacity: 0.7;
            transition: all 0.2s ease;
            border: 2px solid transparent;
            flex-shrink: 0;
            background: #f5f5f5;
        }
        
        .thumbnail:hover, .thumbnail.active {
            opacity: 1;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .thumbnail:hover img {
            transform: scale(1.05);
        }
        
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
    <div class="product-gallery">
        <?php 
        // Initialize variables
        $all_images = [];
        $primary_image = null;
        
        if ($images && $images->num_rows > 0) {
            // Get all images
            $images->data_seek(0);
            while($img = $images->fetch_assoc()) {
                $all_images[] = $img;
            }
            
            // Debug output in HTML comments
            echo '<!-- Found ' . count($all_images) . ' images for this product -->';
            
            // Get primary image (first one)
            $primary_image = $all_images[0];
            
            // Display the primary image - ensure we use the correct uploads directory
            $primary_image_path = '/IMprojFinal/public/uploads/' . basename($primary_image['image_path']);
            // Add debug output
            echo '<!-- Original image path: ' . htmlspecialchars($primary_image['image_path']) . ' -->';
            echo '<!-- Constructed image path: ' . htmlspecialchars($primary_image_path) . ' -->';
            echo '<!-- Primary image path: ' . htmlspecialchars($primary_image_path) . ' -->';
            ?>
            
            <!-- Image Slider -->
            <div class="product-slider">
                <div class="slider-container">
                    <button class="slider-arrow prev">❮</button>
                    <div class="slider-track">
                        <?php foreach ($all_images as $index => $image): 
                            $image_path = '/IMprojFinal/public/uploads/' . basename($image['image_path']);
                            $is_active = $index === 0 ? 'active' : '';
                        ?>
                            <div class="slide <?= $is_active ?>" data-index="<?= $index ?>">
                                <img src="<?= $image_path ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?> - Image <?= $index + 1 ?>" 
                                     class="product-slide-image"
                                     onerror="console.error('Failed to load image:', this.src)">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="slider-arrow next">❯</button>
                    
                    <!-- Slider Dots -->
                    <div class="slider-dots">
                        <?php foreach ($all_images as $index => $image): ?>
                            <button class="dot <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Thumbnails -->
                <?php if (count($all_images) > 1): ?>
                <div class="slider-thumbnails">
                    <?php foreach ($all_images as $index => $image): 
                        $image_path = '/IMprojFinal/public/uploads/' . basename($image['image_path']);
                    ?>
                        <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                            <img src="<?= $image_path ?>" 
                                 alt="Thumbnail <?= $index + 1 ?>"
                                 onerror="console.error('Failed to load thumbnail:', this.src)">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php 
        } else { 
            // No images found
            ?>
            <div class="no-images">
                <p>No images available for this product</p>
            </div>
            <?php 
        } 
        ?>
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
                <?php 
                // Check if user is logged in and has customer role
                $is_customer = false;
                if (isset($_SESSION['user_id'])) {
                    $user_id = $_SESSION['user_id'];
                    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        $is_customer = ($user['role'] === 'customer');
                    }
                }
                
                if ($is_customer): ?>
                    <a href="cart/add.php?id=<?= intval($product['id']) ?>" class="btn-add-cart">
                        🛒 Add to Cart
                    </a>
                <?php else: ?>
                    <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn-add-cart">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const track = document.querySelector('.slider-track');
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    const thumbnails = document.querySelectorAll('.thumbnail');
    const prevBtn = document.querySelector('.prev');
    const nextBtn = document.querySelector('.next');
    
    let currentIndex = 0;
    const totalSlides = slides.length;
    
    // Set initial position
    function updateSlider() {
        track.style.transform = `translateX(-${currentIndex * 100}%)`;
        
        // Update dots
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
        
        // Update thumbnails
        thumbnails.forEach((thumb, index) => {
            thumb.classList.toggle('active', index === currentIndex);
        });
    }
    
    // Next slide
    function nextSlide() {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateSlider();
    }
    
    // Previous slide
    function prevSlide() {
        currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
        updateSlider();
    }
    
    // Go to specific slide
    function goToSlide(index) {
        currentIndex = index;
        updateSlider();
    }
    
    // Event listeners
    if (prevBtn) prevBtn.addEventListener('click', prevSlide);
    if (nextBtn) nextBtn.addEventListener('click', nextSlide);
    
    // Dot navigation
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            const index = parseInt(dot.getAttribute('data-index'));
            goToSlide(index);
        });
    });
    
    // Thumbnail navigation
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', () => {
            const index = parseInt(thumb.getAttribute('data-index'));
            goToSlide(index);
        });
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            prevSlide();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
        }
    });
    
    // Auto-advance slides (optional)
    // let slideInterval = setInterval(nextSlide, 5000);
    
    // Pause on hover
    // track.addEventListener('mouseenter', () => clearInterval(slideInterval));
    // track.addEventListener('mouseleave', () => {
    //     clearInterval(slideInterval);
    //     slideInterval = setInterval(nextSlide, 5000);
    // });
});
document.addEventListener('DOMContentLoaded', function() {
    // Make secondary images clickable to switch with the main image
    const mainImage = document.querySelector('.primary-image-container img');
    const secondaryImages = document.querySelectorAll('.secondary-image img');
    
    secondaryImages.forEach(img => {
        img.addEventListener('click', function() {
            // Swap the src of the main image with the clicked secondary image
            const tempSrc = mainImage.src;
            mainImage.src = this.src;
            this.src = tempSrc;
            
            // Add a visual feedback for the active secondary image
            document.querySelectorAll('.secondary-image').forEach(el => {
                el.style.borderColor = '#ddd';
            });
            this.parentElement.style.borderColor = '#007bff';
        });
    });
    
    // Set the first secondary image as active by default if it exists
    if (secondaryImages.length > 0) {
        secondaryImages[0].parentElement.style.borderColor = '#007bff';
    }
});
</script>

</body>
</html>