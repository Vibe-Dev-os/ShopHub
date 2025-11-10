<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/blockchain.php';

require_admin();

$page_title = 'Blockchain Explorer - Admin Panel';

// Initialize blockchain
$blockchain = new Blockchain($conn);

// Verify blockchain integrity
$verification = $blockchain->verifyChain();

// Get blockchain statistics
$stats = $blockchain->getStats();

// Get all blocks
$blocks_query = $conn->query("SELECT * FROM blockchain_orders ORDER BY block_index DESC");
$blocks = [];
while ($row = $blocks_query->fetch_assoc()) {
    $row['data'] = json_decode($row['data'], true);
    $blocks[] = $row;
}

include 'includes/admin_header.php';
?>

<?php display_message(); ?>

<h2 class="mb-4"><i class="bi bi-diagram-3"></i> Blockchain Explorer</h2>

<!-- Blockchain Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="text-uppercase mb-2">Total Blocks</h6>
                <h3 class="mb-0 fw-bold"><?php echo $stats['total_blocks'] ?? 0; ?></h3>
                <small class="opacity-75">In Blockchain</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card <?php echo $verification['valid'] ? 'bg-success' : 'bg-danger'; ?> text-white">
            <div class="card-body">
                <h6 class="text-uppercase mb-2">Chain Status</h6>
                <h3 class="mb-0 fw-bold"><?php echo $verification['valid'] ? 'VALID' : 'INVALID'; ?></h3>
                <small class="opacity-75">Integrity Check</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="text-uppercase mb-2">First Block</h6>
                <h3 class="mb-0 fw-bold"><?php echo $stats['first_block_time'] ? date('M d, Y', $stats['first_block_time']) : 'N/A'; ?></h3>
                <small class="opacity-75">Genesis Time</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h6 class="text-uppercase mb-2">Last Block</h6>
                <h3 class="mb-0 fw-bold"><?php echo $stats['last_block_time'] ? date('M d, Y', $stats['last_block_time']) : 'N/A'; ?></h3>
                <small class="opacity-75">Latest Time</small>
            </div>
        </div>
    </div>
</div>

<!-- Verification Result -->
<?php if (!$verification['valid']): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Blockchain Integrity Error:</strong> <?php echo $verification['error']; ?>
</div>
<?php else: ?>
<div class="alert alert-success">
    <i class="bi bi-shield-check"></i>
    <strong>Blockchain Verified:</strong> All blocks are valid and properly linked.
</div>
<?php endif; ?>

<!-- Blockchain Blocks -->
<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-boxes"></i> Blockchain Blocks</h5>
    </div>
    <div class="card-body">
        <?php if (empty($blocks)): ?>
            <p class="text-muted">No blocks in blockchain yet.</p>
        <?php else: ?>
            <?php foreach ($blocks as $block): ?>
                <div class="card mb-3 border-<?php echo $block['block_index'] == 0 ? 'warning' : 'primary'; ?>">
                    <div class="card-header bg-<?php echo $block['block_index'] == 0 ? 'warning' : 'light'; ?>">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-0">
                                    <i class="bi bi-box"></i> 
                                    Block #<?php echo $block['block_index']; ?>
                                    <?php if ($block['block_index'] == 0): ?>
                                        <span class="badge bg-warning text-dark">GENESIS</span>
                                    <?php endif; ?>
                                </h6>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> 
                                    <?php echo date('M d, Y H:i:s', $block['timestamp']); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <strong>Order ID:</strong> 
                                    <?php if ($block['order_id'] > 0): ?>
                                        <a href="orders.php" class="text-decoration-none">#<?php echo $block['order_id']; ?></a>
                                    <?php else: ?>
                                        <span class="text-muted">Genesis Block</span>
                                    <?php endif; ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Nonce:</strong> 
                                    <code><?php echo $block['nonce']; ?></code>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <strong>Hash:</strong><br>
                                    <code class="small text-break"><?php echo $block['hash']; ?></code>
                                </p>
                                <p class="mb-0">
                                    <strong>Previous Hash:</strong><br>
                                    <code class="small text-break"><?php echo $block['previous_hash']; ?></code>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($block['order_id'] > 0 && isset($block['data'])): ?>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <strong>Block Data:</strong>
                                    <div class="bg-light p-3 rounded mt-2">
                                        <?php if (isset($block['data']['type']) && $block['data']['type'] == 'genesis'): ?>
                                            <p class="mb-0"><?php echo $block['data']['message']; ?></p>
                                        <?php else: ?>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>User ID:</strong> <?php echo $block['data']['user_id'] ?? 'N/A'; ?></p>
                                                    <p class="mb-1"><strong>Total Amount:</strong> <?php echo format_price($block['data']['total_amount'] ?? 0); ?></p>
                                                    <p class="mb-1"><strong>Payment Method:</strong> <?php echo htmlspecialchars($block['data']['payment_method'] ?? 'N/A'); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Current Status:</strong> 
                                                        <span class="badge bg-info"><?php echo $block['data']['current_status'] ?? $block['data']['status']; ?></span>
                                                    </p>
                                                    <p class="mb-1"><strong>Items Count:</strong> <?php echo $block['data']['items_count'] ?? 0; ?></p>
                                                    <p class="mb-1"><strong>Created:</strong> <?php echo $block['data']['created_at'] ?? 'N/A'; ?></p>
                                                </div>
                                            </div>
                                            
                                            <?php if (isset($block['data']['status_history']) && !empty($block['data']['status_history'])): ?>
                                                <hr>
                                                <strong>Status History:</strong>
                                                <ul class="list-unstyled mt-2 mb-0">
                                                    <?php foreach ($block['data']['status_history'] as $history): ?>
                                                        <li class="mb-1">
                                                            <i class="bi bi-arrow-right-circle"></i>
                                                            <span class="badge bg-secondary"><?php echo $history['status']; ?></span>
                                                            <small class="text-muted">at <?php echo $history['updated_at']; ?></small>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
