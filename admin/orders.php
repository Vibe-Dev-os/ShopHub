<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_admin();

$page_title = 'Manage Orders - Admin Panel';

// Initialize blockchain (with error handling)
try {
    require_once '../includes/blockchain.php';
    $blockchain = new Blockchain($conn);
} catch (Exception $e) {
    error_log("Blockchain initialization error: " . $e->getMessage());
    $blockchain = null;
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_message('error', 'Invalid security token');
    } else {
        $order_id = intval($_POST['order_id']);
        $status = sanitize_input($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        
        if ($stmt->execute()) {
            // Update blockchain (if available)
            $blockchain_message = '';
            if ($blockchain !== null) {
                try {
                    $blockchain->updateOrderStatus($order_id, $status);
                    $blockchain_message = ' (Blockchain Updated)';
                } catch (Exception $e) {
                    error_log("Blockchain update error: " . $e->getMessage());
                }
            }
            set_message('success', 'Order status updated successfully' . $blockchain_message);
        } else {
            set_message('error', 'Failed to update order status');
        }
        $stmt->close();
    }
    
    redirect('orders.php');
}

// Get all orders
$orders_query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
                 FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 ORDER BY o.created_at DESC";
$orders_result = $conn->query($orders_query);

// Store orders in array for modal rendering
$orders = [];
while ($ord = $orders_result->fetch_assoc()) {
    $orders[] = $ord;
}

include 'includes/admin_header.php';
?>

<?php display_message(); ?>

<h2 class="mb-4"><i class="bi bi-cart-check"></i> Manage Orders</h2>

<!-- Orders Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="align-middle">Order ID</th>
                        <th class="align-middle">Customer</th>
                        <th class="align-middle">Total</th>
                        <th class="align-middle">Payment</th>
                        <th class="align-middle text-center">Status</th>
                        <th class="align-middle">Date</th>
                        <th class="align-middle text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="align-middle"><strong>#<?php echo $order['id']; ?></strong></td>
                            <td class="align-middle">
                                <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                            </td>
                            <td class="align-middle"><?php echo format_price($order['total_amount']); ?></td>
                            <td class="align-middle"><?php echo htmlspecialchars($order['payment_method']); ?></td>
                            <td class="align-middle text-center">
                                <?php
                                $badge_class = '';
                                switch ($order['status']) {
                                    case 'Pending': 
                                        $badge_class = 'bg-warning text-dark'; 
                                        $icon = 'bi-clock';
                                        break;
                                    case 'Processing': 
                                        $badge_class = 'bg-info text-dark'; 
                                        $icon = 'bi-arrow-repeat';
                                        break;
                                    case 'Shipped': 
                                        $badge_class = 'bg-primary text-white'; 
                                        $icon = 'bi-truck';
                                        break;
                                    case 'Delivered': 
                                        $badge_class = 'bg-success text-white'; 
                                        $icon = 'bi-check-circle';
                                        break;
                                    case 'Cancelled': 
                                        $badge_class = 'bg-danger text-white'; 
                                        $icon = 'bi-x-circle';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <i class="bi <?php echo $icon; ?> me-1"></i><?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td class="align-middle"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                            <td class="align-middle text-center">
                                <button class="btn btn-sm btn-info" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewOrderModal<?php echo $order['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#updateStatusModal<?php echo $order['id']; ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modals Section -->

<!-- View Order Modals -->
<?php foreach ($orders as $order): ?>
    <?php
    $badge_class = '';
    $icon = '';
    switch ($order['status']) {
        case 'Pending': 
            $badge_class = 'bg-warning text-dark'; 
            $icon = 'bi-clock';
            break;
        case 'Processing': 
            $badge_class = 'bg-info text-dark'; 
            $icon = 'bi-arrow-repeat';
            break;
        case 'Shipped': 
            $badge_class = 'bg-primary text-white'; 
            $icon = 'bi-truck';
            break;
        case 'Delivered': 
            $badge_class = 'bg-success text-white'; 
            $icon = 'bi-check-circle';
            break;
        case 'Cancelled': 
            $badge_class = 'bg-danger text-white'; 
            $icon = 'bi-x-circle';
            break;
    }
    ?>
    <div class="modal fade" id="viewOrderModal<?php echo $order['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-dark text-white">
                                        <h5 class="modal-title">Order #<?php echo $order['id']; ?> Details</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <?php
                                        // Get order items
                                        $items_stmt = $conn->prepare("SELECT oi.*, p.name 
                                                                     FROM order_items oi 
                                                                     JOIN products p ON oi.product_id = p.id 
                                                                     WHERE oi.order_id = ?");
                                        $items_stmt->bind_param("i", $order['id']);
                                        $items_stmt->execute();
                                        $items_result = $items_stmt->get_result();
                                        ?>
                                        
                                        <div class="row g-4 mb-4">
                                            <div class="col-md-6">
                                                <h6 class="text-muted mb-3">Customer Information</h6>
                                                <div class="mb-2">
                                                    <strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
                                                </div>
                                                <div>
                                                    <strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-muted mb-3">Order Information</h6>
                                                <div class="mb-2">
                                                    <strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Status:</strong> <span class="badge <?php echo $badge_class; ?>"><i class="bi <?php echo $icon; ?> me-1"></i><?php echo $order['status']; ?></span>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Payment:</strong> <?php echo htmlspecialchars($order['payment_method']); ?>
                                                </div>
                                                <div>
                                                    <?php
                                                    if ($blockchain !== null) {
                                                        try {
                                                            $blockchain_block = $blockchain->getOrderBlock($order['id']);
                                                            if ($blockchain_block): ?>
                                                                <strong>Blockchain:</strong> 
                                                                <span class="badge bg-success">
                                                                    <i class="bi bi-shield-check"></i> Secured
                                                                </span>
                                                                <small class="text-muted d-block">Block #<?php echo $blockchain_block['block_index']; ?></small>
                                                            <?php else: ?>
                                                                <strong>Blockchain:</strong> 
                                                                <span class="badge bg-secondary">Not Recorded</span>
                                                            <?php endif;
                                                        } catch (Exception $e) {
                                                            // Silently fail if blockchain not available
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <h6 class="text-muted mb-2">Shipping Address</h6>
                                            <div><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
                                        </div>
                                        
                                        <h6 class="text-muted mb-3">Order Items</h6>
                                        <div class="table-responsive">
                                            <table class="table table-borderless mb-0">
                                                <thead class="border-bottom">
                                                    <tr>
                                                        <th class="pb-2">PRODUCT</th>
                                                        <th class="pb-2 text-end">PRICE</th>
                                                        <th class="pb-2 text-center">QUANTITY</th>
                                                        <th class="pb-2 text-end">TOTAL</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($item = $items_result->fetch_assoc()): ?>
                                                        <tr>
                                                            <td class="py-2"><?php echo htmlspecialchars($item['name']); ?></td>
                                                            <td class="py-2 text-end"><?php echo format_price($item['price']); ?></td>
                                                            <td class="py-2 text-center"><?php echo $item['quantity']; ?></td>
                                                            <td class="py-2 text-end"><?php echo format_price($item['price'] * $item['quantity']); ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                                <tfoot class="border-top">
                                                    <tr>
                                                        <th colspan="3" class="text-end pt-3 pb-0">Total:</th>
                                                        <th class="text-end pt-3 pb-0 text-primary"><?php echo format_price($order['total_amount']); ?></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        
                                        <?php $items_stmt->close(); ?>
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
<?php endforeach; ?>

<!-- Update Status Modals -->
<?php foreach ($orders as $order): ?>
    <div class="modal fade" id="updateStatusModal<?php echo $order['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Order Status</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Order Status</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="Pending" <?php echo $order['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Processing" <?php echo $order['status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                    <option value="Shipped" <?php echo $order['status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                    <option value="Delivered" <?php echo $order['status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                    <option value="Cancelled" <?php echo $order['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
<?php endforeach; ?>

<?php include 'includes/admin_footer.php'; ?>
