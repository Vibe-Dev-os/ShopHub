<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    set_message('error', 'Invalid product');
    redirect('products.php');
}

// Get product details
$stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    set_message('error', 'Product not found');
    redirect('products.php');
}

$product = $result->fetch_assoc();
$stmt->close();

$page_title = htmlspecialchars($product['name']) . ' - ' . SITE_NAME;

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!is_logged_in()) {
        set_message('warning', 'Please login to add items to cart');
        redirect('login.php');
    }
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_message('error', 'Invalid security token');
    } else {
        $quantity = max(1, intval($_POST['quantity'] ?? 1));
        $user_id = $_SESSION['user_id'];
        
        // Check stock availability
        if ($quantity > $product['stock']) {
            set_message('error', 'Requested quantity exceeds available stock');
        } else {
            // Check if product already in cart
            $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $check_stmt->bind_param("ii", $user_id, $product_id);
            $check_stmt->execute();
            $cart_result = $check_stmt->get_result();
            
            if ($cart_result->num_rows > 0) {
                // Update quantity
                $cart_item = $cart_result->fetch_assoc();
                $new_quantity = $cart_item['quantity'] + $quantity;
                
                if ($new_quantity > $product['stock']) {
                    set_message('error', 'Cannot add more items. Stock limit reached.');
                } else {
                    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
                    
                    if ($update_stmt->execute()) {
                        set_message('success', 'Cart updated successfully');
                    } else {
                        set_message('error', 'Failed to update cart');
                    }
                    $update_stmt->close();
                }
            } else {
                // Insert new cart item
                $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                
                if ($insert_stmt->execute()) {
                    set_message('success', 'Product added to cart successfully');
                } else {
                    set_message('error', 'Failed to add product to cart');
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
    }
    
    redirect('product-detail.php?id=' . $product_id);
}

// Get related products (same category)
$related_stmt = $conn->prepare("SELECT * FROM products 
                                WHERE category_id = ? AND id != ? AND stock > 0 
                                ORDER BY RAND() LIMIT 4");
$related_stmt->bind_param("ii", $product['category_id'], $product_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();

include 'includes/header.php';
?>

<div class="container">
    <?php display_message(); ?>
    
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>
    
    <!-- Product Detail -->
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="product-detail-image-wrapper">
                <img src="<?php echo get_product_image($product['image_url']); ?>" 
                     class="product-detail-image" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="product-detail-info">
                <div class="mb-3">
                    <span class="badge bg-dark text-white px-3 py-2">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </span>
                </div>
                
                <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="mb-4">
                    <span class="display-6 fw-bold text-primary"><?php echo format_price($product['price']); ?></span>
                </div>
                
                <div class="mb-4">
                    <?php if ($product['stock'] > 0): ?>
                        <div class="alert alert-success d-inline-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <span><strong>In Stock</strong> - <?php echo $product['stock']; ?> available</span>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger d-inline-flex align-items-center">
                            <i class="bi bi-x-circle-fill me-2"></i>
                            <span><strong>Out of Stock</strong></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card mb-4 border-0 bg-light">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-info-circle text-primary"></i> Description
                        </h5>
                        <p class="card-text text-muted mb-0"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                </div>
                
                <?php if ($product['stock'] > 0): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-4">
                            <label for="quantity" class="form-label fw-semibold">Quantity</label>
                            <div class="input-group" style="max-width: 150px;">
                                <button class="btn btn-outline-secondary" type="button" onclick="decrementQty()">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" class="form-control text-center" id="quantity" name="quantity" 
                                       value="1" min="1" max="<?php echo $product['stock']; ?>">
                                <button class="btn btn-outline-secondary" type="button" onclick="incrementQty(<?php echo $product['stock']; ?>)">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary btn-lg w-100 buy-now-detail-btn" 
                                        data-product-id="<?php echo $product['id']; ?>">
                                    <i class="bi bi-bag-check"></i> Buy Now
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="d-grid">
                        <button class="btn btn-secondary btn-lg" disabled>
                            <i class="bi bi-x-circle"></i> Out of Stock
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4 pt-3 border-top">
                    <small class="text-muted d-flex align-items-center">
                        <i class="bi bi-clock me-2"></i> 
                        Added <?php echo time_ago($product['created_at']); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function incrementQty(max) {
        const input = document.getElementById('quantity');
        if (parseInt(input.value) < max) {
            input.value = parseInt(input.value) + 1;
        }
    }
    
    function decrementQty() {
        const input = document.getElementById('quantity');
        if (parseInt(input.value) > 1) {
            input.value = parseInt(input.value) - 1;
        }
    }
    </script>
    
    <!-- Related Products -->
    <?php if ($related_result->num_rows > 0): ?>
        <section class="mb-5">
            <h3 class="mb-4">Related Products</h3>
            <div class="row g-4">
                <?php while ($related = $related_result->fetch_assoc()): ?>
                    <div class="col-md-3">
                        <div class="card h-100 product-card">
                            <a href="product-detail.php?id=<?php echo $related['id']; ?>" class="text-decoration-none">
                                <img src="<?php echo get_product_image($related['image_url']); ?>" 
                                     class="card-img-top product-image" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-success mb-2 align-self-start">In Stock: <?php echo $related['stock']; ?></span>
                                <a href="product-detail.php?id=<?php echo $related['id']; ?>" class="text-decoration-none text-dark">
                                    <h6 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h6>
                                </a>
                                <p class="text-primary fw-bold mb-3"><?php echo format_price($related['price']); ?></p>
                                <div class="mt-auto">
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-primary flex-grow-1 buy-now-btn" 
                                                data-product-id="<?php echo $related['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($related['name']); ?>">
                                            <i class="bi bi-bag-check"></i> Buy Now
                                        </button>
                                        <button class="btn btn-primary add-to-cart-btn" 
                                                data-product-id="<?php echo $related['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($related['name']); ?>">
                                            <i class="bi bi-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php
$related_stmt->close();
include 'includes/footer.php';
?>
