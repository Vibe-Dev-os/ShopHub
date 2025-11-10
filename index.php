<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Home - ' . SITE_NAME;

// Get featured products (latest 8 products)
$featured_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE p.stock > 0 
                   ORDER BY p.created_at DESC 
                   LIMIT 8";
$featured_result = $conn->query($featured_query);

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);

include 'includes/header.php';
?>

<!-- Modern Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-60">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="hero-content">
                    <span class="badge bg-white text-primary mb-3 px-3 py-2">
                        <i class="bi bi-star-fill me-1"></i> Welcome to Our Store
                    </span>
                    <h1 class="display-3 fw-bold mb-4 hero-title">
                        Discover Amazing Products at
                        <span class="text-gradient">Unbeatable Prices</span>
                    </h1>
                    <p class="lead mb-4 text-white-75">
                        Shop the latest trends and enjoy fast, secure delivery. 
                        Quality products, great prices, exceptional service.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="products.php" class="btn btn-light px-4 py-2 hero-btn">
                            <i class="bi bi-shop me-2"></i> Shop Now
                        </a>
                        <a href="products.php" class="btn btn-outline-light px-4 py-2 hero-btn-outline">
                            <i class="bi bi-grid me-2"></i> Browse Categories
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image-wrapper">
                    <img src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=600&h=600&fit=crop" 
                         alt="Shopping" 
                         class="hero-main-image">
                    <div class="floating-card card-1">
                        <i class="bi bi-truck text-primary"></i>
                        <span>Fast Delivery</span>
                    </div>
                    <div class="floating-card card-2">
                        <i class="bi bi-shield-check text-success"></i>
                        <span>Secure Payment</span>
                    </div>
                    <div class="floating-card card-3">
                        <i class="bi bi-star-fill text-warning"></i>
                        <span>Top Quality</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <?php display_message(); ?>
    
    <!-- Categories Section -->
    <section class="mb-5 py-5">
        <div class="text-center mb-5">
            <span class="badge bg-primary text-white px-3 py-2 mb-3">
                <i class="bi bi-grid-3x3-gap me-1"></i> Categories
            </span>
            <h2 class="display-5 fw-bold mb-3">Shop by Category</h2>
            <p class="text-muted">Browse our wide range of product categories</p>
        </div>
        <div class="row g-4">
            <?php 
            // Icon mapping for categories
            $category_icons = [
                'Books' => 'book',
                'Clothing' => 'bag',
                'Electronics' => 'laptop',
                'Home & Kitchen' => 'house-door',
                'Sports' => 'trophy',
                'Toys' => 'controller'
            ];
            
            while ($category = $categories_result->fetch_assoc()): 
                $icon = isset($category_icons[$category['name']]) ? $category_icons[$category['name']] : 'tag';
            ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="products.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                        <div class="category-card">
                            <div class="category-icon">
                                <i class="bi bi-<?php echo $icon; ?>"></i>
                            </div>
                            <h6 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h6>
                            <small class="text-muted">Explore</small>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    
    <!-- Featured Products Section -->
    <section class="mb-5 pt-3 pb-5">
        <div class="text-center mb-4">
            <span class="badge bg-warning text-white px-3 py-2 mb-3">
                <i class="bi bi-star-fill me-1"></i> Featured
            </span>
            <h2 class="display-5 fw-bold mb-3">Featured Products</h2>
            <p class="text-muted">Discover our handpicked selection of amazing products</p>
        </div>
        
        <?php if ($featured_result->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($product = $featured_result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 product-card">
                            <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                <img src="<?php echo get_product_image($product['image_url']); ?>" 
                                     class="card-img-top product-image" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-secondary mb-2 align-self-start">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </span>
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                </a>
                                <p class="card-text text-muted small">
                                    <?php echo htmlspecialchars(substr($product['description'], 0, 80)) . '...'; ?>
                                </p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="h4 mb-0 text-primary"><?php echo format_price($product['price']); ?></span>
                                        <span class="badge bg-success">In Stock: <?php echo $product['stock']; ?></span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-primary flex-grow-1 buy-now-btn" 
                                                data-product-id="<?php echo $product['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                            <i class="bi bi-bag-check"></i> Buy Now
                                        </button>
                                        <button class="btn btn-primary add-to-cart-btn" 
                                                data-product-id="<?php echo $product['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                            <i class="bi bi-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No products available at the moment.
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
