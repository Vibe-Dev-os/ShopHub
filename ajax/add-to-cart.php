<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get product ID
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if product exists and has stock
$product_stmt = $conn->prepare("SELECT id, name, stock FROM products WHERE id = ?");
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

if ($product_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$product = $product_result->fetch_assoc();
$product_stmt->close();

if ($product['stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
    exit;
}

// Check if product already in cart
$check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$check_stmt->bind_param("ii", $user_id, $product_id);
$check_stmt->execute();
$cart_result = $check_stmt->get_result();

if ($cart_result->num_rows > 0) {
    // Update quantity
    $cart_item = $cart_result->fetch_assoc();
    $new_quantity = $cart_item['quantity'] + $quantity;
    
    if ($new_quantity > $product['stock']) {
        echo json_encode(['success' => false, 'message' => 'Cannot add more items. Stock limit reached.']);
        exit;
    }
    
    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
    }
    $update_stmt->close();
} else {
    // Insert new cart item
    $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
    
    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product added to cart successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
    }
    $insert_stmt->close();
}

$check_stmt->close();
$conn->close();
