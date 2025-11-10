# ShopHub - E-commerce System with Blockchain

A modern PHP e-commerce platform with integrated blockchain technology for secure order tracking.

## Features

- 🛒 **Shopping Cart** - Add to cart and buy now functionality
- 👤 **User Authentication** - Registration and login system
- 📦 **Product Management** - Full CRUD operations for products
- 🏷️ **Category Management** - Organize products by categories
- 📋 **Order Management** - Track and manage customer orders
- 🔗 **Blockchain Integration** - Immutable order tracking on blockchain
- 👨‍💼 **Admin Panel** - Complete admin dashboard
- 📊 **Order Analytics** - View order statistics and trends

## Requirements

- **XAMPP** (or any LAMP/WAMP stack)
- **PHP** 7.4 or higher
- **MySQL** 5.7 or higher
- **Web Browser** (Chrome, Firefox, Edge, etc.)

## Installation

### 1. Install XAMPP

Download and install XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)

### 2. Clone/Download the Project

```bash
# Clone from GitHub
git clone https://github.com/Vibe-Dev-os/ShopHub.git

# Or download and extract to:
C:\xampp\htdocs\ecommerce
```

### 3. Create Database

1. Start **XAMPP Control Panel**
2. Start **Apache** and **MySQL**
3. Open **phpMyAdmin**: `http://localhost/phpmyadmin`
4. Create a new database named: `ecommerce`
5. Import the database:
   - Click on `ecommerce` database
   - Go to **Import** tab
   - Choose file: `database.sql`
   - Click **Go**

### 4. Configure Database Connection

Open `includes/config.php` and update if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecommerce');
```

### 5. Set Up Site URL

In `includes/config.php`, update the site URL:

```php
define('SITE_URL', 'http://localhost/ecommerce');
define('SITE_NAME', 'ShopHub');
```

### 6. Create Upload Directory

Make sure the uploads directory exists and is writable:

```
ecommerce/
└── uploads/
    └── products/
```

### 7. Access the Application

**User Site:**
```
http://localhost/ecommerce
```

**Admin Panel:**
```
http://localhost/ecommerce/admin
```

## Default Login Credentials

### Admin Account
- **Email:** `admin@ecommerce.com`
- **Password:** `admin123`

### Test Customer Account
- **Email:** `customer@example.com`
- **Password:** `customer123`

> **Important:** Change these passwords after first login!

## Quick Start Guide

### For Customers:

1. **Register** - Create a new account
2. **Browse Products** - View available products
3. **Add to Cart** - Select products and quantities
4. **Checkout** - Complete your order
5. **View Orders** - Check order status in your profile

### For Admins:

1. **Login** - Use admin credentials
2. **Dashboard** - View statistics and recent orders
3. **Manage Products** - Add, edit, or delete products
4. **Manage Categories** - Organize product categories
5. **Manage Orders** - Update order status
6. **View Blockchain** - Check blockchain integrity

## Blockchain Features

Every order is automatically recorded on a private blockchain:

- **Immutable Records** - Orders cannot be tampered with
- **Status History** - Complete audit trail of status changes
- **Chain Verification** - Verify blockchain integrity
- **Block Explorer** - View all blocks in admin panel

### Accessing Blockchain Explorer

```
Admin Panel → Blockchain
```

View:
- Total blocks
- Chain validity status
- Individual block details
- Order history on blockchain

## Project Structure

```
ecommerce/
├── admin/                  # Admin panel files
│   ├── dashboard.php
│   ├── products.php
│   ├── categories.php
│   ├── orders.php
│   ├── users.php
│   ├── blockchain.php
│   └── includes/
├── assets/                 # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── images/
├── includes/              # Core files
│   ├── config.php        # Database configuration
│   ├── functions.php     # Helper functions
│   ├── header.php        # Site header
│   ├── footer.php        # Site footer
│   └── blockchain.php    # Blockchain class
├── uploads/              # Uploaded files
│   └── products/
├── index.php             # Homepage
├── products.php          # Products listing
├── product-detail.php    # Product details
├── cart.php              # Shopping cart
├── checkout.php          # Checkout page
├── profile.php           # User profile
├── login.php             # Login page
├── register.php          # Registration page
└── database.sql          # Database schema
```

## Troubleshooting

### Common Issues:

**1. Page shows "This page isn't working" (HTTP 500)**
- Check PHP error logs in `C:\xampp\php\logs`
- Ensure all files are properly uploaded
- Verify database connection in `config.php`

**2. Images not displaying**
- Check `uploads/products/` folder exists
- Verify folder permissions (should be writable)
- Check image paths in database

**3. Blockchain errors**
- The blockchain table is created automatically
- If issues persist, check MySQL error logs
- Verify database user has CREATE TABLE permissions

**4. Can't login to admin**
- Verify admin account exists in database
- Check `users` table for role = 'admin'
- Reset password if needed via phpMyAdmin

**5. Redirects not working**
- Check `.htaccess` file exists (if using Apache)
- Verify `SITE_URL` in `config.php` is correct
- Clear browser cache

## Database Tables

- `users` - User accounts (customers and admins)
- `categories` - Product categories
- `products` - Product information
- `cart` - Shopping cart items
- `orders` - Customer orders
- `order_items` - Order line items
- `blockchain_orders` - Blockchain records (auto-created)

## Security Notes

1. **Change default passwords** immediately
2. **Use strong passwords** for admin accounts
3. **Keep XAMPP updated** to latest version
4. **Backup database** regularly
5. **Don't expose** to public internet without proper security

## Technology Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript
- **Framework:** Bootstrap 5
- **Icons:** Bootstrap Icons
- **Blockchain:** Custom PHP implementation (SHA-256)

## Features in Detail

### User Features:
- User registration and authentication
- Product browsing with filters
- Shopping cart management
- Secure checkout process
- Order history and tracking
- Profile management

### Admin Features:
- Dashboard with analytics
- Product CRUD operations
- Category management
- Order management with status updates
- User management
- Blockchain explorer
- Low stock alerts

### Blockchain Features:
- Automatic order recording
- Proof of Work mining
- SHA-256 hashing
- Chain integrity verification
- Complete audit trail
- Tamper-proof records

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review error logs in XAMPP
3. Check database connection settings
4. Verify all files are uploaded correctly

## License

This project is open source and available for educational purposes.

## Credits

Developed by: Vibe-Dev-os
Repository: https://github.com/Vibe-Dev-os/ShopHub

---

**Enjoy using ShopHub! 🛍️🔗**
