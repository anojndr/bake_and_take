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
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    zip VARCHAR(20),
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    order_number VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    delivery_method ENUM('delivery', 'pickup') DEFAULT 'delivery',
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    zip VARCHAR(20),
    instructions TEXT,
    subtotal DECIMAL(10, 2) NOT NULL,
    delivery_fee DECIMAL(10, 2) DEFAULT 0,
    tax DECIMAL(10, 2) DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    read_status BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Newsletter subscribers table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cart table - stores cart header information for each user
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NULL,  -- For guest users who may not be logged in
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
    price DECIMAL(10, 2) NOT NULL,  -- Price at the time of adding to cart
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_product (cart_id, product_id)  -- Prevent duplicate products in same cart
);

-- Insert default categories
INSERT INTO categories (name, slug, icon) VALUES
('Artisan Breads', 'breads', 'bi-basket'),
('Pastries', 'pastries', 'bi-egg-fried'),
('Cakes', 'cakes', 'bi-cake2'),
('Cookies & Treats', 'cookies', 'bi-cookie');

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
(4, 'Oatmeal Raisin Cookies', 'oatmeal-raisin-cookies', 'Wholesome oatmeal cookies with plump raisins.', 75.00, 'oatmeal-raisin.jpg', FALSE);
