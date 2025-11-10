<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_admin();

$page_title = 'Manage Users - Admin Panel';

// Get all users
$users_query = "SELECT u.*, 
                COUNT(DISTINCT o.id) as order_count,
                SUM(o.total_amount) as total_spent
                FROM users u
                LEFT JOIN orders o ON u.id = o.user_id
                GROUP BY u.id
                ORDER BY u.created_at DESC";
$users_result = $conn->query($users_query);

// Store users in array for modal rendering
$users = [];
while ($usr = $users_result->fetch_assoc()) {
    $users[] = $usr;
}

include 'includes/admin_header.php';
?>

<?php display_message(); ?>

<h2 class="mb-4"><i class="bi bi-people"></i> Manage Users</h2>

<!-- Users Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td>
                                <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo $user['order_count']; ?></td>
                            <td><?php echo format_price($user['total_spent'] ?? 0); ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewUserModal<?php echo $user['id']; ?>">
                                    <i class="bi bi-eye"></i>
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

<!-- View User Modals -->
<?php foreach ($users as $user): ?>
    <div class="modal fade" id="viewUserModal<?php echo $user['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">User Details: <?php echo htmlspecialchars($user['name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <h6>Personal Information</h6>
                                                <p>
                                                    <strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?><br>
                                                    <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
                                                    <strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?><br>
                                                    <strong>Role:</strong> <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Statistics</h6>
                                                <p>
                                                    <strong>Total Orders:</strong> <?php echo $user['order_count']; ?><br>
                                                    <strong>Total Spent:</strong> <?php echo format_price($user['total_spent'] ?? 0); ?><br>
                                                    <strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <h6>Address</h6>
                                        <p><?php echo nl2br(htmlspecialchars($user['address'])); ?></p>
                                        
                                        <?php if ($user['order_count'] > 0): ?>
                                            <h6>Recent Orders</h6>
                                            <?php
                                            $user_orders_stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                                            $user_orders_stmt->bind_param("i", $user['id']);
                                            $user_orders_stmt->execute();
                                            $user_orders_result = $user_orders_stmt->get_result();
                                            ?>
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Order ID</th>
                                                        <th>Date</th>
                                                        <th>Total</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($order = $user_orders_result->fetch_assoc()): ?>
                                                        <tr>
                                                            <td>#<?php echo $order['id']; ?></td>
                                                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                            <td><?php echo format_price($order['total_amount']); ?></td>
                                                            <td>
                                                                <?php
                                                                $badge_class = '';
                                                                switch ($order['status']) {
                                                                    case 'Pending': $badge_class = 'bg-warning'; break;
                                                                    case 'Processing': $badge_class = 'bg-info'; break;
                                                                    case 'Shipped': $badge_class = 'bg-primary'; break;
                                                                    case 'Delivered': $badge_class = 'bg-success'; break;
                                                                    case 'Cancelled': $badge_class = 'bg-danger'; break;
                                                                }
                                                                ?>
                                                                <span class="badge <?php echo $badge_class; ?>">
                                                                    <?php echo $order['status']; ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                            <?php $user_orders_stmt->close(); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
<?php endforeach; ?>

<?php include 'includes/admin_footer.php'; ?>
