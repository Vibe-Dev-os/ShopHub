<?php
/**
 * Common Functions Library
 * Reusable functions for the e-commerce application
 */

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect to a page
 */
function redirect($page) {
    header("Location: " . SITE_URL . "/" . $page);
    exit();
}

/**
 * Generate CSRF Token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 */
function set_message($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

/**
 * Display flash message
 */
function display_message() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message']['type'];
        $text = htmlspecialchars($_SESSION['message']['text']);
        
        $alert_class = '';
        switch($type) {
            case 'success':
                $alert_class = 'alert-success';
                break;
            case 'error':
                $alert_class = 'alert-danger';
                break;
            case 'warning':
                $alert_class = 'alert-warning';
                break;
            case 'info':
                $alert_class = 'alert-info';
                break;
            default:
                $alert_class = 'alert-info';
        }
        
        echo "<div class='alert {$alert_class} alert-dismissible fade show' role='alert'>
                {$text}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        
        unset($_SESSION['message']);
    }
}

/**
 * Validate email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength
 */
function validate_password($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

/**
 * Get cart count for current user
 */
function get_cart_count($conn) {
    if (!is_logged_in()) {
        return 0;
    }
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['total'] ?? 0;
}

/**
 * Format price in Philippine Peso
 */
function format_price($price) {
    return '₱' . number_format($price, 2);
}

/**
 * Upload image file
 */
function upload_image($file) {
    // Check if file was uploaded
    if (!isset($file)) {
        return ['success' => false, 'message' => 'No file provided'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $message = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : 'Unknown upload error';
        return ['success' => false, 'message' => $message];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed size of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
    }
    
    // Check file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }
    
    // Verify it's an actual image (skip for JFIF as it's a JPEG variant)
    if ($file_extension !== 'jfif') {
        $check = getimagesize($file['tmp_name']);
        if ($check === false) {
            return ['success' => false, 'message' => 'File is not a valid image'];
        }
    } else {
        // For JFIF files, check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg', 'image/jpg', 'image/pjpeg'])) {
            return ['success' => false, 'message' => 'JFIF file is not a valid JPEG image'];
        }
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $upload_path = UPLOAD_DIR . $new_filename;
    
    // Create upload directory if it doesn't exist
    if (!file_exists(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            return ['success' => false, 'message' => 'Failed to create upload directory'];
        }
    }
    
    // Check if directory is writable
    if (!is_writable(UPLOAD_DIR)) {
        return ['success' => false, 'message' => 'Upload directory is not writable'];
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file. Check directory permissions.'];
    }
}

/**
 * Delete image file
 */
function delete_image($filename) {
    if (empty($filename)) {
        return false;
    }
    
    $file_path = UPLOAD_DIR . $filename;
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    
    return false;
}

/**
 * Get product image URL
 */
function get_product_image($image_url) {
    if (empty($image_url)) {
        return SITE_URL . '/assets/images/no-image.png';
    }
    
    return SITE_URL . '/assets/uploads/' . htmlspecialchars($image_url);
}

/**
 * Paginate results
 */
function paginate($total_records, $records_per_page, $current_page) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_pages' => $total_pages,
        'offset' => $offset,
        'current_page' => $current_page
    ];
}

/**
 * Time ago function
 */
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Require login
 */
function require_login() {
    if (!is_logged_in()) {
        set_message('warning', 'Please login to continue');
        redirect('login.php');
    }
}

/**
 * Require admin
 */
function require_admin() {
    if (!is_logged_in() || !is_admin()) {
        set_message('error', 'Access denied. Admin privileges required.');
        redirect('index.php');
    }
}
?>
