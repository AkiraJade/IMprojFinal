<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';

// Log session and POST data for debugging
error_log('Session data: ' . print_r($_SESSION, true));
error_log('POST data: ' . print_r($_POST, true));

// Get product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: shop.php");
    exit();
}

// Handle comment submission
$message = "";
$message_type = "";

// Check if product_reviews table exists
$table_check = $conn->query("SHOW TABLES LIKE 'product_reviews'");
if ($table_check->num_rows === 0) {
    error_log("ERROR: product_reviews table does not exist!");
    $message = "❌ Error: Database table for reviews is missing. Please contact support.";
    $message_type = "error";
}

// Debug: Log script start
error_log("\n=== NEW REQUEST ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("GET data: " . print_r($_GET, true));
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION data: " . print_r($_SESSION, true));

// Handle form submission
// Debug: Log form submission
error_log("Form submitted with data: " . print_r($_POST, true));

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['submit_review']) || isset($_POST['rating']))) {
    error_log("\n=== PROCESSING COMMENT SUBMISSION ===");
    
    if (!isset($_SESSION['user_id'])) {
        error_log("ERROR: User not logged in");
        $message = "❌ Please login to leave a comment!";
        $message_type = "error";
    } else {
        $comment = trim($_POST['comment'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        $user_id = $_SESSION['user_id'];
        $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        error_log("Processing comment - User ID: $user_id, Product ID: $product_id, Rating: $rating, Comment: '$comment'");
        
        // Basic validation
        if (empty($comment) || $rating < 1 || $rating > 5 || $product_id < 1) {
            $error_msg = "Validation failed - ";
            $error_msg .= empty($comment) ? 'Comment is empty, ' : '';
            $error_msg .= ($rating < 1 || $rating > 5) ? 'Rating is invalid, ' : '';
            $error_msg .= ($product_id < 1) ? 'Product ID is invalid' : '';
            error_log($error_msg);
            
            $message = "❌ Please provide a valid comment and rating!";
            $message_type = "error";
        } else {
        
            // Check for profanity
            require_once __DIR__ . '/../includes/ProfanityFilter.php';
            
            if (ProfanityFilter::hasProfanity($comment)) {
                $message = "❌ Your comment contains inappropriate language. Please revise your comment.";
                $message_type = "error";
                // Log the attempt
                error_log("Profanity detected in comment by user ID: $user_id");
                // Show the form again with the original comment (not filtered)
                $_POST['comment'] = $comment;
            } else {
                // Only proceed with saving if there's no profanity
                $comment = ProfanityFilter::filter($comment);
                
                error_log("After profanity filter - Comment: '$comment'");
                // Database operations
                $sql = "INSERT INTO product_reviews (product_id, user_id, comment, rating) VALUES (?, ?, ?, ?)";
                error_log("Preparing SQL: $sql");
                
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("iisi", $product_id, $user_id, $comment, $rating);
                    error_log("Bound parameters - Product ID: $product_id, User ID: $user_id, Rating: $rating");
                    
                    if ($stmt->execute()) {
                        $insert_id = $stmt->insert_id;
                        error_log("Comment inserted successfully. Insert ID: $insert_id");
                        
                        // Clear form and redirect to prevent resubmission
                        $_POST = array();
                        $redirect_url = $_SERVER['REQUEST_URI'];
                        error_log("Redirecting to: $redirect_url");
                        header("Location: $redirect_url");
                        exit();
                    } else {
                        $error = $stmt->error;
                        error_log("Database error: " . $error);
                        $message = "❌ Error saving comment. Please try again. (Error: " . $error . ")";
                        $message_type = "error";
                    }
                    $stmt->close();
                } else {
                    $error = $conn->error;
                    error_log("Error preparing statement: " . $error);
                    $message = "❌ Error: " . $error;
                    $message_type = "error";
                }
            } // Close else block for profanity check
        } // Close else block for validation
    } // Close else block for user not logged in
} // Close if isset(submit_review) and POST request

// Handle comment update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_review'])) {
    if (isset($_SESSION['user_id'])) {
        $review_id = intval($_POST['review_id']);
        $comment = trim($_POST['comment']);
        $rating = intval($_POST['rating']);
        $user_id = $_SESSION['user_id'];
        
        // Profanity filter
        $bad_words = ['fuck', 'shit', 'damn', 'bitch', 'ass', 'hell', 'crap', 'bastard', 'puta', 'gago', 'tanga', 'bobo'];
        foreach ($bad_words as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $replacement = str_repeat('*', strlen($word));
            $comment = preg_replace($pattern, $replacement, $comment);
        }
        
        $stmt = $conn->prepare("UPDATE product_reviews SET comment = ?, rating = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param("siii", $comment, $rating, $review_id, $user_id);
        
        if ($stmt->execute()) {
            $message = "✅ Comment updated successfully!";
            $message_type = "success";
        }
    }
}

// Handle comment deletion
if (isset($_GET['delete_review']) && isset($_SESSION['user_id'])) {
    $review_id = intval($_GET['delete_review']);
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("DELETE FROM product_reviews WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $review_id, $user_id);
    $stmt->execute();
    
    header("Location: product_details.php?id=$product_id");
    exit();
}

// Fetch all reviews for this product
$reviews_query = $conn->prepare("
    SELECT pr.*, u.username, u.profile_photo 
    FROM product_reviews pr 
    JOIN users u ON pr.user_id = u.id 
    WHERE pr.product_id = ? 
    ORDER BY pr.created_at DESC
");
$reviews_query->bind_param("i", $product_id);
$reviews_query->execute();
$reviews = $reviews_query->get_result();

// Calculate average rating
$avg_query = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM product_reviews WHERE product_id = ?");
$avg_query->bind_param("i", $product_id);
$avg_query->execute();
$rating_data = $avg_query->get_result()->fetch_assoc();

include '../includes/header.php';
?>

<style>
.product-detail-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 2rem;
}

.product-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    margin-bottom: 3rem;
}

.product-image-section img {
    width: 100%;
    border-radius: var(--radius-lg);
    border: 1px solid rgba(155, 77, 224, 0.2);
}

.product-info-section {
    padding: 2rem;
    background: var(--dark-light);
    border-radius: var(--radius-lg);
    border: 1px solid rgba(155, 77, 224, 0.2);
}

.product-info-section h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.product-price {
    font-size: 2rem;
    color: var(--primary);
    font-weight: 700;
    margin: 1rem 0;
}

.product-meta {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin: 2rem 0;
}

.meta-item {
    padding: 1rem;
    background: var(--dark);
    border-radius: var(--radius-md);
}

.reviews-section {
    background: var(--dark-light);
    padding: 2rem;
    border-radius: var(--radius-lg);
    border: 1px solid rgba(155, 77, 224, 0.2);
}

.review-form {
    background: var(--dark);
    padding: 2rem;
    border-radius: var(--radius-md);
    margin-bottom: 2rem;
}

.review-item {
    background: var(--dark);
    padding: 1.5rem;
    border-radius: var(--radius-md);
    margin-bottom: 1rem;
    border: 1px solid rgba(155, 77, 224, 0.1);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.review-author {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.review-author img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid var(--primary);
}

.rating-stars {
    color: #ffd700;
    font-size: 1.2rem;
}

.edit-form {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--dark-light);
    border-radius: var(--radius-md);
}

@media (max-width: 768px) {
    .product-detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="product-detail-container">
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>" style="padding: 1rem; margin-bottom: 2rem; border-radius: var(--radius-md); background: <?= $message_type == 'success' ? '#10b981' : '#ef4444' ?>;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="product-detail-grid">
        <div class="product-image-section">
            <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        </div>

        <div class="product-info-section">
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            
            <div class="rating-stars">
                <?php 
                $avg_rating = $rating_data['avg_rating'] ?? 0;
                for ($i = 1; $i <= 5; $i++) {
                    echo $i <= round($avg_rating) ? '⭐' : '☆';
                }
                ?>
                <span style="color: var(--text-secondary); font-size: 1rem;">
                    (<?= $rating_data['total_reviews'] ?? 0 ?> reviews)
                </span>
            </div>

            <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>

            <div class="product-meta">
                <div class="meta-item">
                    <strong>Brand:</strong><br>
                    <?= htmlspecialchars($product['brand']) ?>
                </div>
                <div class="meta-item">
                    <strong>Category:</strong><br>
                    <?= htmlspecialchars($product['category']) ?>
                </div>
                <div class="meta-item">
                    <strong>Size:</strong><br>
                    <?= htmlspecialchars($product['size']) ?>
                </div>
                <div class="meta-item">
                    <strong>Condition:</strong><br>
                    <?= htmlspecialchars($product['condition_type']) ?>
                </div>
                <div class="meta-item">
                    <strong>Stock:</strong><br>
                    <?= $product['stock'] > 0 ? $product['stock'] . ' available' : 'Out of stock' ?>
                </div>
            </div>

            <?php if ($product['stock'] > 0): ?>
                <form method="GET" action="<?= BASE_URL ?>/cart/add.php" class="add-to-cart-form">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="return_url" value="<?= urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="quantity" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">Quantity:</label>
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               value="1" 
                               min="1" 
                               max="<?= $product['stock'] ?>" 
                               class="form-input quantity-input" 
                               style="width: 100px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: var(--dark-light); color: var(--text-primary);"
                               data-required="true"
                               data-min="1"
                               data-max="<?= $product['stock'] ?>"
                               data-pattern-message="Please enter a valid quantity between 1 and <?= $product['stock'] ?>">
                        <div id="quantity-error" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: none;">Please enter a valid quantity (1-<?= $product['stock'] ?>)</div>
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <span>🛒</span> Add to Cart
                    </button>
                </form>
                <script>
                // Add form submission handler
                document.querySelector('.add-to-cart-form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const form = this;
                    const formData = new FormData(form);
                    
                    fetch(form.action + '?' + new URLSearchParams(formData), {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            const message = document.createElement('div');
                            message.className = 'alert alert-success';
                            message.textContent = data.message || 'Product added to cart!';
                            form.parentNode.insertBefore(message, form.nextSibling);
                            setTimeout(() => message.remove(), 3000);
                            
                            // Update cart count if needed
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = parseInt(cartCount.textContent || '0') + 1;
                            }
                            
                            // Redirect if needed
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            }
                        } else {
                            throw new Error(data.message || 'Failed to add to cart');
                        }
                    })
                    .catch(error => {
                        alert(error.message || 'An error occurred. Please try again.');
                        console.error('Error:', error);
                    });
                });
                </script>
                <style>
                    .add-to-cart-form button:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    }
                    .add-to-cart-form button:active {
                        transform: translateY(1px);
                    }
                    .quantity-input::-webkit-inner-spin-button,
                    .quantity-input::-webkit-outer-spin-button {
                        opacity: 1;
                    }
                </style>
            <?php else: ?>
                <button class="btn-secondary" disabled style="width: 100%; padding: 1rem; cursor: not-allowed; opacity: 0.7;">Out of Stock</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="reviews-section">
        <h2>Customer Reviews & Comments</h2>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="review-form">
                <h3>Leave a Comment</h3>
                <style>
                .form-input.error {
                    border-color: #ef4444 !important;
                }
                .error-message {
                    color: #ef4444;
                    font-size: 0.875rem;
                    margin-top: 0.25rem;
                    margin-bottom: 0.5rem;
                }
                </style>
                <form method="POST" action="" id="reviewForm" class="needs-validation" novalidate onsubmit="return validateReviewForm(this);">
                    <div class="form-group">
                        <label for="rating">Rating:</label>
                        <select name="rating" 
                                id="rating" 
                                class="form-input" 
                                style="width: 100%; padding: 0.5rem; margin-bottom: 1rem;"
                                required
                                data-pattern-message="Please select a rating">
                            <option value="">Select a rating</option>
                            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                            <option value="4">⭐⭐⭐⭐ Good</option>
                            <option value="3">⭐⭐⭐ Average</option>
                            <option value="2">⭐⭐ Poor</option>
                            <option value="1">⭐ Terrible</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="comment">Your Comment:</label>
                        <textarea name="comment" 
                                id="comment" 
                                rows="4" 
                                class="form-input" 
                                placeholder="Share your thoughts about this product..." 
                                style="width: 100%; padding: 1rem; margin-bottom: 1rem; border-radius: var(--radius-md);"
                                required
                                data-pattern-message="Please enter a comment">
                        </textarea>
                    </div>

                    <button type="submit" name="submit_review" value="1" class="btn-primary">Post Comment</button>
                </form>
            </div>
        <?php else: ?>
            <div class="review-form">
                <p>Please <a href="<?= BASE_URL ?>/login.php" style="color: var(--primary);">login</a> to leave a comment.</p>
            </div>
        <?php endif; ?>

        <h3>All Comments (<?= $rating_data['total_reviews'] ?? 0 ?>)</h3>

        <?php if ($reviews->num_rows > 0): ?>
            <?php while ($review = $reviews->fetch_assoc()): ?>
                <div class="review-item">
                    <div class="review-header">
                        <div class="review-author">
                            <?php if ($review['profile_photo']): ?>
                                <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($review['profile_photo']) ?>" alt="Profile">
                            <?php else: ?>
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                    <?= strtoupper(substr($review['username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <strong><?= htmlspecialchars($review['username']) ?></strong>
                                <div class="rating-stars" style="font-size: 0.9rem;">
                                    <?php for ($i = 1; $i <= 5; $i++) echo $i <= $review['rating'] ? '⭐' : '☆'; ?>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right; color: var(--text-secondary); font-size: 0.9rem;">
                            <?= date('M d, Y', strtotime($review['created_at'])) ?>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id']): ?>
                                <br>
                                <a href="#" onclick="toggleEdit(<?= $review['id'] ?>); return false;" style="color: var(--primary); font-size: 0.85rem;">Edit</a>
                                <a href="?id=<?= $product_id ?>&delete_review=<?= $review['id'] ?>" onclick="return confirm('Delete this comment?');" style="color: #ef4444; font-size: 0.85rem; margin-left: 10px;">Delete</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <p id="comment-<?= $review['id'] ?>"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id']): ?>
                        <div id="edit-form-<?= $review['id'] ?>" class="edit-form" style="display: none;">
                            <form method="POST">
                                <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                <label>Rating:</label>
                                <select name="rating" style="width: 100%; padding: 0.5rem; margin-bottom: 1rem;">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <option value="<?= $i ?>" <?= $review['rating'] == $i ? 'selected' : '' ?>>
                                            <?= str_repeat('⭐', $i) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <textarea name="comment" rows="3" style="width: 100%; padding: 0.5rem; margin-bottom: 1rem;"><?= htmlspecialchars($review['comment']) ?></textarea>
                                <button type="submit" name="update_review" class="btn-primary">Update</button>
                                <button type="button" onclick="toggleEdit(<?= $review['id'] ?>)" class="btn-secondary">Cancel</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No comments yet. Be the first to comment!</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Prevent form submission if quantity is invalid
function validateQuantity() {
    const quantityInput = document.getElementById('quantity');
    const quantity = parseInt(quantityInput.value);
    const maxStock = parseInt(quantityInput.max);
    const errorElement = document.getElementById('quantity-error');
    
    if (isNaN(quantity) || quantity < 1 || quantity > maxStock) {
        errorElement.style.display = 'block';
        quantityInput.style.borderColor = '#ef4444';
        return false;
    }
    
    errorElement.style.display = 'none';
    quantityInput.style.borderColor = 'var(--border-color)';
    return true;
}

// Add event listeners for quantity validation
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
        quantityInput.addEventListener('change', validateQuantity);
        quantityInput.addEventListener('input', validateQuantity);
        
        // Also validate on form submission
        const form = quantityInput.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!validateQuantity()) {
                    e.preventDefault();
                    return false;
                }
                return true;
            });
        }
    }
});

// List of bad words for client-side validation (keep in sync with server-side)
const profanityWords = [
    // English profanity
    'fuck', 'shit', 'asshole', 'bitch', 'cunt', 'dick', 'pussy', 'cock', 'whore', 'slut',
    'bastard', 'dickhead', 'piss', 'crap', 'damn', 'fag', 'faggot', 'retard', 'nigger',
    'nigga', 'chink', 'spic', 'kike', 'coon', 'twat', 'wanker', 'bollocks', 'arsehole',
    'bloody', 'bugger', 'cow', 'crikey', 'cunt', 'minge', 'prick', 'pissed', 'pissed off',
    'piss off', 'shitty', 'shite', 'twat', 'wank', 'wanker', 'whore',
    
    // Filipino/Tagalog profanity
    'puta', 'putang ina', 'putangina', 'puta ina', 'gago', 'gaga', 'bobo', 'tanga', 'ulol',
    'bobo', 'bubu', 'bubuwit', 'burat', 'puking ina', 'pukina', 'pota', 'potang ina',
    'potangina', 'tanga', 'tarantado', 'ulol', 'unggoy', 'yawa', 'yawwa', 'leche', 'lintik',
    'pakshet', 'pakyu', 'peste', 'pukinang ina', 'puta ka', 'putang ina mo', 'shet', 'sira ulo',
    'suso mo', 'tamod', 'tanga', 'tanga ka', 'tarantado', 'timang', 'tite', 'tungaw', 'ugok',
    'ulol', 'ungas', 'ungas', 'ungas ka', 'yawa', 'yawa ka'
];

/**
 * Check if text contains profanity
 */
function hasProfanity(text) {
    if (!text) return false;
    
    const lowerText = text.toLowerCase();
    return profanityWords.some(word => {
        const regex = new RegExp('\\b' + word + '\\b', 'i');
        return regex.test(lowerText);
    });
}

// Debug form submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reviewForm');
    if (form) {
        console.log('Review form found');
        
        // Debug form submission
        form.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            console.log('Form data:', {
                rating: document.getElementById('rating')?.value,
                comment: document.getElementById('comment')?.value
            });
            
            // Let the form submit normally
            return true;
        });
    } else {
        console.log('Review form not found!');
    }
});
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reviewForm');
    if (form) {
        console.log('Review form found');
        
        // Debug form submission
        form.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            console.log('Form data:', {
                rating: document.getElementById('rating')?.value,
                comment: document.getElementById('comment')?.value
            });
            
            // Let the form submit normally
            return true;
        });
    } else {
        console.log('Review form not found!');
    }
});

// Initialize form validation with custom submit handler
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reviewForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Manually validate required fields
            const rating = document.getElementById('rating');
            const comment = document.getElementById('comment');
            let isValid = true;

            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(el => el.remove());
            document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

            // Validate rating
            if (!rating.value) {
                showError(rating, 'Please select a rating');
                isValid = false;
            }

            // Validate comment
            if (!comment.value.trim()) {
                showError(comment, 'Please enter a comment');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // If all valid, the form will submit normally
            return true;
        });
        
        // Add input event listeners to clear error when user starts typing/selecting
        const rating = document.getElementById('rating');
        const comment = document.getElementById('comment');
        
        if (rating) {
            rating.addEventListener('change', function() {
                if (this.value) {
                    this.classList.remove('error');
                    const errorMsg = this.parentNode.querySelector('.error-message');
                    if (errorMsg) errorMsg.remove();
                }
            });
        }
        
        if (comment) {
            comment.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('error');
                    const errorMsg = this.parentNode.querySelector('.error-message');
                    if (errorMsg) errorMsg.remove();
                }
            });
        }
    }
});

function showError(field, message) {
    // Remove any existing error message
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // Add error class to field
    field.classList.add('error');
    
    // Create and append error message
    const errorElement = document.createElement('div');
    errorElement.className = 'error-message';
    errorElement.style.color = '#ef4444';
    errorElement.style.fontSize = '0.875rem';
    errorElement.style.marginTop = '0.25rem';
    errorElement.style.marginBottom = '0.5rem';
    errorElement.textContent = message;
    
    field.parentNode.insertBefore(errorElement, field.nextSibling);
}

function toggleEdit(reviewId) {
    const commentDiv = document.getElementById('comment-' + reviewId);
    const editForm = document.getElementById('edit-form-' + reviewId);
    
    if (editForm.style.display === 'none') {
        editForm.style.display = 'block';
        commentDiv.style.display = 'none';
    } else {
        editForm.style.display = 'none';
        commentDiv.style.display = 'block';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
