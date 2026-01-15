# Bake & Take 🥖🧁

**Bake & Take** is a full-featured e-commerce web application designed for a bakery business. Built with **PHP** and **MySQL**, it provides a seamless ordering experience for customers and a robust management system for administrators.

### 🌐 **Live Site:** [bakeandtake.xyz](https://bakeandtake.xyz)

> **Note:** This project was developed as a final requirement for the Bachelor of Science in Information Technology (BSIT) program (Year 2026).

---

## 🚀 Features

### 🛒 Customer Side
* **User Authentication**: Secure registration and login system.
* **Product Catalog**: Browse bakery items by category (Bread, Pastries, Cakes, etc.).
* **Shopping Cart**: Add items, update quantities, and manage the cart.
* **Checkout System**: Secure checkout with order summary.
* **Payment Integration**:
    * **PayPal**: Real-time payment processing.
    * **Cash on Delivery (COD)**.
* **Order Tracking**: View order status and history.
* **Profile Management**: Update personal details and password.

### 🛡️ Admin Panel
* **Dashboard**: Overview of sales, recent orders, and total users.
* **Product Management**: Add, edit, and delete products with image uploads.
* **Category Management**: Organize products into categories.
* **Order Management**:
    * View order details.
    * Update statuses (Pending, Confirmed, Preparing, Ready, Completed, Cancelled).
    * Printable invoices/receipts.
* **User Management**: View and manage registered customers.
* **Database Backup**: Built-in tool to backup and restore the database.

### 🔔 Notifications
* **SMS Integration**: Automated SMS updates for order confirmations and status changes (via SMSGate & Android).
* **Email Notifications**: Transactional emails using PHPMailer.

---

## 🛠️ Tech Stack

* **Backend**: PHP (Native)
* **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
* **Database**: MySQL
* **Libraries & APIs**:
    * [PHPMailer](https://github.com/PHPMailer/PHPMailer) (Email)
    * PayPal Checkout SDK
    * SMSGate (Android Gateway)

---

## ⚙️ Installation & Setup (Local Development)

### 1. Prerequisites
* A local server environment like **XAMPP**, **WAMP**, or **MAMP**.
* PHP 7.4 or higher.
* MySQL/MariaDB.

### 2. Database Setup
1.  Open **phpMyAdmin** (usually `http://localhost/phpmyadmin`).
2.  Create a new database named `bake_and_take`.
3.  Import the schema and initial data:
    * Import `database/schema.sql` (Main structure).
    * Import `database/migration_*.sql` files (e.g., `migration_add_sms.sql`, `paypal_migration.sql`).
4.  *(Optional)* Run `database/update_prices_php.sql` to seed current product prices.

### 3. Configuration
1.  **Database Connection**:
    * Open `includes/connect.php`.
    * Ensure the credentials match your local setup:
        ```php
        $dbhost = "localhost";
        $dbuser = "root";
        $dbpass = ""; // Your MySQL password
        $db = "bake_and_take";
        ```

2.  **SMS Configuration** (Optional):
    * Copy `includes/sms_config.example.php` to `includes/sms_config.php`.
    * Configure your SMS Gateway URL and credentials as per `docs/SMS_INTEGRATION.md`.

3.  **Email Configuration**:
    * Configure SMTP settings in `includes/mailer.php` (or wherever PHPMailer is initialized) with your email provider credentials.

### 4. Running the Project
1.  Move the project folder to your server's root directory (e.g., `htdocs` or `www`).
2.  Access the website:
    * **Local Storefront**: `http://localhost/bake_and_take/`
    * **Local Admin Panel**: `http://localhost/bake_and_take/admin/`
    * **Live Site**: [https://bakeandtake.xyz](https://bakeandtake.xyz)

---

## 👥 The Team

* **Jandron Gian Ramos** - Developer
* **Benedict Orio** - Developer
* **Paulchristian Dimaculangan** - Developer
* **Diannedra Halili** - Developer
* **Angelene Castillo** - Developer

---

## 📄 License & Disclaimer

This project is for **educational purposes only**. It does not represent a real commercial entity. No real money or products are exchanged.

&copy; 2026 Bake & Take. All rights reserved.