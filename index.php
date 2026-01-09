<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$allowedPages = ['home', 'menu', 'about', 'contact', 'cart', 'checkout', 'login', 'register', 'order-success', 'orders'];

if (!in_array($page, $allowedPages)) {
    $page = 'home';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bake & Take - Artisan bakery offering freshly baked goods, pastries, and custom cakes. Order online for pickup or delivery.">
    <meta name="keywords" content="bakery, pastries, cakes, bread, artisan, fresh baked">
    <title>Bake & Take - Artisan Bakery</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-cake2 me-2"></i>Bake & Take
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'home' ? 'active' : ''; ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'menu' ? 'active' : ''; ?>" href="index.php?page=menu">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'about' ? 'active' : ''; ?>" href="index.php?page=about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'contact' ? 'active' : ''; ?>" href="index.php?page=contact">Contact</a>
                    </li>
                </ul>
                <div class="nav-actions">
                    <a href="index.php?page=cart" class="btn btn-cart me-2" id="cartBtn">
                        <i class="bi bi-cart3"></i>
                        <?php $cartCount = getCartItemCount(); ?>
                        <span class="cart-count" id="cartCount" style="display: <?php echo $cartCount > 0 ? 'flex' : 'none'; ?>"><?php echo $cartCount; ?></span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="index.php?page=orders">My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="includes/logout.php">Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="index.php?page=login" class="btn btn-primary-custom">Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <?php include "pages/{$page}.php"; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand">
                        <h3><i class="bi bi-cake2 me-2"></i>Bake & Take</h3>
                        <p>Crafting happiness, one bite at a time. Fresh artisan baked goods made with love and the finest ingredients.</p>
                        <div class="social-links">
                            <a href="#"><i class="bi bi-facebook"></i></a>
                            <a href="#"><i class="bi bi-instagram"></i></a>
                            <a href="#"><i class="bi bi-twitter-x"></i></a>
                            <a href="#"><i class="bi bi-pinterest"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php?page=menu">Menu</a></li>
                        <li><a href="index.php?page=about">About Us</a></li>
                        <li><a href="index.php?page=contact">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h5>Categories</h5>
                    <ul class="footer-links">
                        <li><a href="index.php?page=menu&category=breads">Breads</a></li>
                        <li><a href="index.php?page=menu&category=pastries">Pastries</a></li>
                        <li><a href="index.php?page=menu&category=cakes">Cakes</a></li>
                        <li><a href="index.php?page=menu&category=cookies">Cookies</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h5>Contact Info</h5>
                    <ul class="contact-info">
                        <li><i class="bi bi-geo-alt me-2"></i>PUP Sto. Tomas, Batangas</li>
                        <li><i class="bi bi-telephone me-2"></i>(043) 123-4567</li>
                        <li><i class="bi bi-envelope me-2"></i>bakeandtake@pup.edu.ph</li>
                        <li><i class="bi bi-clock me-2"></i>Mon-Sat: 7AM - 8PM</li>
                    </ul>
                </div>
            </div>
            <hr class="footer-divider">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright">&copy; 2026 Bake & Take. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="footer-link me-3">Privacy Policy</a>
                    <a href="#" class="footer-link">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
