<?php
$flash = getFlashMessage();

// Fetch user data if logged in for pre-filling the form
$userData = null;
if (isset($_SESSION['user_id']) && $conn) {
    $stmt = mysqli_prepare($conn, "SELECT first_name, last_name, email, phone FROM users WHERE user_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $userData = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}
?>

<!-- Page Header -->
<header class="page-header">
    <div class="container">
        <h1>Contact Us</h1>
        <div class="breadcrumb-custom">
            <a href="index.php">Home</a>
            <span>/</span>
            <span>Contact</span>
        </div>
    </div>
</header>

<!-- Contact Section -->
<section class="section contact-section">
    <div class="container">
        <?php if ($flash): ?>
        <div class="alert-custom alert-<?php echo $flash['type']; ?> mb-4">
            <i class="bi bi-check-circle"></i>
            <?php echo $flash['message']; ?>
        </div>
        <?php endif; ?>
        
        <div class="row g-5">
            <!-- Contact Info -->
            <div class="col-lg-5">
                <div class="contact-info-card">
                    <h3>Get in Touch</h3>
                    <p>Have a question about our products or want to place a custom order? We'd love to hear from you!</p>
                    
                    <div class="contact-info-list">
                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Visit Us</h5>
                                <p>Polytechnic University of the Philippines<br>Sto. Tomas, Batangas, Philippines</p>
                            </div>
                        </div>
                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Call Us</h5>
                                <p>09763197468</p>
                            </div>
                        </div>
                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Email Us</h5>
                                <p>anojndr@gmail.com</p>
                            </div>
                        </div>
                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Opening Hours</h5>
                                <p>Mon - Sat: 7:00 AM - 8:00 PM<br>Sunday: 8:00 AM - 6:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="contact-form-card">
                    <h3>Send us a Message</h3>
                    <form action="includes/process_contact.php" method="POST" id="contactForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="firstName">First Name *</label>
                                <input type="text" class="form-control form-control-custom" id="firstName" name="first_name" required value="<?php echo htmlspecialchars($userData['first_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="lastName">Last Name *</label>
                                <input type="text" class="form-control form-control-custom" id="lastName" name="last_name" required value="<?php echo htmlspecialchars($userData['last_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email Address *</label>
                                <input type="email" class="form-control form-control-custom" id="email" name="email" required value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="tel" class="form-control form-control-custom" id="phone" name="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="subject">Subject *</label>
                                <select class="form-control form-control-custom" id="subject" name="subject" required>
                                    <option value="">Select a subject</option>
                                    <option value="general">General Inquiry</option>
                                    <option value="order">Order Question</option>
                                    <option value="custom">Custom Order Request</option>
                                    <option value="catering">Catering Services</option>
                                    <option value="feedback">Feedback</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="message">Message *</label>
                                <textarea class="form-control form-control-custom" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-hero btn-hero-primary w-100">
                                    <i class="bi bi-send me-2"></i> Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section">
    <div class="container-fluid p-0">
        <div class="map-wrapper">
            <iframe 
                src="https://maps.google.com/maps?q=Polytechnic+University+of+the+Philippines+Santo+Tomas+Batangas&t=&z=16&ie=UTF8&iwloc=&output=embed" 
                width="100%" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>

<style>
.contact-section { background: var(--cream); }

.contact-info-card {
    background: var(--gradient-hero);
    color: var(--white);
    padding: 2.5rem;
    border-radius: var(--radius-xl);
    height: 100%;
}

.contact-info-card h3 {
    color: var(--white);
    margin-bottom: 1rem;
}

.contact-info-card > p {
    color: rgba(255,255,255,0.8);
    margin-bottom: 2rem;
}

.contact-info-list { margin-bottom: 2rem; }

.contact-info-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.contact-icon {
    width: 50px;
    height: 50px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.contact-details h5 {
    color: var(--primary-light);
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.contact-details p {
    margin: 0;
    line-height: 1.6;
    color: rgba(255,255,255,0.9);
}



.contact-form-card {
    background: var(--white);
    padding: 2.5rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
}

.contact-form-card h3 {
    color: var(--dark);
    margin-bottom: 1.5rem;
}

.map-wrapper {
    border-radius: 0;
    overflow: hidden;
}

.map-wrapper iframe {
    display: block;
}

@media (max-width: 991px) {
    .contact-info-card { margin-bottom: 2rem; }
}
</style>
