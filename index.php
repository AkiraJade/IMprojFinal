<?php 
require_once __DIR__ . '/includes/config.php';

// Get featured products (3 random or newest products)
$featured_query = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UrbanThrift - Sustainable Fashion Marketplace</title>
    <link rel="stylesheet" href="/IMprojFinal/public/css/style.css">
    <style>
        /* Hero Section */
        .hero-section {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #0A0A0F 0%, #1a0f2e 50%, #0A0A0F 100%);
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .hero-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.4;
            animation: heroFloat 25s infinite ease-in-out;
        }

        .hero-orb-1 {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, #9b4de0 0%, transparent 70%);
            top: -20%;
            left: -10%;
            animation-delay: 0s;
        }

        .hero-orb-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #C77DFF 0%, transparent 70%);
            bottom: -15%;
            right: -5%;
            animation-delay: 8s;
        }

        .hero-orb-3 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, #E0AAFF 0%, transparent 70%);
            top: 50%;
            left: 50%;
            animation-delay: 16s;
        }

        @keyframes heroFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(80px, -80px) scale(1.15); }
            66% { transform: translate(-60px, 60px) scale(0.85); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 900px;
            padding: 0 2rem;
        }

        .hero-logo {
            font-size: clamp(4rem, 10vw, 8rem);
            font-weight: 900;
            background: linear-gradient(135deg, #FFFFFF 0%, #C77DFF 50%, #9b4de0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            letter-spacing: -3px;
            animation: glow 4s ease-in-out infinite;
            line-height: 1;
        }

        @keyframes glow {
            0%, 100% { filter: drop-shadow(0 0 30px rgba(155, 77, 224, 0.4)); }
            50% { filter: drop-shadow(0 0 60px rgba(199, 125, 255, 0.7)); }
        }

        .hero-tagline {
            font-size: clamp(1.5rem, 3vw, 2.5rem);
            color: #B8B8C8;
            font-weight: 300;
            margin-bottom: 2rem;
            line-height: 1.4;
        }

        .hero-description {
            font-size: clamp(1rem, 2vw, 1.3rem);
            color: #858596;
            margin-bottom: 3rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-cta {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-btn {
            padding: 1.5rem 3rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: var(--radius-lg);
            text-decoration: none;
            transition: var(--transition);
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .hero-btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 10px 40px rgba(155, 77, 224, 0.4);
        }

        .hero-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 50px rgba(155, 77, 224, 0.6);
        }

        .hero-btn-secondary {
            background: rgba(155, 77, 224, 0.1);
            color: var(--primary-light);
            border: 2px solid var(--primary);
            backdrop-filter: blur(10px);
        }

        .hero-btn-secondary:hover {
            background: rgba(155, 77, 224, 0.2);
            transform: translateY(-3px);
        }

        .scroll-indicator {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s infinite;
            z-index: 2;
            cursor: pointer;
        }

        .scroll-indicator span {
            display: block;
            width: 30px;
            height: 50px;
            border: 2px solid var(--primary);
            border-radius: 25px;
            position: relative;
        }

        .scroll-indicator span::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 50%;
            width: 6px;
            height: 6px;
            background: var(--primary-light);
            border-radius: 50%;
            transform: translateX(-50%);
            animation: scroll 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-10px); }
        }

        @keyframes scroll {
            0% { top: 10px; opacity: 1; }
            100% { top: 30px; opacity: 0; }
        }

        /* Info Section */
        .info-section {
            padding: 8rem 2rem;
            background: var(--dark);
            position: relative;
        }

        .info-container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .info-header {
            margin-bottom: 5rem;
        }

        .info-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        .info-subtitle {
            font-size: clamp(1.1rem, 2vw, 1.5rem);
            color: var(--text-secondary);
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
            margin-top: 4rem;
        }

        .info-card {
            background: var(--dark-light);
            padding: 3rem;
            border-radius: var(--radius-xl);
            border: 1px solid rgba(155, 77, 224, 0.2);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(155, 77, 224, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: var(--transition);
        }

        .info-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-glow);
            border-color: var(--primary);
        }

        .info-card:hover::before {
            opacity: 1;
        }

        .info-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            display: block;
        }

        .info-card h3 {
            font-size: 1.8rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .info-card p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            line-height: 1.7;
        }

        /* Stats Section */
        .stats-section {
            padding: 5rem 2rem;
            background: linear-gradient(135deg, rgba(155, 77, 224, 0.05) 0%, transparent 100%);
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            text-align: center;
        }

        .stat-item {
            position: relative;
        }

        .stat-number {
            font-size: clamp(3rem, 5vw, 4.5rem);
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* Featured Products Section */
        .featured-section {
            padding: 8rem 2rem;
            background: var(--black);
        }

        .featured-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .featured-header {
            text-align: center;
            margin-bottom: 5rem;
        }

        .featured-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .featured-subtitle {
            font-size: clamp(1.1rem, 2vw, 1.4rem);
            color: var(--text-secondary);
        }

        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 3rem;
            margin-bottom: 4rem;
        }

        .featured-card {
            background: var(--dark);
            border-radius: var(--radius-xl);
            overflow: hidden;
            border: 1px solid rgba(155, 77, 224, 0.2);
            transition: var(--transition);
            position: relative;
            cursor: pointer;
        }

        .featured-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(155, 77, 224, 0.15) 0%, transparent 100%);
            opacity: 0;
            transition: var(--transition);
            z-index: 1;
        }

        .featured-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-glow);
            border-color: var(--primary);
        }

        .featured-card:hover::before {
            opacity: 1;
        }

        .featured-image {
            position: relative;
            height: 400px;
            overflow: hidden;
        }

        .featured-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .featured-card:hover .featured-image img {
            transform: scale(1.1);
        }

        .featured-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 0.9rem;
            z-index: 2;
            box-shadow: var(--shadow-md);
        }

        .featured-content {
            padding: 2rem;
            position: relative;
            z-index: 2;
        }

        .featured-content h3 {
            font-size: 1.8rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .featured-meta {
            display: flex;
            gap: 1rem;
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .featured-price {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-light);
            margin-bottom: 1rem;
        }

        .featured-btn {
            width: 100%;
            padding: 1rem;
            background: rgba(155, 77, 224, 0.1);
            border: 2px solid var(--primary);
            color: var(--primary-light);
            border-radius: var(--radius-md);
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
        }

        .featured-btn:hover {
            background: var(--primary);
            color: white;
        }

        .view-all-container {
            text-align: center;
            margin-top: 4rem;
        }

        .view-all-btn {
            display: inline-block;
            padding: 1.5rem 4rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-lg);
            font-weight: 700;
            font-size: 1.2rem;
            transition: var(--transition);
            box-shadow: 0 10px 40px rgba(155, 77, 224, 0.4);
            position: relative;
            overflow: hidden;
        }

        .view-all-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .view-all-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(155, 77, 224, 0.6);
        }

        .view-all-btn:hover::before {
            left: 100%;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-section {
                height: auto;
                min-height: 100vh;
                padding: 4rem 1rem;
            }

            .featured-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .hero-cta {
                flex-direction: column;
                align-items: stretch;
            }

            .hero-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include './includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-background">
        <div class="hero-orb hero-orb-1"></div>
        <div class="hero-orb hero-orb-2"></div>
        <div class="hero-orb hero-orb-3"></div>
    </div>
    
    <div class="hero-content">
        <h1 class="hero-logo">UrbanThrift</h1>
        <p class="hero-tagline">Sustainable Fashion Meets Smart Shopping</p>
        <p class="hero-description">
            Discover unique, pre-loved fashion pieces that tell a story. 
            Every purchase helps reduce waste and supports a circular economy.
        </p>
        <div class="hero-cta">
            <a href="#featured" class="hero-btn hero-btn-primary">Explore Collection</a>
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="hero-btn hero-btn-secondary">Join Now</a>
            <?php else: ?>
                <a href="cart/cart.php" class="hero-btn hero-btn-secondary">View Cart</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="scroll-indicator" onclick="document.getElementById('info').scrollIntoView({behavior: 'smooth'})">
        <span></span>
    </div>
</section>

<!-- Info Section -->
<section class="info-section" id="info">
    <div class="info-container">
        <div class="info-header">
            <h2 class="info-title">Why Choose UrbanThrift?</h2>
            <p class="info-subtitle">
                Join thousands of conscious shoppers making a difference through sustainable fashion choices
            </p>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <span class="info-icon">♻️</span>
                <h3>Eco-Friendly</h3>
                <p>Reduce fashion waste by giving clothes a second life. Each purchase saves resources and protects our planet.</p>
            </div>

            <div class="info-card">
                <span class="info-icon">💰</span>
                <h3>Affordable Prices</h3>
                <p>Premium brands at fraction of the cost. Quality fashion doesn't have to break the bank.</p>
            </div>

            <div class="info-card">
                <span class="info-icon">✨</span>
                <h3>Unique Finds</h3>
                <p>Discover one-of-a-kind pieces you won't find anywhere else. Stand out with authentic style.</p>
            </div>

            <div class="info-card">
                <span class="info-icon">🚀</span>
                <h3>Fast Delivery</h3>
                <p>Quick and reliable shipping. Your thrifted treasures delivered right to your doorstep.</p>
            </div>

            <div class="info-card">
                <span class="info-icon">🔒</span>
                <h3>Secure Shopping</h3>
                <p>Shop with confidence. Safe transactions and buyer protection on every purchase.</p>
            </div>

            <div class="info-card">
                <span class="info-icon">💚</span>
                <h3>Quality Assured</h3>
                <p>Every item is carefully inspected and authenticated. Only the best makes it to our collection.</p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="stats-container">
        <div class="stat-item">
            <div class="stat-number">10K+</div>
            <div class="stat-label">Happy Customers</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">5K+</div>
            <div class="stat-label">Products Sold</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">98%</div>
            <div class="stat-label">Satisfaction Rate</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">50T</div>
            <div class="stat-label">Waste Reduced</div>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="featured-section" id="featured">
    <div class="featured-container">
        <div class="featured-header">
            <h2 class="featured-title">Featured Products</h2>
            <p class="featured-subtitle">Hand-picked treasures just for you</p>
        </div>

        <div class="featured-grid">
            <?php while($product = $featured_query->fetch_assoc()): ?>
            <div class="featured-card" onclick="window.location.href='/IMprojFinal/public/product_view.php?id=<?= $product['id'] ?>'">
                <div class="featured-image">
                    <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($product['image']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                    <span class="featured-badge"><?= htmlspecialchars($product['condition_type']) ?></span>
                </div>
                <div class="featured-content">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <div class="featured-meta">
                        <span><?= htmlspecialchars($product['brand']) ?></span>
                        <span>•</span>
                        <span><?= htmlspecialchars($product['size']) ?></span>
                    </div>
                    <div class="featured-price">₱<?= number_format($product['price'], 2) ?></div>
                    <button class="featured-btn">View Details</button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <div class="view-all-container">
            <a href="public/shop.php" class="view-all-btn">View All Products →</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>