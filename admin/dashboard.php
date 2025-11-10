<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_admin();

$page_title = 'Dashboard - Admin Panel';

// Get statistics
$stats = [];

// Total Products
$result = $conn->query("SELECT COUNT(*) as total FROM products");
$stats['products'] = $result->fetch_assoc()['total'];

// Total Orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders");
$stats['orders'] = $result->fetch_assoc()['total'];

// Total Users
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$stats['users'] = $result->fetch_assoc()['total'];

// Total Sales
$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'Cancelled'");
$stats['sales'] = $result->fetch_assoc()['total'] ?? 0;

// Recent Orders
$recent_orders = $conn->query("SELECT o.*, u.name as customer_name 
                               FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               ORDER BY o.created_at DESC 
                               LIMIT 10");

// Low Stock Products
$low_stock = $conn->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5");

// Order Status Distribution
$status_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$status_result = $conn->query($status_query);
$status_data = [];
while ($row = $status_result->fetch_assoc()) {
    $status_data[$row['status']] = $row['count'];
}

include 'includes/admin_header.php';
?>

<?php display_message(); ?>

<h2 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h2>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2">Total Sales</h6>
                        <h3 class="mb-0 fw-bold"><?php echo format_price($stats['sales']); ?></h3>
                        <small class="opacity-75">Revenue Generated</small>
                    </div>
                    <i class="bi bi-cash-coin display-4"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="orders.php" class="text-white text-decoration-none small">
                    <i class="bi bi-arrow-right-circle"></i> View Orders
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2">Total Orders</h6>
                        <h3 class="mb-0 fw-bold"><?php echo $stats['orders']; ?></h3>
                        <small class="opacity-75">Orders Placed</small>
                    </div>
                    <i class="bi bi-cart-check display-4"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="orders.php" class="text-white text-decoration-none small">
                    <i class="bi bi-arrow-right-circle"></i> Manage Orders
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2">Total Products</h6>
                        <h3 class="mb-0 fw-bold"><?php echo $stats['products']; ?></h3>
                        <small class="opacity-75">In Inventory</small>
                    </div>
                    <i class="bi bi-box-seam display-4"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="products.php" class="text-white text-decoration-none small">
                    <i class="bi bi-arrow-right-circle"></i> View Products
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2">Total Users</h6>
                        <h3 class="mb-0 fw-bold"><?php echo $stats['users']; ?></h3>
                        <small class="opacity-75">Registered Customers</small>
                    </div>
                    <i class="bi bi-people display-4"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="users.php" class="text-white text-decoration-none small">
                    <i class="bi bi-arrow-right-circle"></i> View Users
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock & Order Status - Moved Below Stats -->
<div class="row mb-4">
    <div class="col-md-6">
        <!-- Low Stock Alert -->
        <div class="card h-100">
            <div class="card-header bg-danger text-white py-2">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Low Stock Alert</h6>
            </div>
            <div class="card-body p-3">
                <?php 
                $low_stock_check = $conn->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5");
                if ($low_stock_check->num_rows > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($product = $low_stock_check->fetch_assoc()): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-0 border-0">
                                <span><?php echo htmlspecialchars($product['name']); ?></span>
                                <span class="badge bg-danger"><?php echo $product['stock']; ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">All products have sufficient stock</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <!-- Order Status -->
        <div class="card h-100">
            <div class="card-header bg-info text-white py-2">
                <h6 class="mb-0"><i class="bi bi-pie-chart"></i> Order Status</h6>
            </div>
            <div class="card-body p-3">
                <?php 
                $status_check = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
                if ($status_check->num_rows > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($status = $status_check->fetch_assoc()): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-0 border-0">
                                <span><?php echo $status['status']; ?></span>
                                <span class="badge bg-primary"><?php echo $status['count']; ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No orders yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Orders -->
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Orders</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="align-middle">Order ID</th>
                                <th class="align-middle">Customer</th>
                                <th class="align-middle">Amount</th>
                                <th class="align-middle text-center">Status</th>
                                <th class="align-middle">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td class="align-middle"><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td class="align-middle"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td class="align-middle"><?php echo format_price($order['total_amount']); ?></td>
                                    <td class="align-middle text-center">
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
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <i class="bi <?php echo $icon; ?> me-1"></i><?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td class="align-middle"><?php echo time_ago($order['created_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
