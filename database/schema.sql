-- Bake & Take Database Schema
-- Run this script in MySQL/MariaDB to create the database

CREATE DATABASE IF NOT EXISTS bake_and_take;
USE bake_and_take;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    is_admin BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_method ENUM('email', 'phone') NULL,
    verification_token VARCHAR(255) NULL,
    verification_token_expires_at TIMESTAMP NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,

    -- Pending email change (two-step: verify old email via OTP, then verify new email via link)
    pending_email VARCHAR(100) NULL,
    pending_email_token VARCHAR(255) NULL,
    pending_email_expires TIMESTAMP NULL,
    pending_email_old_otp VARCHAR(10) NULL,
    email_change_step VARCHAR(20) NULL,
    email_change_cancel_token VARCHAR(255) NULL,

    -- Pending phone change (multi-step OTP + recovery)
    pending_phone VARCHAR(20) NULL,
    pending_phone_otp VARCHAR(10) NULL,
    pending_phone_expires TIMESTAMP NULL,
    phone_change_step VARCHAR(20) NULL,
    phone_recovery_token VARCHAR(255) NULL,

    -- Password reset (store only a hash of the token)
    password_reset_token_hash CHAR(64) NULL,
    password_reset_expires_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_verification_token (verification_token),
    KEY idx_pending_email_token (pending_email_token),
    KEY idx_pending_phone (pending_phone),
    KEY idx_email_cancel_token (email_change_cancel_token),
    KEY idx_password_reset_token_hash (password_reset_token_hash)
);

-- Create default admin account (password: admin123)
-- Run this after creating the table:
-- INSERT INTO users (first_name, last_name, email, password, is_admin) VALUES
-- ('Admin', 'User', 'admin@bakeandtake.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    stock INT DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Orders table  
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    first_name VARCHAR(50) NULL,
    last_name VARCHAR(50) NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    subtotal DECIMAL(10, 2) NOT NULL,
    tax DECIMAL(10, 2) DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
    confirmation_method ENUM('sms', 'email') DEFAULT NULL,
    confirmation_token VARCHAR(64) DEFAULT NULL,
    confirmed_at TIMESTAMP NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_confirmation_token (confirmation_token),
    KEY idx_orders_phone_status (phone, status)
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);


-- Cart table - stores cart header information for each user
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_cart (user_id)  -- Each user can only have one active cart
);

-- Cart items table - stores individual items in the cart
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_product (cart_id, product_id),  -- Prevent duplicate products in same cart
    KEY idx_cart_items_product (product_id)
);

-- Insert default categories
INSERT INTO categories (name, slug, icon) VALUES
('Artisan Breads', 'breads', 'bi-basket'),
('Pastries', 'pastries', 'bi-egg-fried'),
('Cakes', 'cakes', 'bi-cake2'),
('Cookies & Treats', 'cookies', 'bi-cookie')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    icon = VALUES(icon);

-- Insert sample products
-- Prices in Philippine Peso (₱) - Updated based on market research from local bakeries
INSERT INTO products (category_id, name, slug, description, price, image, featured) VALUES
(1, 'Sourdough Loaf', 'sourdough-loaf', 'Traditional sourdough with a crispy crust and chewy interior. Fermented for 24 hours.', 299.00, 'sourdough.jpg', TRUE),
(2, 'Butter Croissant', 'butter-croissant', 'Flaky, buttery layers made with authentic French technique.', 85.00, 'croissant.jpg', TRUE),
(3, 'Red Velvet Cake', 'red-velvet-cake', 'Classic red velvet with cream cheese frosting. Serves 8-10.', 1350.00, 'red-velvet.jpg', TRUE),
(4, 'Chocolate Chip Cookies', 'chocolate-chip-cookies', 'Chewy cookies loaded with premium dark chocolate chips.', 85.00, 'chocolate-chip.jpg', TRUE),
(1, 'Baguette', 'baguette', 'Classic French baguette with a golden crust.', 145.00, 'baguette.jpg', FALSE),
(2, 'Pain au Chocolat', 'pain-au-chocolat', 'Buttery pastry with rich dark chocolate bars inside.', 145.00, 'pain-chocolat.jpg', TRUE),
(3, 'Carrot Cake', 'carrot-cake', 'Moist carrot cake with cream cheese frosting and walnuts.', 1250.00, 'carrot-cake.jpg', FALSE),
(4, 'Macarons Box', 'macarons-box', 'Assorted French macarons in various flavors. Box of 12.', 950.00, 'macarons.jpg', TRUE),
(1, 'Ciabatta', 'ciabatta', 'Italian bread with large air pockets and crispy crust.', 120.00, 'ciabatta.jpg', FALSE),
(2, 'Danish Pastry', 'danish-pastry', 'Fruit-topped danish with vanilla custard.', 140.00, 'danish.jpg', FALSE),
(3, 'Chocolate Truffle Cake', 'chocolate-truffle-cake', 'Decadent chocolate cake with ganache. Serves 10-12.', 1700.00, 'truffle-cake.jpg', TRUE),
(4, 'Oatmeal Raisin Cookies', 'oatmeal-raisin-cookies', 'Wholesome oatmeal cookies with plump raisins.', 75.00, 'oatmeal-raisin.jpg', FALSE)
ON DUPLICATE KEY UPDATE
    category_id = VALUES(category_id),
    name = VALUES(name),
    description = VALUES(description),
    price = VALUES(price),
    image = VALUES(image),
    featured = VALUES(featured);

-- PayPal transactions table - logs all PayPal API interactions
CREATE TABLE IF NOT EXISTS paypal_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    paypal_order_id VARCHAR(100),
    paypal_capture_id VARCHAR(100),
    paypal_payer_id VARCHAR(100),
    amount DECIMAL(10, 2),
    currency VARCHAR(10) DEFAULT 'PHP',
    transaction_type ENUM('create_order', 'capture', 'refund', 'webhook') NOT NULL DEFAULT 'capture',
    status VARCHAR(50),
    request_data JSON,
    response_data JSON,
    raw_response TEXT,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    KEY idx_order_id (order_id)
);

-- SMS log table - tracks all SMS messages sent/received
CREATE TABLE IF NOT EXISTS sms_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    direction ENUM('outbound', 'inbound') NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'delivered', 'failed', 'received') DEFAULT 'pending',
    gateway_response TEXT,
    order_id INT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_phone (phone_number),
    INDEX idx_direction (direction),
    INDEX idx_status (status)
);

-- SMS OTP table - stores one-time passwords for verification
CREATE TABLE IF NOT EXISTS sms_otp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    purpose ENUM('order_verify', 'phone_verify', 'login', 'registration', 'other') DEFAULT 'other',
    reference_id INT,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone_otp (phone_number, otp_code),
    INDEX idx_expires (expires_at)
);
