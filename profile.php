<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

require_login();

$page_title = 'My Profile - ' . SITE_NAME;
$user_id = $_SESSION['user_id'];

// Get user details
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Get user orders
$orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

include 'includes/header.php';
?>

<div class="container my-4">
    <?php display_message(); ?>
    
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-person-circle fs-2 me-3 text-primary"></i>
        <h2 class="mb-0">My Profile</h2>
    </div>
    
    <div class="row g-4">
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body text-center p-4">
                    <div class="profile-avatar mb-3">
                        <i class="bi bi-person-circle display-1 text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($user['name']); ?></h4>
                    <p class="text-muted mb-3">
                        <i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    <span class="badge bg-dark px-3 py-2">
                        <i class="bi bi-person-badge me-1"></i><?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
            </div>
            
            <!-- Account Information Card -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Phone:</label>
                        <p class="mb-0 fw-semibold">
                            <i class="bi bi-telephone text-primary me-2"></i><?php echo htmlspecialchars($user['phone']); ?>
                        </p>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Address:</label>
                        <p class="mb-0">
                            <i class="bi bi-geo-alt text-primary me-2"></i><?php echo nl2br(htmlspecialchars($user['address'])); ?>
                        </p>
                    </div>
                    <hr>
                    <div class="mb-0">
                        <label class="text-muted small mb-1">Member Since:</label>
                        <p class="mb-0">
                            <i class="bi bi-calendar-check text-primary me-2"></i><?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-bag-check me-2"></i>Order History</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($orders_result->num_rows > 0): ?>
                        <div class="order-list">
                            <?php while ($order = $orders_result->fetch_assoc()): ?>
                                <?php
                                $status_class = '';
                                $status_icon = '';
                                switch ($order['status']) {
                                    case 'Pending':
                                        $status_class = 'bg-warning text-dark';
                                        $status_icon = 'clock';
                                        break;
                                    case 'Processing':
                                        $status_class = 'bg-info text-dark';
                                        $status_icon = 'arrow-repeat';
                                        break;
                                    case 'Shipped':
                                        $status_class = 'bg-primary';
                                        $status_icon = 'truck';
                                        break;
                                    case 'Delivered':
                                        $status_class = 'bg-success';
                                        $status_icon = 'check-circle';
                                        break;
                                    case 'Cancelled':
                                        $status_class = 'bg-danger';
                                        $status_icon = 'x-circle';
                                        break;
                                }
                                ?>
                                <div class="order-item border-bottom p-3">
                                    <div class="row align-items-center g-2">
                                        <div class="col-1">
                                            <strong class="text-primary">#<?php echo $order['id']; ?></strong>
                                        </div>
                                        <div class="col-2">
                                            <span class="text-muted small"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                                        </div>
                                        <div class="col-2">
                                            <strong class="text-dark"><?php echo format_price($order['total_amount']); ?></strong>
                                        </div>
                                        <div class="col-3">
                                            <span class="text-muted small"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                                        </div>
                                        <div class="col-2">
                                            <span class="badge <?php echo $status_class; ?> d-inline-flex align-items-center">
                                                <i class="bi bi-<?php echo $status_icon; ?> me-1"></i>
                                                <?php echo htmlspecialchars($order['status']); ?>
                                            </span>
                                        </div>
                                        <div class="col-2 text-end">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                        
                                        <!-- Order Details Modal -->
                                        <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title">
                                                            <i class="bi bi-receipt me-2"></i>Order #<?php echo $order['id']; ?> Details
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php
                                                        // Get order items
                                                        $items_stmt = $conn->prepare("SELECT oi.*, p.name, p.image_url 
                                                                                     FROM order_items oi 
                                                                                     JOIN products p ON oi.product_id = p.id 
                                                                                     WHERE oi.order_id = ?");
                                                        $items_stmt->bind_param("i", $order['id']);
                                                        $items_stmt->execute();
                                                        $items_result = $items_stmt->get_result();
                                                        ?>
                                                        
                                                        <div class="row g-3 mb-4">
                                                            <div class="col-md-6">
                                                                <label class="text-muted small d-block mb-1">
                                                                    <i class="bi bi-calendar-event me-1"></i>Order Date:
                                                                </label>
                                                                <div class="fw-semibold">
                                                                    <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="text-muted small d-block mb-1">
                                                                    <i class="bi bi-credit-card me-1"></i>Payment Method:
                                                                </label>
                                                                <div class="fw-semibold">
                                                                    <?php echo htmlspecialchars($order['payment_method']); ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="text-muted small d-block mb-1">
                                                                    <i class="bi bi-info-circle me-1"></i>Status:
                                                                </label>
                                                                <div>
                                                                    <span class="badge <?php echo $status_class; ?> d-inline-flex align-items-center">
                                                                        <i class="bi bi-<?php echo $status_icon; ?> me-1"></i>
                                                                        <?php echo $order['status']; ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="text-muted small d-block mb-1">
                                                                    <i class="bi bi-geo-alt me-1"></i>Shipping Address:
                                                                </label>
                                                                <div>
                                                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <h6 class="mb-3"><i class="bi bi-box-seam me-2"></i>Order Items:</h6>
                                                        <div class="table-responsive">
                                                            <table class="table table-borderless mb-0">
                                                                <thead class="border-bottom">
                                                                    <tr>
                                                                        <th class="pb-2">Product</th>
                                                                        <th class="text-end pb-2">Price</th>
                                                                        <th class="text-center pb-2">Quantity</th>
                                                                        <th class="text-end pb-2">Total</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php while ($item = $items_result->fetch_assoc()): ?>
                                                                        <tr>
                                                                            <td class="py-2"><?php echo htmlspecialchars($item['name']); ?></td>
                                                                            <td class="text-end py-2"><?php echo format_price($item['price']); ?></td>
                                                                            <td class="text-center py-2"><?php echo $item['quantity']; ?></td>
                                                                            <td class="text-end fw-semibold py-2"><?php echo format_price($item['price'] * $item['quantity']); ?></td>
                                                                        </tr>
                                                                    <?php endwhile; ?>
                                                                </tbody>
                                                                <tfoot class="border-top">
                                                                    <tr>
                                                                        <th colspan="3" class="text-end pt-2 pb-0 fw-bold">Total:</th>
                                                                        <th class="text-end text-primary pt-2 pb-0 fw-bold"><?php echo format_price($order['total_amount']); ?></th>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                        
                                                        <?php $items_stmt->close(); ?>
                                                    </div>
                                                    <div class="modal-footer py-2">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            <i class="bi bi-x-circle me-1"></i>Close
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bag-x display-4 text-muted"></i>
                            <p class="mt-3 text-muted">No orders yet</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="bi bi-shop me-2"></i>Start Shopping
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$orders_stmt->close();
include 'includes/footer.php';
?>
