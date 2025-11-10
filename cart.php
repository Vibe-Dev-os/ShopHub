<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

require_login();

$page_title = 'Shopping Cart - ' . SITE_NAME;
$user_id = $_SESSION['user_id'];

// Clear any previous checkout session data when viewing cart
if (isset($_SESSION['selected_cart_items'])) {
    unset($_SESSION['selected_cart_items']);
}
if (isset($_SESSION['buy_now_item'])) {
    unset($_SESSION['buy_now_item']);
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_message('error', 'Invalid security token');
    } else {
        // Update quantity
        if (isset($_POST['update_cart'])) {
            $cart_id = intval($_POST['cart_id']);
            $quantity = max(1, intval($_POST['quantity']));
            
            // Get product stock
            $stock_stmt = $conn->prepare("SELECT p.stock FROM cart c 
                                          JOIN products p ON c.product_id = p.id 
                                          WHERE c.id = ? AND c.user_id = ?");
            $stock_stmt->bind_param("ii", $cart_id, $user_id);
            $stock_stmt->execute();
            $stock_result = $stock_stmt->get_result();
            
            if ($stock_result->num_rows > 0) {
                $stock = $stock_result->fetch_assoc()['stock'];
                
                if ($quantity > $stock) {
                    set_message('error', 'Requested quantity exceeds available stock');
                } else {
                    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                    $update_stmt->bind_param("iii", $quantity, $cart_id, $user_id);
                    
                    if ($update_stmt->execute()) {
                        set_message('success', 'Cart updated successfully');
                    }
                    $update_stmt->close();
                }
            }
            $stock_stmt->close();
        }
        
        // Remove item
        if (isset($_POST['remove_item'])) {
            $cart_id = intval($_POST['cart_id']);
            
            $delete_stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $cart_id, $user_id);
            
            if ($delete_stmt->execute()) {
                set_message('success', 'Item removed from cart');
            }
            $delete_stmt->close();
        }
    }
    
    redirect('cart.php');
}

// Get cart items
$cart_query = "SELECT c.*, p.name, p.price, p.stock, p.image_url 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = ? 
               ORDER BY c.created_at DESC";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

// Calculate totals
$subtotal = 0;
$cart_items = [];
while ($item = $cart_result->fetch_assoc()) {
    $item['total'] = $item['price'] * $item['quantity'];
    $subtotal += $item['total'];
    $cart_items[] = $item;
}

$shipping = $subtotal > 50 ? 0 : 10;
$total = $subtotal + $shipping;

include 'includes/header.php';
?>

<div class="container my-4">
    <?php display_message(); ?>
    
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-cart3 fs-2 me-3 text-primary"></i>
        <h2 class="mb-0">Shopping Cart</h2>
        <span class="badge bg-primary ms-3"><?php echo count($cart_items); ?> items</span>
    </div>
    
    <?php if (count($cart_items) > 0): ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-0">
                        <?php foreach ($cart_items as $index => $item): ?>
                            <div class="cart-item p-4 <?php echo $index < count($cart_items) - 1 ? 'border-bottom' : ''; ?>" 
                                 data-cart-id="<?php echo $item['id']; ?>" style="cursor: pointer;">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="form-check">
                                            <input class="form-check-input cart-checkbox" type="checkbox" 
                                                   value="<?php echo $item['id']; ?>" 
                                                   id="cart_<?php echo $item['id']; ?>"
                                                   data-price="<?php echo $item['total']; ?>"
                                                   checked>
                                            <label class="form-check-label" for="cart_<?php echo $item['id']; ?>"></label>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-3">
                                        <div class="cart-item-image">
                                            <img src="<?php echo get_product_image($item['image_url']); ?>" 
                                                 class="img-fluid rounded" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-8">
                                        <h5 class="mb-2"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="text-muted mb-1"><?php echo format_price($item['price']); ?> each</p>
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="bi bi-check-circle"></i> Stock: <?php echo $item['stock']; ?>
                                        </span>
                                    </div>
                                    <div class="col-md-3 col-6 mt-3 mt-md-0">
                                        <label class="form-label small text-muted">Quantity</label>
                                        <form method="POST" action="" class="d-flex align-items-center">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <div class="input-group input-group-sm" style="max-width: 120px;">
                                                <input type="number" name="quantity" class="form-control text-center" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" max="<?php echo $item['stock']; ?>">
                                                <button type="submit" name="update_cart" class="btn btn-outline-secondary" title="Update">
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-3 col-6 mt-3 mt-md-0 text-end">
                                        <p class="h5 text-primary mb-2"><?php echo format_price($item['total']); ?></p>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="remove_item" class="btn btn-outline-danger">
                                                <i class="bi bi-trash"></i> Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <a href="products.php" class="btn btn-outline-primary w-100 mb-3">
                    <i class="bi bi-arrow-left"></i> Continue Shopping
                </a>
                <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-receipt"></i> Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                            <span class="text-muted">Subtotal:</span>
                            <span class="fw-semibold" id="cart-subtotal"><?php echo format_price($subtotal); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                            <span class="text-muted">Shipping:</span>
                            <span class="fw-semibold text-success" id="cart-shipping">
                                <?php echo $shipping > 0 ? format_price($shipping) : 'FREE'; ?>
                            </span>
                        </div>
                        
                        <div class="alert alert-info mb-3" id="shipping-alert" style="display: none;">
                            <i class="bi bi-info-circle"></i> 
                            <small>Add <strong id="shipping-needed">₱0.00</strong> more for free shipping!</small>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <span class="h5 mb-0">Total:</span>
                            <span class="h4 text-primary mb-0" id="cart-total"><?php echo format_price($total); ?></span>
                        </div>
                        
                        <form method="POST" action="checkout.php" id="checkout-form" onsubmit="return confirmCheckout()">
                            <input type="hidden" name="selected_items" id="selected-items" value="">
                            <button type="submit" class="btn btn-primary w-100 btn-lg mb-2" id="checkout-btn">
                                <i class="bi bi-credit-card"></i> Proceed to Checkout (<span id="selected-count"><?php echo count($cart_items); ?></span>)
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="bi bi-shield-check"></i> Secure checkout
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x display-1 text-muted"></i>
            <h3 class="mt-3">Your cart is empty</h3>
            <p class="text-muted">Add some products to get started!</p>
            <a href="products.php" class="btn btn-primary">
                <i class="bi bi-shop"></i> Browse Products
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
$stmt->close();
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.cart-checkbox');
    const cartItems = document.querySelectorAll('.cart-item');
    const subtotalEl = document.getElementById('cart-subtotal');
    const shippingEl = document.getElementById('cart-shipping');
    const totalEl = document.getElementById('cart-total');
    const selectedCountEl = document.getElementById('selected-count');
    const selectedItemsInput = document.getElementById('selected-items');
    const shippingAlert = document.getElementById('shipping-alert');
    const shippingNeeded = document.getElementById('shipping-needed');
    const checkoutBtn = document.getElementById('checkout-btn');
    
    function formatPrice(amount) {
        return '₱' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
    
    // Make entire cart item clickable
    cartItems.forEach(function(cartItem) {
        cartItem.addEventListener('click', function(e) {
            // Don't toggle if clicking on buttons, inputs, or links
            if (e.target.closest('button') || e.target.closest('input[type="number"]') || e.target.closest('a')) {
                return;
            }
            
            const cartId = this.getAttribute('data-cart-id');
            const checkbox = document.getElementById('cart_' + cartId);
            
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                updateTotals();
                
                // Add visual feedback
                if (checkbox.checked) {
                    this.style.backgroundColor = '';
                } else {
                    this.style.backgroundColor = '#f8f9fa';
                }
            }
        });
    });
    
    function updateTotals() {
        let subtotal = 0;
        let selectedItems = [];
        let selectedCount = 0;
        
        checkboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                subtotal += parseFloat(checkbox.getAttribute('data-price'));
                selectedItems.push(checkbox.value);
                selectedCount++;
            }
        });
        
        const shipping = subtotal > 50 ? 0 : 10;
        const total = subtotal + shipping;
        
        // Update display
        subtotalEl.textContent = formatPrice(subtotal);
        totalEl.textContent = formatPrice(total);
        selectedCountEl.textContent = selectedCount;
        selectedItemsInput.value = selectedItems.join(',');
        
        // Debug: Log selected items
        console.log('Selected items:', selectedItems.join(','));
        
        // Update shipping
        if (shipping === 0) {
            shippingEl.textContent = 'FREE';
            shippingEl.classList.add('text-success');
            shippingAlert.style.display = 'none';
        } else {
            shippingEl.textContent = formatPrice(shipping);
            shippingEl.classList.remove('text-success');
            
            if (subtotal > 0 && subtotal < 50) {
                const needed = 50 - subtotal;
                shippingNeeded.textContent = formatPrice(needed);
                shippingAlert.style.display = 'block';
            } else {
                shippingAlert.style.display = 'none';
            }
        }
        
        // Disable checkout if no items selected
        if (selectedCount === 0) {
            checkoutBtn.disabled = true;
            checkoutBtn.classList.add('disabled');
        } else {
            checkoutBtn.disabled = false;
            checkoutBtn.classList.remove('disabled');
        }
    }
    
    // Add event listeners to checkboxes
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', updateTotals);
    });
    
    // Initialize on page load
    updateTotals();
    
    // Confirm checkout function
    window.confirmCheckout = function() {
        const selectedItems = document.getElementById('selected-items').value;
        console.log('Submitting checkout with items:', selectedItems);
        
        if (!selectedItems) {
            alert('Please select at least one item to checkout');
            return false;
        }
        return true;
    };
});
</script>

<?php
include 'includes/footer.php';
?>
