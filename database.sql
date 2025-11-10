-- E-Commerce Database Schema
-- Created for Modern Full-Stack E-Commerce Application

-- Drop existing tables if they exist
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    category_id INT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_price (price),
    INDEX idx_stock (stock)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders Table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    payment_method VARCHAR(50) NOT NULL,
    shipping_address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items Table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart Table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Admin Account
-- Password: admin123 (CHANGE THIS IN PRODUCTION!)
-- Note: Run generate_passwords.php to get correct password hashes, then update this table
INSERT INTO users (name, email, password_hash, phone, address, role) VALUES
('Admin User', 'admin@ecommerce.com', 'TEMP_HASH_RUN_generate_passwords.php', '1234567890', 'Admin Address', 'admin');

-- Insert Sample Categories
INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and gadgets'),
('Clothing', 'Fashion and apparel'),
('Books', 'Books and educational materials'),
('Home & Kitchen', 'Home appliances and kitchen items'),
('Sports', 'Sports equipment and accessories'),
('Toys', 'Toys and games for all ages');

-- Insert Sample Products
INSERT INTO products (name, description, price, stock, category_id, image_url) VALUES
('Wireless Headphones', 'High-quality Bluetooth headphones with noise cancellation', 79.99, 50, 1, 'headphones.jpg'),
('Smartphone', 'Latest model with advanced camera and processor', 699.99, 30, 1, 'smartphone.jpg'),
('Laptop', 'Powerful laptop for work and gaming', 1299.99, 20, 1, 'laptop.jpg'),
('T-Shirt', 'Comfortable cotton t-shirt', 19.99, 100, 2, 'tshirt.jpg'),
('Jeans', 'Classic blue denim jeans', 49.99, 75, 2, 'jeans.jpg'),
('Running Shoes', 'Lightweight running shoes', 89.99, 60, 5, 'shoes.jpg'),
('Coffee Maker', 'Automatic coffee maker with timer', 59.99, 40, 4, 'coffee-maker.jpg'),
('Blender', 'High-speed blender for smoothies', 39.99, 45, 4, 'blender.jpg'),
('Novel Book', 'Bestselling fiction novel', 14.99, 200, 3, 'book.jpg'),
('Basketball', 'Official size basketball', 24.99, 80, 5, 'basketball.jpg');

-- Create a test customer account
-- Password: customer123
-- Note: Run generate_passwords.php to get correct password hashes, then update this table
INSERT INTO users (name, email, password_hash, phone, address, role) VALUES
('John Doe', 'customer@example.com', 'TEMP_HASH_RUN_generate_passwords.php', '9876543210', '123 Main Street, City, State 12345', 'customer');
