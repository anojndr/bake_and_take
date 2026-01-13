<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Fetch user data from database
$user = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, email, phone, is_verified, 
                   email_verified, phone_verified, created_at, updated_at,
                   pending_email, pending_phone, pending_email_token, pending_phone_otp,
                   pending_email_expires, pending_phone_expires,
                   email_change_step, phone_change_step, phone_recovery_token
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        // Handle error silently
    }
}

if (!$user) {
    header('Location: index.php?page=login');
    exit;
}

$flash = getFlashMessage();
?>

<!-- Page Header -->
<header class="page-header">
    <div class="container">
        <h1>My Profile</h1>
        <div class="breadcrumb-custom">
            <a href="index.php">Home</a>
            <span>/</span>
            <span>Profile</span>
        </div>
    </div>
</header>

<!-- Profile Section -->
<section class="section profile-section">
    <div class="container">
        <div class="profile-container">
            <?php if ($flash): ?>
            <div class="alert-custom alert-<?php echo $flash['type']; ?>">
                <i class="bi bi-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'info' ? 'info-circle' : 'exclamation-circle'); ?>"></i>
                <?php echo $flash['message']; ?>
            </div>
            <?php endif; ?>
            
            <!-- Profile Header Card -->
            <div class="profile-header-card">
                <div class="profile-avatar">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div class="profile-header-info">
                    <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="profile-badges">
                        <?php if ($user['email_verified']): ?>
                        <span class="badge badge-verified"><i class="bi bi-envelope-check me-1"></i>Email Verified</span>
                        <?php else: ?>
                        <span class="badge badge-unverified"><i class="bi bi-envelope-exclamation me-1"></i>Email Not Verified</span>
                        <?php endif; ?>
                        
                        <?php if ($user['phone_verified']): ?>
                        <span class="badge badge-verified"><i class="bi bi-phone-vibrate me-1"></i>Phone Verified</span>
                        <?php else: ?>
                        <span class="badge badge-unverified"><i class="bi bi-phone me-1"></i>Phone Not Verified</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="profile-member-since">
                    <i class="bi bi-calendar3"></i>
                    <span>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
            
            <!-- Profile Settings -->
            <div class="profile-content-grid">
                <!-- Personal Information Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3><i class="bi bi-person me-2"></i>Personal Information</h3>
                    </div>
                    <div class="profile-card-body">
                        <form action="includes/process_profile.php" method="POST" id="personalInfoForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="update_name">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control form-control-custom" name="first_name" 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control form-control-custom" name="last_name" 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group mt-3">
                                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-lock input-icon"></i>
                                    <input type="password" class="form-control form-control-custom with-icon" 
                                           name="current_password" placeholder="Enter your password to confirm" required>
                                </div>
                                <small class="form-text text-muted">Required to save changes to your name.</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary-profile mt-4">
                                <i class="bi bi-check-lg me-2"></i>Save Changes
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Email Settings Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3><i class="bi bi-envelope me-2"></i>Email Address</h3>
                        <?php if ($user['email_verified']): ?>
                        <span class="status-badge status-verified"><i class="bi bi-check-circle me-1"></i>Verified</span>
                        <?php endif; ?>
                    </div>
                    <div class="profile-card-body">
                        <div class="current-value-display">
                            <span class="value-label">Current Email</span>
                            <span class="value-text"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        
                        <?php if ($user['pending_email'] && strtotime($user['pending_email_expires']) > time()): ?>
                            <?php if (($user['email_change_step'] ?? '') === 'verify_old'): ?>
                            <div class="pending-change-alert alert-step1">
                                <i class="bi bi-shield-lock"></i>
                                <div>
                                    <strong>Step 1: Verify Your Current Email</strong>
                                    <p>We sent a 6-digit code to your current email: <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
                                    <p class="small text-muted mt-1">New email pending: <?php echo htmlspecialchars($user['pending_email']); ?></p>
                                    <small>Expires: <?php echo date('M j, Y g:i A', strtotime($user['pending_email_expires'])); ?></small>
                                </div>
                            </div>

                            <form action="includes/process_profile.php" method="POST" class="otp-form mt-3">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="verify_old_email_code">
                                <div class="form-group">
                                    <label class="form-label">Enter Code from Current Email</label>
                                    <div class="otp-input-group">
                                        <input type="text" class="form-control form-control-custom otp-input" 
                                               name="otp_code" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required>
                                        <button type="submit" class="btn btn-primary-profile">
                                            <i class="bi bi-arrow-right"></i> Next
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <form action="includes/process_profile.php" method="POST" class="mt-2">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="cancel_email_change">
                                <button type="submit" class="btn btn-text-link"><i class="bi bi-x-circle me-1"></i>Cancel email change</button>
                            </form>
                            <?php else: ?>
                            <div class="pending-change-alert">
                                <i class="bi bi-hourglass-split"></i>
                                <div>
                                    <strong>Pending Email Change</strong>
                                    <p>Verification link sent to: <?php echo htmlspecialchars($user['pending_email']); ?></p>
                                    <p class="small text-muted mt-1"><i class="bi bi-info-circle me-1"></i>Please verify your new email to complete the change.</p>
                                    <small>Expires: <?php echo date('M j, Y g:i A', strtotime($user['pending_email_expires'])); ?></small>
                                </div>
                            </div>

                            <form action="includes/process_profile.php" method="POST" class="mt-2">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="cancel_email_change">
                                <button type="submit" class="btn btn-text-link"><i class="bi bi-x-circle me-1"></i>Cancel email change</button>
                            </form>
                            <?php endif; ?>
                        <?php else: ?>
                        <form action="includes/process_profile.php" method="POST" id="emailChangeForm" class="change-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="request_email_change">
                            
                            <div class="security-notice mb-3">
                                <i class="bi bi-shield-lock"></i>
                                <span>For security, please confirm your password to change your email address.</span>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-lock input-icon"></i>
                                    <input type="password" class="form-control form-control-custom with-icon" 
                                           name="current_password" placeholder="Enter your password" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">New Email Address <span class="text-danger">*</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-envelope input-icon"></i>
                                    <input type="email" class="form-control form-control-custom with-icon" 
                                           name="new_email" placeholder="Enter new email address" required>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>A 6-digit code will be sent to your current email first.
                                    After entering it, we will send a verification link to your new email.
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-outline-profile">
                                <i class="bi bi-envelope-arrow-up me-2"></i>Request Email Change
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Phone Settings Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3><i class="bi bi-phone me-2"></i>Phone Number</h3>
                        <?php if ($user['phone_verified']): ?>
                        <span class="status-badge status-verified"><i class="bi bi-check-circle me-1"></i>Verified</span>
                        <?php endif; ?>
                    </div>
                    <div class="profile-card-body">
                        <div class="current-value-display">
                            <span class="value-label">Current Phone</span>
                            <span class="value-text"><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></span>
                        </div>
                        
                        <?php 
                        // Determine the current step in phone change process
                        $phoneChangeStep = $user['phone_change_step'] ?? 'none';
                        $hasPendingChange = $user['pending_phone'] && strtotime($user['pending_phone_expires']) > time();
                        ?>
                        
                        <?php if ($hasPendingChange && $phoneChangeStep === 'verify_old'): ?>
                        <!-- Step 1: Verify OTP sent to OLD phone -->
                        <div class="step-indicator">
                            <div class="step active">
                                <span class="step-num">1</span>
                                <span class="step-label">Verify Current</span>
                            </div>
                            <div class="step-line"></div>
                            <div class="step">
                                <span class="step-num">2</span>
                                <span class="step-label">Verify New</span>
                            </div>
                        </div>
                        
                        <div class="pending-change-alert alert-step1">
                            <i class="bi bi-shield-lock"></i>
                            <div>
                                <strong>Step 1: Confirm Your Identity</strong>
                                <p>We sent an OTP to your current phone: <strong><?php echo htmlspecialchars($user['phone']); ?></strong></p>
                                <p class="small text-muted">New number pending: <?php echo htmlspecialchars($user['pending_phone']); ?></p>
                                <small>Expires: <?php echo date('M j, Y g:i A', strtotime($user['pending_phone_expires'])); ?></small>
                            </div>
                        </div>
                        
                        <form action="includes/process_profile.php" method="POST" class="otp-form mt-3">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="verify_old_phone_otp">
                            
                            <div class="form-group">
                                <label class="form-label">Enter OTP from Current Phone</label>
                                <div class="otp-input-group">
                                    <input type="text" class="form-control form-control-custom otp-input" 
                                           name="otp_code" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required>
                                    <button type="submit" class="btn btn-primary-profile">
                                        <i class="bi bi-arrow-right"></i> Next
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div class="recovery-option mt-3">
                            <p class="text-muted small mb-2"><i class="bi bi-question-circle me-1"></i>Can't access your current phone?</p>
                            <form action="includes/process_profile.php" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="phone_change_email_recovery">
                                <button type="submit" class="btn btn-text-link"><i class="bi bi-envelope me-1"></i>Verify via Email instead</button>
                            </form>
                        </div>
                        
                        <form action="includes/process_profile.php" method="POST" class="mt-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="cancel_phone_change">
                            <button type="submit" class="btn btn-text-link text-danger"><i class="bi bi-x-circle me-1"></i>Cancel phone change</button>
                        </form>
                        
                        <?php elseif ($hasPendingChange && $phoneChangeStep === 'verify_new'): ?>
                        <!-- Step 2: Verify OTP sent to NEW phone -->
                        <div class="step-indicator">
                            <div class="step completed">
                                <span class="step-num"><i class="bi bi-check"></i></span>
                                <span class="step-label">Verified</span>
                            </div>
                            <div class="step-line active"></div>
                            <div class="step active">
                                <span class="step-num">2</span>
                                <span class="step-label">Verify New</span>
                            </div>
                        </div>
                        
                        <div class="pending-change-alert alert-step2">
                            <i class="bi bi-phone-vibrate"></i>
                            <div>
                                <strong>Step 2: Verify New Phone Number</strong>
                                <p>We sent an OTP to your new phone: <strong><?php echo htmlspecialchars($user['pending_phone']); ?></strong></p>
                                <small>Expires: <?php echo date('M j, Y g:i A', strtotime($user['pending_phone_expires'])); ?></small>
                            </div>
                        </div>
                        
                        <form action="includes/process_profile.php" method="POST" class="otp-form mt-3">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="verify_new_phone_otp">
                            
                            <div class="form-group">
                                <label class="form-label">Enter OTP from New Phone</label>
                                <div class="otp-input-group">
                                    <input type="text" class="form-control form-control-custom otp-input" 
                                           name="otp_code" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required>
                                    <button type="submit" class="btn btn-primary-profile">
                                        <i class="bi bi-check-lg"></i> Complete
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <form action="includes/process_profile.php" method="POST" class="mt-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="resend_new_phone_otp">
                            <button type="submit" class="btn btn-text-link"><i class="bi bi-arrow-repeat me-1"></i>Resend OTP to new phone</button>
                        </form>
                        
                        <form action="includes/process_profile.php" method="POST" class="mt-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="cancel_phone_change">
                            <button type="submit" class="btn btn-text-link text-danger"><i class="bi bi-x-circle me-1"></i>Cancel phone change</button>
                        </form>
                        
                        <?php elseif ($hasPendingChange && $phoneChangeStep === 'email_recovery'): ?>
                        <!-- Email Recovery: Verify via email link -->
                        <div class="pending-change-alert alert-recovery">
                            <i class="bi bi-envelope-check"></i>
                            <div>
                                <strong>Email Verification Requested</strong>
                                <p>We sent a verification link to: <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
                                <p class="small text-muted">New number pending: <?php echo htmlspecialchars($user['pending_phone']); ?></p>
                                <small>Please check your email and click the verification link to proceed.</small>
                            </div>
                        </div>
                        
                        <form action="includes/process_profile.php" method="POST" class="mt-3">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="resend_phone_recovery_email">
                            <button type="submit" class="btn btn-outline-profile"><i class="bi bi-envelope me-1"></i>Resend Email</button>
                        </form>
                        
                        <form action="includes/process_profile.php" method="POST" class="mt-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="cancel_phone_change">
                            <button type="submit" class="btn btn-text-link text-danger"><i class="bi bi-x-circle me-1"></i>Cancel phone change</button>
                        </form>
                        
                        <?php else: ?>
                        <!-- Initial form: Start phone change process -->
                        <form action="includes/process_profile.php" method="POST" id="phoneChangeForm" class="change-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="request_phone_change">
                            
                            <div class="security-notice mb-3">
                                <i class="bi bi-shield-lock"></i>
                                <span>For security, changing your phone requires verification from both your current and new phone.</span>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-lock input-icon"></i>
                                    <input type="password" class="form-control form-control-custom with-icon" 
                                           name="current_password" placeholder="Enter your password" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">New Phone Number <span class="text-danger">*</span></label>
                                <div class="phone-input-wrapper">
                                    <span class="phone-prefix">+63</span>
                                    <input type="tel" class="form-control form-control-custom phone-input" 
                                           name="new_phone" placeholder="9XX XXX XXXX" maxlength="10" pattern="[0-9]{10}" required>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>OTP verification will be required for both your current and new phone.
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-outline-profile mt-3">
                                <i class="bi bi-phone-vibrate me-2"></i>Start Phone Change
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Password Change Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3><i class="bi bi-shield-lock me-2"></i>Change Password</h3>
                    </div>
                    <div class="profile-card-body">
                        <form action="includes/process_profile.php" method="POST" id="passwordChangeForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Current Password</label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-lock input-icon"></i>
                                    <input type="password" class="form-control form-control-custom with-icon" 
                                           name="current_password" placeholder="Enter current password" required>
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">New Password</label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-lock-fill input-icon"></i>
                                    <input type="password" class="form-control form-control-custom with-icon" 
                                           name="new_password" placeholder="Min 8 characters" required minlength="8">
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-lock-fill input-icon"></i>
                                    <input type="password" class="form-control form-control-custom with-icon" 
                                           name="confirm_password" placeholder="Repeat new password" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary-profile">
                                <i class="bi bi-shield-check me-2"></i>Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="profile-quick-actions">
                <a href="index.php?page=orders" class="quick-action-btn">
                    <i class="bi bi-bag"></i>
                    <span>My Orders</span>
                </a>
                <a href="index.php?page=cart" class="quick-action-btn">
                    <i class="bi bi-cart3"></i>
                    <span>Shopping Cart</span>
                </a>
                <a href="includes/logout.php" class="quick-action-btn quick-action-danger">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</section>

<style>
.profile-section {
    background: var(--cream);
    min-height: 60vh;
}

.profile-container {
    max-width: 900px;
    margin: 0 auto;
}

/* Alert Styles */
.alert-custom {
    padding: 1rem 1.25rem;
    border-radius: var(--radius-md);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.95rem;
}

.alert-custom i {
    font-size: 1.1rem;
    flex-shrink: 0;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.alert-success {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}

.alert-info {
    background: #dbeafe;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

/* Profile Header Card */
.profile-header-card {
    background: linear-gradient(135deg, var(--cream) 0%, var(--accent) 100%);
    border-radius: var(--radius-xl);
    padding: 2.5rem;
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-md);
    flex-wrap: wrap;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    background: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-lg);
    flex-shrink: 0;
}

.profile-avatar i {
    font-size: 4rem;
    color: var(--primary-dark);
}

.profile-header-info {
    flex: 1;
    min-width: 250px;
}

.profile-header-info h2 {
    font-family: var(--font-display);
    color: var(--dark);
    margin-bottom: 0.25rem;
}

.profile-email {
    color: var(--text-secondary);
    margin-bottom: 0.75rem;
}

.profile-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.badge {
    padding: 0.4rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
}

.badge-verified {
    background: rgba(22, 163, 74, 0.15);
    color: #16a34a;
}

.badge-unverified {
    background: rgba(217, 119, 6, 0.15);
    color: #d97706;
}

.profile-member-since {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-secondary);
    font-size: 0.9rem;
    background: var(--white);
    padding: 0.75rem 1.25rem;
    border-radius: var(--radius-md);
}

/* Profile Content Grid */
.profile-content-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.profile-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.profile-card:hover {
    box-shadow: var(--shadow-md);
}

.profile-card-header {
    background: linear-gradient(135deg, var(--cream) 0%, rgba(212, 165, 116, 0.1) 100%);
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--cream-dark);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.profile-card-header h3 {
    font-size: 1.1rem;
    color: var(--dark);
    margin: 0;
    font-family: var(--font-display);
}

.profile-card-body {
    padding: 1.5rem;
}

/* Status Badge */
.status-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-verified {
    background: rgba(22, 163, 74, 0.15);
    color: #16a34a;
}

/* Current Value Display */
.current-value-display {
    background: var(--cream);
    padding: 1rem 1.25rem;
    border-radius: var(--radius-md);
    margin-bottom: 1rem;
}

.value-label {
    display: block;
    font-size: 0.75rem;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
}

.value-text {
    font-weight: 500;
    color: var(--dark);
    font-size: 1.05rem;
}

/* Pending Change Alert */
.pending-change-alert {
    background: rgba(13, 110, 253, 0.1);
    border: 1px solid rgba(13, 110, 253, 0.2);
    padding: 1rem 1.25rem;
    border-radius: var(--radius-md);
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.pending-change-alert i {
    font-size: 1.5rem;
    color: #0d6efd;
    flex-shrink: 0;
    margin-top: 0.25rem;
}

.pending-change-alert strong {
    display: block;
    color: #084298;
    margin-bottom: 0.25rem;
}

.pending-change-alert p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.pending-change-alert small {
    color: var(--text-light);
}

/* Security Notice */
.security-notice {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.08) 0%, rgba(13, 110, 253, 0.04) 100%);
    border: 1px solid rgba(13, 110, 253, 0.2);
    border-radius: var(--radius-md);
    padding: 0.875rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
    color: #084298;
}

.security-notice i {
    font-size: 1.25rem;
    color: #0d6efd;
    flex-shrink: 0;
}

/* Step Indicator */
.step-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    padding: 1rem 0;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
}

.step-num {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--cream);
    border: 2px solid var(--cream-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-light);
    transition: all 0.3s ease;
}

.step.active .step-num {
    background: var(--primary);
    border-color: var(--primary);
    color: var(--white);
}

.step.completed .step-num {
    background: #16a34a;
    border-color: #16a34a;
    color: var(--white);
}

.step-label {
    font-size: 0.75rem;
    color: var(--text-light);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.step.active .step-label,
.step.completed .step-label {
    color: var(--dark);
}

.step-line {
    width: 60px;
    height: 3px;
    background: var(--cream-dark);
    margin: 0 0.75rem;
    margin-bottom: 1.5rem;
    border-radius: 2px;
    transition: all 0.3s ease;
}

.step-line.active {
    background: linear-gradient(90deg, #16a34a 0%, var(--primary) 100%);
}

/* Alert Variants for Phone Change Steps */
.alert-step1 {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(13, 110, 253, 0.05) 100%);
    border-color: rgba(13, 110, 253, 0.2);
}

.alert-step1 i {
    color: #0d6efd;
}

.alert-step2 {
    background: linear-gradient(135deg, rgba(22, 163, 74, 0.1) 0%, rgba(22, 163, 74, 0.05) 100%);
    border-color: rgba(22, 163, 74, 0.2);
}

.alert-step2 i {
    color: #16a34a;
}

.alert-recovery {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
    border-color: rgba(139, 92, 246, 0.2);
}

.alert-recovery i {
    color: #8b5cf6;
}

.recovery-option {
    padding: 0.75rem;
    background: rgba(249, 250, 251, 0.8);
    border-radius: var(--radius-md);
    border: 1px dashed var(--cream-dark);
}


/* Form Styles */
.change-form {
    margin-top: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.input-icon-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
}

.form-control-custom.with-icon {
    padding-left: 2.75rem;
}

.form-control-custom {
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-md);
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
}

.form-control-custom:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.15);
    outline: none;
}

.form-text {
    font-size: 0.8rem;
    margin-top: 0.5rem;
}

/* Phone Input */
.phone-input-wrapper {
    display: flex;
    align-items: center;
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-md);
    overflow: hidden;
    transition: all 0.2s ease;
    background: var(--white);
}

.phone-input-wrapper:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.15);
}

.phone-prefix {
    background: var(--cream);
    color: var(--text-secondary);
    padding: 0.75rem 1rem;
    font-weight: 600;
    font-size: 1rem;
    border-right: 2px solid var(--cream-dark);
    user-select: none;
}

.phone-input {
    border: none !important;
    border-radius: 0 !important;
    padding-left: 1rem !important;
    flex: 1;
}

.phone-input:focus {
    box-shadow: none !important;
    outline: none !important;
}

/* OTP Input */
.otp-input-group {
    display: flex;
    gap: 0.75rem;
}

.otp-input {
    flex: 1;
    text-align: center;
    font-size: 1.25rem;
    letter-spacing: 0.5rem;
    font-weight: 600;
}

/* Buttons */
.btn-primary-profile {
    background: var(--gradient-warm);
    color: var(--white);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-md);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
}

.btn-primary-profile:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-outline-profile {
    background: transparent;
    color: var(--primary-dark);
    border: 2px solid var(--primary);
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-md);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
}

.btn-outline-profile:hover {
    background: var(--primary);
    color: var(--white);
}

.btn-text-link {
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 0.85rem;
    cursor: pointer;
    text-decoration: underline;
    padding: 0;
}

.btn-text-link:hover {
    color: var(--primary-dark);
}

/* Quick Actions */
.profile-quick-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.25rem 2rem;
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    text-decoration: none;
    color: var(--text-primary);
    min-width: 120px;
}

.quick-action-btn i {
    font-size: 1.75rem;
    color: var(--primary-dark);
}

.quick-action-btn span {
    font-weight: 500;
    font-size: 0.9rem;
}

.quick-action-btn:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
    background: var(--accent);
}

.quick-action-danger:hover {
    background: #fee2e2;
}

.quick-action-danger:hover i {
    color: #dc2626;
}

/* Responsive */
@media (max-width: 991px) {
    .profile-content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767px) {
    .profile-header-card {
        flex-direction: column;
        text-align: center;
        padding: 2rem 1.5rem;
    }
    
    .profile-header-info {
        min-width: 100%;
    }
    
    .profile-badges {
        justify-content: center;
    }
    
    .profile-member-since {
        justify-content: center;
        width: 100%;
    }
    
    .profile-quick-actions {
        flex-direction: column;
    }
    
    .quick-action-btn {
        flex-direction: row;
        justify-content: flex-start;
        min-width: 100%;
    }
    
    .otp-input-group {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phone number input formatting
    const phoneInputs = document.querySelectorAll('.phone-input');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            this.value = value;
        });
        
        input.addEventListener('keypress', function(e) {
            if (!/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                e.preventDefault();
            }
        });
    });
    
    // OTP input formatting
    const otpInput = document.querySelector('.otp-input');
    if (otpInput) {
        otpInput.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value.length > 6) {
                value = value.slice(0, 6);
            }
            this.value = value;
        });
        
        otpInput.addEventListener('keypress', function(e) {
            if (!/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                e.preventDefault();
            }
        });
    }
    
    // Password confirmation validation
    const passwordForm = document.getElementById('passwordChangeForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = this.querySelector('input[name="new_password"]').value;
            const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });
    }
});
</script>
