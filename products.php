<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Products - ' . SITE_NAME;

// Pagination
$records_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Filters
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'newest';

// Build query
$where_conditions = ["p.stock > 0"];
$params = [];
$types = "";

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if (!empty($search_query)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_clause = implode(" AND ", $where_conditions);

// Sorting
$order_by = "p.created_at DESC";
switch ($sort_by) {
    case 'price_low':
        $order_by = "p.price ASC";
        break;
    case 'price_high':
        $order_by = "p.price DESC";
        break;
    case 'name':
        $order_by = "p.name ASC";
        break;
}

// Count total records
$count_query = "SELECT COUNT(*) as total FROM products p WHERE {$where_clause}";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// Pagination calculation
$pagination = paginate($total_records, $records_per_page, $current_page);

// Get products
$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE {$where_clause} 
                   ORDER BY {$order_by} 
                   LIMIT ? OFFSET ?";

$stmt = $conn->prepare($products_query);
$params[] = $records_per_page;
$params[] = $pagination['offset'];
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$products_result = $stmt->get_result();

// Get categories for filter
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);

include 'includes/header.php';
?>

<div class="container">
    <?php display_message(); ?>
    
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-grid"></i> Products</h2>
            <?php if (!empty($search_query)): ?>
                <p class="text-muted">
                    Showing <?php echo $total_records; ?> results for "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                    <a href="products.php" class="btn btn-sm btn-outline-secondary ms-2">
                        <i class="bi bi-x-circle me-1"></i>Clear Search
                    </a>
                </p>
            <?php else: ?>
                <p class="text-muted">Browse our collection of <?php echo $total_records; ?> products</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="0">All Categories</option>
                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Sort By</label>
                            <select name="sort" class="form-select">
                                <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name: A to Z</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Products Grid -->
    <?php if ($products_result->num_rows > 0): ?>
        <div class="row g-4 mb-4">
            <?php while ($product = $products_result->fetch_assoc()): ?>
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
                                    <span class="badge bg-success">Stock: <?php echo $product['stock']; ?></span>
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
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php
                    $query_params = $_GET;
                    for ($i = 1; $i <= $pagination['total_pages']; $i++):
                        $query_params['page'] = $i;
                        $query_string = http_build_query($query_params);
                    ?>
                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo $query_string; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No products found matching your criteria.
        </div>
    <?php endif; ?>
</div>

<?php
$stmt->close();
include 'includes/footer.php';
?>
