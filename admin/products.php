<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_admin();

$page_title = 'Manage Products - Admin Panel';

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_message('error', 'Invalid security token');
    } else {
        // Add Product
        if (isset($_POST['add_product'])) {
            $name = sanitize_input($_POST['name']);
            $description = sanitize_input($_POST['description']);
            $price = floatval($_POST['price']);
            $stock = intval($_POST['stock']);
            $category_id = intval($_POST['category_id']);
            
            $image_url = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_image($_FILES['image']);
                if ($upload_result['success']) {
                    $image_url = $upload_result['filename'];
                } else {
                    set_message('error', $upload_result['message']);
                    redirect('products.php');
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, category_id, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiss", $name, $description, $price, $stock, $category_id, $image_url);
            
            if ($stmt->execute()) {
                set_message('success', 'Product added successfully');
            } else {
                set_message('error', 'Failed to add product');
            }
            $stmt->close();
        }
        
        // Edit Product
        if (isset($_POST['edit_product'])) {
            $product_id = intval($_POST['product_id']);
            $name = sanitize_input($_POST['name']);
            $description = sanitize_input($_POST['description']);
            $price = floatval($_POST['price']);
            $stock = intval($_POST['stock']);
            $category_id = intval($_POST['category_id']);
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Get old image
                $old_img_stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
                $old_img_stmt->bind_param("i", $product_id);
                $old_img_stmt->execute();
                $old_image = $old_img_stmt->get_result()->fetch_assoc()['image_url'];
                $old_img_stmt->close();
                
                // Upload new image
                $upload_result = upload_image($_FILES['image']);
                if ($upload_result['success']) {
                    // Delete old image
                    if (!empty($old_image)) {
                        delete_image($old_image);
                    }
                    
                    $image_url = $upload_result['filename'];
                    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, image_url = ? WHERE id = ?");
                    $stmt->bind_param("ssdissi", $name, $description, $price, $stock, $category_id, $image_url, $product_id);
                } else {
                    set_message('error', $upload_result['message']);
                    redirect('products.php');
                }
            } else {
                $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ? WHERE id = ?");
                $stmt->bind_param("ssdiii", $name, $description, $price, $stock, $category_id, $product_id);
            }
            
            if ($stmt->execute()) {
                set_message('success', 'Product updated successfully');
            } else {
                set_message('error', 'Failed to update product');
            }
            $stmt->close();
        }
        
        // Delete Product
        if (isset($_POST['delete_product'])) {
            $product_id = intval($_POST['product_id']);
            
            // Get image to delete
            $img_stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
            $img_stmt->bind_param("i", $product_id);
            $img_stmt->execute();
            $image_url = $img_stmt->get_result()->fetch_assoc()['image_url'];
            $img_stmt->close();
            
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            
            if ($stmt->execute()) {
                // Delete image file
                if (!empty($image_url)) {
                    delete_image($image_url);
                }
                set_message('success', 'Product deleted successfully');
            } else {
                set_message('error', 'Failed to delete product');
            }
            $stmt->close();
        }
    }
    
    redirect('products.php');
}

// Get all products
$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   ORDER BY p.created_at DESC";
$products_result = $conn->query($products_query);

// Store products in array for modal rendering
$products = [];
while ($prod = $products_result->fetch_assoc()) {
    $products[] = $prod;
}

// Get categories for dropdown
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");

include 'includes/admin_header.php';
?>

<?php display_message(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-seam"></i> Manage Products</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="bi bi-plus-circle"></i> Add New Product
    </button>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="align-middle">ID</th>
                        <th class="align-middle">Image</th>
                        <th class="align-middle">Name</th>
                        <th class="align-middle">Category</th>
                        <th class="align-middle">Price</th>
                        <th class="align-middle text-center">Stock</th>
                        <th class="align-middle text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="align-middle"><?php echo $product['id']; ?></td>
                            <td class="align-middle">
                                <img src="<?php echo get_product_image($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="rounded"
                                     style="width: 50px; height: 50px; object-fit: cover;">
                            </td>
                            <td class="align-middle"><?php echo htmlspecialchars($product['name']); ?></td>
                            <td class="align-middle"><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td class="align-middle"><?php echo format_price($product['price']); ?></td>
                            <td class="align-middle text-center">
                                <span class="badge <?php echo $product['stock'] < 10 ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo $product['stock']; ?>
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <button class="btn btn-sm btn-warning" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editProductModal<?php echo $product['id']; ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteProductModal<?php echo $product['id']; ?>">
                                    <i class="bi bi-trash"></i>
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

<!-- Edit Product Modals -->
<?php foreach ($products as $product): ?>
    <div class="modal fade" id="editProductModal<?php echo $product['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Product</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Name</label>
                                                        <input type="text" name="name" class="form-control" 
                                                               value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Category</label>
                                                        <select name="category_id" class="form-select" required>
                                                            <?php
                                                            $categories_result->data_seek(0);
                                                            while ($cat = $categories_result->fetch_assoc()):
                                                            ?>
                                                                <option value="<?php echo $cat['id']; ?>" 
                                                                        <?php echo $cat['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                                </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" class="form-control" rows="2" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Price</label>
                                                        <input type="number" name="price" class="form-control" step="0.01" 
                                                               value="<?php echo $product['price']; ?>" required>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Stock</label>
                                                        <input type="number" name="stock" class="form-control" 
                                                               value="<?php echo $product['stock']; ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Image (leave empty to keep current)</label>
                                                <input type="file" name="image" class="form-control" accept="image/*">
                                                <?php if (!empty($product['image_url'])): ?>
                                                    <small class="text-muted">Current: <?php echo htmlspecialchars($product['image_url']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="edit_product" class="btn btn-primary">Update Product</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
<?php endforeach; ?>

<!-- Delete Product Modals -->
<?php foreach ($products as $product): ?>
    <div class="modal fade" id="deleteProductModal<?php echo $product['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Delete Product</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($product['name']); ?></strong>?</p>
                                            <p class="text-danger">This action cannot be undone.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
<?php endforeach; ?>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    $categories_result->data_seek(0);
                                    while ($cat = $categories_result->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Price</label>
                                <input type="number" name="price" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" name="stock" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
