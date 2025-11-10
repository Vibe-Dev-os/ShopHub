<?php
if (!isset($page_title)) {
    $page_title = 'Admin Panel - ' . SITE_NAME;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white" id="sidebar-wrapper">
            <div class="sidebar-heading text-center py-4 fs-4 fw-bold border-bottom">
                <i class="bi bi-speedometer2"></i> Admin Panel
            </div>
            <div class="list-group list-group-flush">
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/products.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="bi bi-box-seam"></i> Products
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="bi bi-tags"></i> Categories
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="bi bi-cart-check"></i> Orders
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/users.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="bi bi-people"></i> Users
                </a>
                <a href="<?php echo SITE_URL; ?>/index.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="bi bi-house"></i> Back to Store
                </a>
                <a href="<?php echo SITE_URL; ?>/logout.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="ms-auto">
                        <span class="navbar-text">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </span>
                    </div>
                </div>
            </nav>
            
            <div class="container-fluid p-4">
