<?php
require_once __DIR__ . '/../includes/config.php';

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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $message = "❌ Please login to leave a comment!";
        $message_type = "error";
    } else {
        $comment = trim($_POST['comment']);
        $rating = intval($_POST['rating']);
        $user_id = $_SESSION['user_id'];
        
        // Profanity filter - mask bad words
        $bad_words = ['fuck', 'shit', 'damn', 'bitch', 'ass', 'hell', 'crap', 'bastard', 'puta', 'gago', 'tanga', 'bobo'];
        foreach ($bad_words as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $replacement = str_repeat('*', strlen($word));
            $comment = preg_replace($pattern, $replacement, $comment);
        }
        
        if (empty($comment)) {
            $message = "❌ Comment cannot be empty!";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, comment, rating) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $product_id, $user_id, $comment, $rating);
            
            if ($stmt->execute()) {
                $message = "✅ Comment posted successfully!";
                $message_type = "success";
            } else {
                $message = "❌ Failed to post comment!";
                $message_type = "error";
            }
        }
    }
}

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
                <form method="POST" action="<?= BASE_URL ?>/cart/add.php">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <label>Quantity:</label>
                    <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" style="width: 100px; padding: 0.5rem; margin: 1rem 0;">
                    <br>
                    <button type="submit" class="btn-primary" style="width: 100%; padding: 1rem; font-size: 1.2rem;">🛒 Add to Cart</button>
                </form>
            <?php else: ?>
                <button class="btn-secondary" disabled style="width: 100%; padding: 1rem;">Out of Stock</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="reviews-section">
        <h2>Customer Reviews & Comments</h2>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="review-form">
                <h3>Leave a Comment</h3>
                <form method="POST">
                    <label>Rating:</label>
                    <select name="rating" required style="width: 100%; padding: 0.5rem; margin-bottom: 1rem;">
                        <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                        <option value="4">⭐⭐⭐⭐ Good</option>
                        <option value="3">⭐⭐⭐ Average</option>
                        <option value="2">⭐⭐ Poor</option>
                        <option value="1">⭐ Terrible</option>
                    </select>

                    <label>Your Comment:</label>
                    <textarea name="comment" rows="4" required placeholder="Share your thoughts about this product..." style="width: 100%; padding: 1rem; margin-bottom: 1rem; border-radius: var(--radius-md);"></textarea>

                    <button type="submit" name="submit_review" class="btn-primary">Post Comment</button>
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
