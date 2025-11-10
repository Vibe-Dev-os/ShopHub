<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

require_login();

// Initialize blockchain (with error handling)
try {
    require_once 'includes/blockchain.php';
    $blockchain = new Blockchain($conn);
    $blockchain->createGenesisBlock();
} catch (Exception $e) {
    error_log("Blockchain initialization error: " . $e->getMessage());
    $blockchain = null;
}

$page_title = 'Checkout - ' . SITE_NAME;
$user_id = $_SESSION['user_id'];

// Check if this is a "Buy Now" checkout
$is_buy_now = isset($_SESSION['buy_now_item']);
$cart_items = [];
$subtotal = 0;

if ($is_buy_now) {
    // Use buy now item from session
    $buy_now = $_SESSION['buy_now_item'];
    
    // Verify product still exists and has stock
    $product_stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
    $product_stmt->bind_param("i", $buy_now['product_id']);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    
    if ($product_result->num_rows === 0) {
        unset($_SESSION['buy_now_item']);
        set_message('error', 'Product not found');
        redirect('products.php');
    }
    
    $product = $product_result->fetch_assoc();
    $product_stmt->close();
    
    if ($buy_now['quantity'] > $product['stock']) {
        unset($_SESSION['buy_now_item']);
        set_message('error', 'Insufficient stock for this product');
        redirect('products.php');
    }
    
    // Create cart item array for buy now
    $item = [
        'product_id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => $buy_now['quantity'],
        'stock' => $product['stock'],
        'total' => $product['price'] * $buy_now['quantity']
    ];
    
    $cart_items[] = $item;
    $subtotal = $item['total'];
    
} else {
    // Regular cart checkout
    // Check if specific items are selected from POST or session
    $selected_items = isset($_POST['selected_items']) ? $_POST['selected_items'] : '';
    
    // Debug: Log what we received
    error_log("POST selected_items: " . $selected_items);
    error_log("Session selected_cart_items: " . print_r($_SESSION['selected_cart_items'] ?? [], true));
    
    // Store in session if coming from cart page
    if (!empty($selected_items)) {
        $_SESSION['selected_cart_items'] = explode(',', $selected_items);
        error_log("Stored in session: " . print_r($_SESSION['selected_cart_items'], true));
    }
    
    // Use session data if available
    if (isset($_SESSION['selected_cart_items']) && !empty($_SESSION['selected_cart_items'])) {
        $selected_ids = $_SESSION['selected_cart_items'];
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        
        $cart_query = "SELECT c.*, p.name, p.price, p.stock, p.image_url 
                       FROM cart c 
                       JOIN products p ON c.product_id = p.id 
                       WHERE c.user_id = ? AND c.id IN ($placeholders)";
        $stmt = $conn->prepare($cart_query);
        
        // Bind parameters dynamically
        $types = 'i' . str_repeat('i', count($selected_ids));
        $params = array_merge([$user_id], $selected_ids);
        $stmt->bind_param($types, ...$params);
    } else {
        // Checkout all cart items
        $cart_query = "SELECT c.*, p.name, p.price, p.stock, p.image_url 
                       FROM cart c 
                       JOIN products p ON c.product_id = p.id 
                       WHERE c.user_id = ?";
        $stmt = $conn->prepare($cart_query);
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $cart_result = $stmt->get_result();
    
    // Check if cart is empty
    if ($cart_result->num_rows === 0) {
        unset($_SESSION['selected_cart_items']);
        set_message('warning', 'Your cart is empty or no items selected');
        redirect('cart.php');
    }
    
    // Calculate totals and validate stock
    $stock_error = false;
    
    while ($item = $cart_result->fetch_assoc()) {
        if ($item['quantity'] > $item['stock']) {
            $stock_error = true;
            set_message('error', 'Some items in your cart exceed available stock. Please update your cart.');
            redirect('cart.php');
        }
        
        $item['total'] = $item['price'] * $item['quantity'];
        $subtotal += $item['total'];
        $cart_items[] = $item;
    }
}

$shipping = $subtotal > 50 ? 0 : 10;
$total = $subtotal + $shipping;

// Get user details
$user_stmt = $conn->prepare("SELECT name, email, phone, address FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_message('error', 'Invalid security token');
        redirect('checkout.php');
    }
    
    $payment_method = sanitize_input($_POST['payment_method'] ?? '');
    $shipping_address = sanitize_input($_POST['shipping_address'] ?? '');
    
    if (empty($payment_method) || empty($shipping_address)) {
        set_message('error', 'Please fill in all required fields');
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create order
            $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_method, shipping_address) 
                                         VALUES (?, ?, 'Pending', ?, ?)");
            $order_stmt->bind_param("idss", $user_id, $total, $payment_method, $shipping_address);
            $order_stmt->execute();
            $order_id = $conn->insert_id;
            $order_stmt->close();
            
            // Insert order items and update stock
            foreach ($cart_items as $item) {
                // Insert order item
                $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                            VALUES (?, ?, ?, ?)");
                $item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $item_stmt->execute();
                $item_stmt->close();
                
                // Update product stock
                $stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stock_stmt->execute();
                $stock_stmt->close();
            }
            
            // Clear cart items based on checkout type
            if ($is_buy_now) {
                // Clear buy now session
                unset($_SESSION['buy_now_item']);
            } else if (isset($_SESSION['selected_cart_items']) && !empty($_SESSION['selected_cart_items'])) {
                // Clear only selected items from cart
                $selected_ids = $_SESSION['selected_cart_items'];
                $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
                $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND id IN ($placeholders)");
                $types = 'i' . str_repeat('i', count($selected_ids));
                $params = array_merge([$user_id], $selected_ids);
                $clear_stmt->bind_param($types, ...$params);
                $clear_stmt->execute();
                $clear_stmt->close();
                unset($_SESSION['selected_cart_items']);
            } else {
                // Clear all cart items
                $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $clear_stmt->bind_param("i", $user_id);
                $clear_stmt->execute();
                $clear_stmt->close();
            }
            
            // Add order to blockchain (if available)
            $blockchain_message = '';
            if ($blockchain !== null) {
                try {
                    $blockchain_data = [
                        'order_id' => $order_id,
                        'user_id' => $user_id,
                        'total_amount' => $total,
                        'payment_method' => $payment_method,
                        'status' => 'Pending',
                        'items_count' => count($cart_items),
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    $blockchain->addOrderBlock($order_id, $blockchain_data);
                    $blockchain_message = ' (Secured on Blockchain)';
                } catch (Exception $e) {
                    error_log("Blockchain order addition error: " . $e->getMessage());
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            set_message('success', 'Order placed successfully! Order ID: #' . $order_id . $blockchain_message);
            redirect('profile.php');
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            set_message('error', 'Failed to place order. Please try again.');
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <?php display_message(); ?>
    
    <h2 class="mb-4"><i class="bi bi-credit-card"></i> Checkout</h2>
    
    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <!-- Shipping Information -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-truck"></i> Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Shipping Address *</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                            <small class="text-muted">You can modify the address if needed</small>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-wallet2"></i> Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3 p-3 border rounded">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="gcash" value="GCash" checked>
                            <label class="form-check-label w-100" for="gcash">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-phone text-primary fs-4 me-3"></i>
                                    <div>
                                        <strong>GCash</strong>
                                        <p class="text-muted small mb-0">Pay via GCash mobile wallet</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3 p-3 border rounded">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="cod" value="Cash on Delivery">
                            <label class="form-check-label w-100" for="cod">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-cash-coin text-success fs-4 me-3"></i>
                                    <div>
                                        <strong>Cash on Delivery (COD)</strong>
                                        <p class="text-muted small mb-0">Pay when you receive your order</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3 p-3 border rounded">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="card" value="Credit/Debit Card">
                            <label class="form-check-label w-100" for="card">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-credit-card text-info fs-4 me-3"></i>
                                    <div>
                                        <strong>Credit/Debit Card</strong>
                                        <p class="text-muted small mb-0">Pay securely with Visa, Mastercard, or other cards</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="place_order" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-check-circle"></i> Place Order
                </button>
            </form>
        </div>
        
        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Order Summary</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></span>
                            <span><?php echo format_price($item['total']); ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span><?php echo format_price($subtotal); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span><?php echo $shipping > 0 ? format_price($shipping) : 'FREE'; ?></span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <strong>Total:</strong>
                        <strong class="text-success h4"><?php echo format_price($total); ?></strong>
                    </div>
                </div>
            </div>
            
            <!-- Security Notice -->
            <div class="alert alert-info">
                <i class="bi bi-shield-check"></i> <strong>Secure Checkout</strong>
                <p class="small mb-0">Your payment information is encrypted and secure.</p>
            </div>
        </div>
    </div>
</div>

<?php
$stmt->close();
include 'includes/footer.php';
?>
