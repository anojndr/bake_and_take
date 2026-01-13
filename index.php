<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$allowedPages = ['home', 'menu', 'about', 'contact', 'cart', 'checkout', 'login', 'register', 'order-success', 'order-confirmed', 'email-confirmed', 'orders', 'privacy-policy', 'terms-of-service', 'verify-phone'];

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
    <!-- Chatbot CSS -->
    <link href="assets/css/chatbot.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
    <!-- Navigation (Desktop) -->
    <nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-cake2 me-2"></i>Bake & Take
            </a>
            
            <!-- Desktop Nav Actions (hidden on mobile) -->
            <div class="nav-actions d-none d-lg-flex align-items-center order-lg-2">
                <a href="index.php?page=cart" class="btn btn-cart me-2" id="cartBtn">
                    <i class="bi bi-cart3"></i>
                    <?php $cartCount = getCartItemCount(); ?>
                    <span class="cart-count" id="cartCount" style="display: <?php echo $cartCount > 0 ? 'flex' : 'none'; ?>"><?php echo $cartCount; ?></span>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="btn btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <i class="bi bi-chevron-down ms-1" style="font-size: 0.8rem;"></i>
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
            
            <!-- Desktop nav collapse (hidden on mobile) -->
            <div class="collapse navbar-collapse d-lg-flex" id="navbarNav">
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
            </div>
        </div>
    </nav>
    
    <!-- Mobile Bottom Navigation Bar -->
    <nav class="mobile-bottom-nav d-lg-none" id="mobileBottomNav">
        <a href="index.php" class="mobile-nav-item <?php echo $page === 'home' ? 'active' : ''; ?>">
            <i class="bi bi-house"></i>
            <span>Home</span>
        </a>
        <a href="index.php?page=menu" class="mobile-nav-item <?php echo $page === 'menu' ? 'active' : ''; ?>">
            <i class="bi bi-grid"></i>
            <span>Menu</span>
        </a>
        <a href="index.php?page=cart" class="mobile-nav-item <?php echo $page === 'cart' ? 'active' : ''; ?>">
            <i class="bi bi-cart3"></i>
            <span>Cart</span>
            <?php if ($cartCount > 0): ?>
            <span class="mobile-cart-badge"><?php echo $cartCount; ?></span>
            <?php endif; ?>
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
        <a href="index.php?page=orders" class="mobile-nav-item <?php echo $page === 'orders' ? 'active' : ''; ?>">
            <i class="bi bi-person"></i>
            <span>Account</span>
        </a>
        <?php else: ?>
        <a href="index.php?page=login" class="mobile-nav-item <?php echo $page === 'login' ? 'active' : ''; ?>">
            <i class="bi bi-person"></i>
            <span>Sign In</span>
        </a>
        <?php endif; ?>
        <button class="mobile-nav-item" id="mobileMoreBtn" type="button">
            <i class="bi bi-three-dots"></i>
            <span>More</span>
        </button>
    </nav>
    
    <!-- Mobile More Menu Overlay -->
    <div class="mobile-more-menu" id="mobileMoreMenu">
        <div class="mobile-more-content">
            <div class="mobile-more-header">
                <h5>More</h5>
                <button class="mobile-more-close" id="mobileMoreClose">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="mobile-more-links">
                <a href="index.php?page=about" class="mobile-more-item <?php echo $page === 'about' ? 'active' : ''; ?>">
                    <i class="bi bi-info-circle"></i>
                    <span>About Us</span>
                </a>
                <a href="index.php?page=contact" class="mobile-more-item <?php echo $page === 'contact' ? 'active' : ''; ?>">
                    <i class="bi bi-envelope"></i>
                    <span>Contact</span>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php?page=orders" class="mobile-more-item">
                    <i class="bi bi-bag"></i>
                    <span>My Orders</span>
                </a>
                <a href="includes/logout.php" class="mobile-more-item">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main>
        <?php include "pages/{$page}.php"; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <!-- Main Footer Content -->
            <div class="row g-4 g-lg-5">
                <!-- Brand Section -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand">
                        <h5>About Us</h5>
                        <p>Experience the art of artisan baking. From crusty sourdoughs to delicate pastries, every bite tells a story of passion and tradition.</p>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php?page=menu">Menu</a></li>
                        <li><a href="index.php?page=about">About Us</a></li>
                        <li><a href="index.php?page=contact">Contact</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="index.php?page=orders">My Orders</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-3 col-md-6">
                    <h5>Contact Us</h5>
                    <ul class="contact-info">
                        <li>
                            <i class="bi bi-geo-alt me-2"></i>
                            <span>Polytechnic University of the Philippines<br>Sto. Tomas, Batangas, Philippines</span>
                        </li>
						<li>
                            <i class="bi bi-telephone me-2"></i>
                            <span>09763197468</span>
                        </li>
                        <li>
                            <i class="bi bi-envelope me-2"></i>
                            <span>anojndr@gmail.com</span>
                        </li>
                    </ul>
                </div>

                <!-- Store Hours -->
                <div class="col-lg-3 col-md-6">
                    <h5>Store Hours</h5>
                    <ul class="footer-links store-hours">
                        <li><span class="day">Monday - Saturday</span><br><span class="time">7:00 AM - 8:00 PM</span></li>
                        <li><span class="day">Sunday</span><br><span class="time">8:00 AM - 6:00 PM</span></li>
                    </ul>
                </div>
            </div>

            <!-- Footer Divider -->
            <hr class="footer-divider">

            <!-- Bottom Bar -->
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright">&copy; 2026 Bake & Take. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="index.php?page=privacy-policy" class="footer-link me-3" target="_blank" rel="noopener noreferrer">Privacy Policy</a>
                    <a href="index.php?page=terms-of-service" class="footer-link" target="_blank" rel="noopener noreferrer">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    <!-- Chatbot JS -->
    <script src="assets/js/chatbot.js?v=<?php echo time(); ?>"></script>
</body>
</html>
