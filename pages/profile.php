<?php
// User is already authenticated via index.php
// Fetch user data from database
$user = null;
if ($conn) {
    $stmt = mysqli_prepare($conn, "
        SELECT user_id, first_name, last_name, email, phone, is_verified, 
               email_verified, phone_verified, created_at, updated_at,
               pending_email, pending_phone, pending_email_token, pending_phone_otp,
               pending_email_expires, pending_phone_expires,
               email_change_step, phone_change_step, phone_recovery_token
        FROM users 
        WHERE user_id = ?
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}

if (!$user) {
    // User data not found - show error instead of redirect (can't redirect after HTML output)
    echo '<div class="container py-5"><div class="alert alert-danger">Unable to load profile. Please <a href="index.php?page=login">log in again</a>.</div></div>';
    return;
}

// Fetch recent orders (last 3)
$recentOrders = [];
if ($conn) {
    $stmt = mysqli_prepare($conn, "
        SELECT o.order_id, o.order_number, o.created_at, o.total, o.status,
               GROUP_CONCAT(CONCAT(p.name, ' x', oi.quantity) SEPARATOR ', ') as items_summary
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE o.user_id = ?
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
        LIMIT 3
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $recentOrders[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

// Determine which view to show
$editMode = isset($_GET['edit']) && $_GET['edit'] === 'profile';
$editEmailMode = isset($_GET['edit']) && $_GET['edit'] === 'email';
$editPhoneMode = isset($_GET['edit']) && $_GET['edit'] === 'phone';
$editPasswordMode = isset($_GET['edit']) && $_GET['edit'] === 'password';
$verifyPhoneMode = isset($_GET['action']) && $_GET['action'] === 'verify_phone';
$verifyEmailMode = isset($_GET['action']) && $_GET['action'] === 'verify_email';

$flash = getFlashMessage();

// For phone change page: suppress misleading "No pending phone verification" error 
// when user navigates back after successful change or when there's no pending change
if ($editPhoneMode && $flash && $flash['type'] === 'error' && 
    strpos($flash['message'], 'No pending phone verification') !== false) {
    // Only suppress if there's no actual pending phone change
    $hasPendingPhoneChange = $user['pending_phone'] && 
                             $user['pending_phone_expires'] && 
                             strtotime($user['pending_phone_expires']) > time();
    if (!$hasPendingPhoneChange) {
        $flash = null; // Clear the misleading error message
    }
}

// Helper function for email display (no masking - user's own data)
function maskEmail($email) {
    return $email;
}

// Helper function for phone display (no masking - user's own data)
function maskPhone($phone) {
    return $phone;
}
?>

<!-- Page Header -->
<header class="page-header">
    <div class="container">
        <h1><?php 
            if ($editMode) echo 'Edit Profile';
            elseif ($editEmailMode) echo 'Change Email';
            elseif ($editPhoneMode) echo 'Change Phone';
            elseif ($editPasswordMode) echo 'Change Password';
            else echo 'My Account';
        ?></h1>
        <div class="breadcrumb-custom">
            <a href="index.php">Home</a>
            <span>/</span>
            <a href="index.php?page=profile">Profile</a>
            <?php if ($editMode || $editEmailMode || $editPhoneMode || $editPasswordMode): ?>
            <span>/</span>
            <span><?php 
                if ($editMode) echo 'Edit Profile';
                elseif ($editEmailMode) echo 'Change Email';
                elseif ($editPhoneMode) echo 'Change Phone';
                elseif ($editPasswordMode) echo 'Change Password';
            ?></span>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Profile Section -->
<section class="lazada-profile-section">
    <div class="container">
        <div class="lazada-profile-layout">
            <!-- Sidebar Navigation -->
            <aside class="profile-sidebar">
                <div class="sidebar-user-header">
                    <div class="sidebar-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div class="sidebar-user-info">
                        <span class="sidebar-greeting">Hello, <?php echo htmlspecialchars($user['first_name']); ?></span>
                        <?php if ($user['email_verified'] && $user['phone_verified']): ?>
                        <span class="verified-badge"><i class="bi bi-patch-check-fill"></i> Verified Account</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <nav class="sidebar-nav">
                    <div class="nav-section">
                        <h4 class="nav-section-title">
                            <i class="bi bi-person-gear"></i>
                            Manage My Account
                        </h4>
                        <ul class="nav-links">
                            <li><a href="index.php?page=profile" class="<?php echo !$editMode && !$editEmailMode && !$editPhoneMode && !$editPasswordMode ? 'active' : ''; ?>">My Profile</a></li>
                            <li><a href="index.php?page=profile&edit=password" class="<?php echo $editPasswordMode ? 'active' : ''; ?>">Change Password</a></li>
                        </ul>
                    </div>
                    
                    <div class="nav-section">
                        <h4 class="nav-section-title">
                            <i class="bi bi-bag"></i>
                            My Orders
                        </h4>
                        <ul class="nav-links">
                            <li><a href="index.php?page=orders">Order History</a></li>
                        </ul>
                    </div>
                    
                    <div class="nav-section">
                        <a href="includes/logout.php" class="nav-logout">
                            <i class="bi bi-box-arrow-right"></i>
                            Logout
                        </a>
                    </div>
                </nav>
            </aside>
            
            <!-- Main Content Area -->
            <main class="profile-main-content">
                <?php if ($flash): ?>
                <div class="alert-lazada alert-<?php echo $flash['type']; ?>">
                    <i class="bi bi-<?php echo $flash['type'] === 'success' ? 'check-circle-fill' : ($flash['type'] === 'info' ? 'info-circle-fill' : 'exclamation-circle-fill'); ?>"></i>
                    <?php echo $flash['message']; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($editMode): ?>
                <!-- ============ EDIT PROFILE VIEW ============ -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>Edit Profile</h2>
                    </div>
                    <div class="content-card-body">
                        <form action="includes/process_profile.php" method="POST" class="edit-profile-form" id="editProfileForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-row three-cols">
                                <!-- Full Name -->
                                <div class="form-field">
                                    <label>Full Name</label>
                                    <div class="input-with-clear">
                                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" class="form-input">
                                        <button type="button" class="clear-btn" onclick="this.previousElementSibling.value=''"><i class="bi bi-x-circle"></i></button>
                                    </div>
                                </div>
                                
                                <!-- Email Address -->
                                <div class="form-field">
                                    <label>Email Address <a href="index.php?page=profile&edit=email" class="change-link">Change</a></label>
                                    <div class="static-value"><?php echo htmlspecialchars(maskEmail($user['email'])); ?></div>
                                </div>
                                
                                <!-- Mobile -->
                                <div class="form-field">
                                    <label>Mobile <a href="index.php?page=profile&edit=phone" class="change-link">Change</a></label>
                                    <div class="static-value"><?php echo htmlspecialchars($user['phone'] ? maskPhone($user['phone']) : 'Not set'); ?></div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-save-changes">SAVE CHANGES</button>
                                <a href="index.php?page=profile" class="btn-cancel">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php elseif ($editEmailMode): ?>
                <!-- ============ CHANGE EMAIL VIEW ============ -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>Change Email Address</h2>
                        <a href="index.php?page=profile" class="back-link"><i class="bi bi-arrow-left"></i> Back to Profile</a>
                    </div>
                    <div class="content-card-body">
                        <?php if ($user['pending_email'] && strtotime($user['pending_email_expires']) > time()): ?>
                            <?php if (($user['email_change_step'] ?? '') === 'verify_old'): ?>
                            <!-- Step 1: Verify current email -->
                            <div class="verification-alert info">
                                <i class="bi bi-shield-lock"></i>
                                <div>
                                    <strong>Step 1: Verify Your Current Email</strong>
                                    <p>We sent a 6-digit code to: <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
                                    <small>New email pending: <?php echo htmlspecialchars($user['pending_email']); ?></small>
                                </div>
                            </div>
                            
                            <form action="includes/process_profile.php" method="POST" class="otp-form">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="verify_old_email_code">
                                <input type="hidden" name="otp_code" id="otp_code_hidden" value="">
                                <div class="form-field">
                                    <label>Enter Code from Current Email</label>
                                    <div class="otp-input-group">
                                        <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="0" autocomplete="off">
                                        <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="1" autocomplete="off">
                                        <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="2" autocomplete="off">
                                        <span class="otp-separator">-</span>
                                        <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="3" autocomplete="off">
                                        <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="4" autocomplete="off">
                                        <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="5" autocomplete="off">
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-save-changes">VERIFY & CONTINUE</button>
                                </div>
                            </form>
                            
                            <form action="includes/process_profile.php" method="POST" class="mt-3">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="cancel_email_change">
                                <button type="submit" class="btn-text-link"><i class="bi bi-x-circle"></i> Cancel email change</button>
                            </form>
                            <?php else: ?>
                            <!-- Pending verification on new email -->
                            <div class="verification-alert pending">
                                <i class="bi bi-hourglass-split"></i>
                                <div>
                                    <strong>Pending Email Verification</strong>
                                    <p>We sent a verification link to: <strong><?php echo htmlspecialchars($user['pending_email']); ?></strong></p>
                                    <small>Please check your new email and click the verification link.</small>
                                </div>
                            </div>
                            
                            <form action="includes/process_profile.php" method="POST" class="mt-3">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="cancel_email_change">
                                <button type="submit" class="btn-text-link"><i class="bi bi-x-circle"></i> Cancel email change</button>
                            </form>
                            <?php endif; ?>
                        <?php else: ?>
                        <!-- Request new email change -->
                        <form action="includes/process_profile.php" method="POST" class="edit-profile-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="request_email_change">
                            
                            <div class="security-notice">
                                <i class="bi bi-shield-lock"></i>
                                <span>For security, please confirm your password to change your email address.</span>
                            </div>
                            
                            <div class="form-field">
                                <label>Current Password <span class="required">*</span></label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="current_password" id="email_change_password" class="form-input has-toggle" placeholder="Enter your password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('email_change_password')" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label>New Email Address <span class="required">*</span></label>
                                <input type="email" name="new_email" class="form-input" placeholder="Enter new email address" required>
                                <small class="form-hint">A verification code will be sent to your current email first.</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-save-changes">REQUEST EMAIL CHANGE</button>
                                <a href="index.php?page=profile" class="btn-cancel">Cancel</a>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php elseif ($editPhoneMode): ?>
                <!-- ============ CHANGE PHONE VIEW ============ -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>Change Phone Number</h2>
                        <a href="index.php?page=profile" class="back-link"><i class="bi bi-arrow-left"></i> Back to Profile</a>
                    </div>
                    <div class="content-card-body">
                        <?php 
                        $phoneChangeStep = $user['phone_change_step'] ?? 'none';
                        $hasPendingChange = $user['pending_phone'] && strtotime($user['pending_phone_expires']) > time();
                        ?>
                        
                        <?php if ($hasPendingChange && $phoneChangeStep === 'verify_old'): ?>
                        <!-- Step 1: Verify OTP on old phone -->
                        <div class="step-indicator">
                            <div class="step active"><span>1</span><label>Verify Current</label></div>
                            <div class="step-line"></div>
                            <div class="step"><span>2</span><label>Verify New</label></div>
                        </div>
                        
                        <div class="verification-alert info">
                            <i class="bi bi-shield-lock"></i>
                            <div>
                                <strong>Step 1: Confirm Your Identity</strong>
                                <p>We sent an OTP to your current phone: <strong><?php echo htmlspecialchars($user['phone']); ?></strong></p>
                                <small>New number pending: <?php echo htmlspecialchars($user['pending_phone']); ?></small>
                            </div>
                        </div>
                        
                        <form action="includes/process_profile.php" method="POST" class="otp-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="verify_old_phone_otp">
                            <input type="hidden" name="otp_code" id="otp_code_hidden" value="">
                            <div class="form-field">
                                <label>Enter OTP from Current Phone</label>
                                <div class="otp-input-group">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="0" autocomplete="off">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="1" autocomplete="off">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="2" autocomplete="off">
                                    <span class="otp-separator">-</span>
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="3" autocomplete="off">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="4" autocomplete="off">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="5" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-save-changes">VERIFY & CONTINUE</button>
                            </div>
                        </form>
                        
                        <div class="recovery-option">
                            <p><i class="bi bi-question-circle"></i> Can't access your current phone?</p>
                            <form action="includes/process_profile.php" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="phone_change_email_recovery">
                                <button type="submit" class="btn-text-link"><i class="bi bi-envelope"></i> Verify via Email instead</button>
                            </form>
                        </div>
                        
                        <form action="includes/process_profile.php" method="POST" class="mt-3">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="cancel_phone_change">
                            <button type="submit" class="btn-text-link text-danger"><i class="bi bi-x-circle"></i> Cancel phone change</button>
                        </form>
                        
                        <?php elseif ($hasPendingChange && $phoneChangeStep === 'verify_new'): ?>
                        <!-- Step 2: Verify OTP on new phone -->
                        <div class="step-indicator">
                            <div class="step completed"><span><i class="bi bi-check"></i></span><label>Verified</label></div>
                            <div class="step-line active"></div>
                            <div class="step active"><span>2</span><label>Verify New</label></div>
                        </div>
                        
                        <div class="verification-alert success">
                            <i class="bi bi-phone-vibrate"></i>
                            <div>
                                <strong>Step 2: Verify New Phone Number</strong>
                                <p>We sent an OTP to your new phone: <strong><?php echo htmlspecialchars($user['pending_phone']); ?></strong></p>
                            </div>
                        </div>
                        
                        <form action="includes/process_profile.php" method="POST" class="otp-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="verify_new_phone_otp">
                            <input type="hidden" name="otp_code" id="otp_code_hidden" value="">
                            <div class="form-field">
                                <label>Enter OTP from New Phone</label>
                                <div class="otp-input-group">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="0" autocomplete="off">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="1" autocomplete="off">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="2" autocomplete="off">
                                    <span class="otp-separator">-</span>
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="3" autocomplete="off">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="4" autocomplete="off">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="5" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-save-changes">COMPLETE CHANGE</button>
                            </div>
                        </form>
                        
                        <form action="includes/process_profile.php" method="POST" class="mt-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="resend_new_phone_otp">
                            <button type="submit" class="btn-text-link"><i class="bi bi-arrow-repeat"></i> Resend OTP to new phone</button>
                        </form>
                        
                        <form action="includes/process_profile.php" method="POST" class="mt-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="cancel_phone_change">
                            <button type="submit" class="btn-text-link text-danger"><i class="bi bi-x-circle"></i> Cancel phone change</button>
                        </form>
                        
                        <?php elseif ($hasPendingChange && $phoneChangeStep === 'email_recovery'): ?>
                        <!-- Email Recovery Mode -->
                        <div class="verification-alert purple">
                            <i class="bi bi-envelope-check"></i>
                            <div>
                                <strong>Email Verification Requested</strong>
                                <p>We sent a verification link to: <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
                                <small>Please check your email and click the verification link to proceed.</small>
                            </div>
                        </div>
                        
                        <form action="includes/process_profile.php" method="POST" class="mt-3">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="resend_phone_recovery_email">
                            <button type="submit" class="btn-outline"><i class="bi bi-envelope"></i> Resend Email</button>
                        </form>
                        
                        <form action="includes/process_profile.php" method="POST" class="mt-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="cancel_phone_change">
                            <button type="submit" class="btn-text-link text-danger"><i class="bi bi-x-circle"></i> Cancel phone change</button>
                        </form>
                        
                        <?php else: ?>
                        <!-- Initial phone change form -->
                        <form action="includes/process_profile.php" method="POST" class="edit-profile-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="request_phone_change">
                            
                            <div class="security-notice">
                                <i class="bi bi-shield-lock"></i>
                                <span>For security, changing your phone requires verification from both your current and new phone.</span>
                            </div>
                            
                            <div class="form-field">
                                <label>Current Password <span class="required">*</span></label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="current_password" id="phone_change_password" class="form-input has-toggle" placeholder="Enter your password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('phone_change_password')" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label>New Phone Number <span class="required">*</span></label>
                                <div class="phone-input-wrapper">
                                    <span class="phone-prefix">+63</span>
                                    <input type="tel" name="new_phone" class="form-input phone-input" placeholder="9XX XXX XXXX" maxlength="10" pattern="[0-9]{10}" required>
                                </div>
                                <small class="form-hint">OTP verification will be required for both your current and new phone.</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-save-changes">START PHONE CHANGE</button>
                                <a href="index.php?page=profile" class="btn-cancel">Cancel</a>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php elseif ($editPasswordMode): ?>
                <!-- ============ CHANGE PASSWORD VIEW ============ -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>Change Password</h2>
                        <a href="index.php?page=profile" class="back-link"><i class="bi bi-arrow-left"></i> Back to Profile</a>
                    </div>
                    <div class="content-card-body">
                        <form action="includes/process_profile.php" method="POST" class="edit-profile-form" id="passwordForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-field">
                                <label>Current Password <span class="required">*</span></label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="current_password" id="current_password" class="form-input has-toggle" placeholder="Enter current password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('current_password')" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label>New Password <span class="required">*</span></label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="new_password" id="new_password" class="form-input has-toggle" placeholder="Min 8 characters" required minlength="8">
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_password')" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label>Confirm New Password <span class="required">*</span></label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="confirm_password" id="profile_confirm_password" class="form-input has-toggle" placeholder="Repeat new password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('profile_confirm_password')" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-save-changes">UPDATE PASSWORD</button>
                                <a href="index.php?page=profile" class="btn-cancel">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php elseif ($verifyPhoneMode): ?>
                <!-- ============ VERIFY PHONE VIEW ============ -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>Verify Phone Number</h2>
                        <a href="index.php?page=profile" class="back-link"><i class="bi bi-arrow-left"></i> Back to Profile</a>
                    </div>
                    <div class="content-card-body">
                        <?php 
                        $phoneVerifyStep = $_SESSION['phone_verify_step'] ?? 'send_otp';
                        ?>
                        
                        <?php if ($phoneVerifyStep === 'send_otp'): ?>
                        <!-- Step 1: Send OTP -->
                        <div class="verification-info-box">
                            <i class="bi bi-phone-vibrate"></i>
                            <div>
                                <strong>Verify Your Phone Number</strong>
                                <p>We'll send a verification code to: <strong><?php echo htmlspecialchars($user['phone']); ?></strong></p>
                            </div>
                        </div>
                        
                        <form action="includes/process_profile.php" method="POST" class="verify-phone-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="send_phone_verification_otp">
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-save-changes">
                                    <i class="bi bi-send"></i> SEND VERIFICATION CODE
                                </button>
                                <a href="index.php?page=profile" class="btn-cancel">Cancel</a>
                            </div>
                        </form>
                        
                        <?php elseif ($phoneVerifyStep === 'verify_otp'): ?>
                        <!-- Step 2: Enter OTP -->
                        <div class="verification-alert success">
                            <i class="bi bi-check-circle"></i>
                            <div>
                                <strong>Code Sent!</strong>
                                <p>We sent a 6-digit verification code to: <strong><?php echo htmlspecialchars($user['phone']); ?></strong></p>
                            </div>
                        </div>
                        
                        <form action="includes/process_profile.php" method="POST" class="otp-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="verify_phone_otp">
                            <input type="hidden" name="otp_code" id="otp_code_hidden" value="">
                            <div class="form-field">
                                <label>Enter Verification Code</label>
                                <div class="otp-input-group">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="0" autocomplete="off" autofocus>
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="1" autocomplete="off">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="2" autocomplete="off">
                                    <span class="otp-separator">-</span>
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="3" autocomplete="off">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="4" autocomplete="off">
                                    <input type="text" class="otp-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="5" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-save-changes">VERIFY PHONE</button>
                            </div>
                        </form>
                        
                        <div class="otp-actions">
                            <form action="includes/process_profile.php" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="resend_phone_verification_otp">
                                <button type="submit" class="btn-text-link"><i class="bi bi-arrow-repeat"></i> Resend Code</button>
                            </form>
                            
                            <form action="includes/process_profile.php" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="cancel_phone_verification">
                                <button type="submit" class="btn-text-link text-danger"><i class="bi bi-x-circle"></i> Cancel</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php elseif ($verifyEmailMode): ?>
                <!-- ============ VERIFY EMAIL VIEW ============ -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>Verify Email Address</h2>
                        <a href="index.php?page=profile" class="back-link"><i class="bi bi-arrow-left"></i> Back to Profile</a>
                    </div>
                    <div class="content-card-body">
                        <?php 
                        $emailVerifyStep = $_SESSION['email_verify_step'] ?? 'send_link';
                        ?>
                        
                        <?php if ($emailVerifyStep === 'send_link'): ?>
                        <!-- Step 1: Send Verification Link -->
                        <div class="verification-info-box email">
                            <i class="bi bi-envelope-check"></i>
                            <div>
                                <strong>Verify Your Email Address</strong>
                                <p>We'll send a verification link to: <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
                            </div>
                        </div>
                        
                        <form action="includes/process_profile.php" method="POST" class="verify-phone-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="send_email_verification_link">
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-save-changes">
                                    <i class="bi bi-send"></i> SEND VERIFICATION LINK
                                </button>
                                <a href="index.php?page=profile" class="btn-cancel">Cancel</a>
                            </div>
                        </form>
                        
                        <?php elseif ($emailVerifyStep === 'check_inbox'): ?>
                        <!-- Step 2: Check Inbox -->
                        <div class="verification-alert success">
                            <i class="bi bi-envelope-open"></i>
                            <div>
                                <strong>Verification Link Sent!</strong>
                                <p>We sent a verification link to: <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
                                <small>Please check your inbox and click the link to verify your email.</small>
                            </div>
                        </div>
                        
                        <div class="email-verify-instructions">
                            <p><i class="bi bi-info-circle"></i> Didn't receive the email? Check your spam folder or click below to resend.</p>
                        </div>
                        
                        <div class="otp-actions">
                            <form action="includes/process_profile.php" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="resend_email_verification_link">
                                <button type="submit" class="btn-text-link"><i class="bi bi-arrow-repeat"></i> Resend Link</button>
                            </form>
                            
                            <form action="includes/process_profile.php" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="cancel_email_verification">
                                <button type="submit" class="btn-text-link text-danger"><i class="bi bi-x-circle"></i> Cancel</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- ============ DASHBOARD VIEW ============ -->
                <h1 class="page-title">Manage My Account</h1>
                
                <!-- Info Panels Row -->
                <div class="info-panels-grid">
                    <!-- Personal Profile Panel -->
                    <div class="info-panel">
                        <div class="info-panel-header">
                            <h3>Personal Profile</h3>
                            <a href="index.php?page=profile&edit=profile" class="edit-link">EDIT</a>
                        </div>
                        <div class="info-panel-body">
                            <p class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                            <p class="profile-email"><?php echo htmlspecialchars(maskEmail($user['email'])); ?></p>
                        </div>
                    </div>
                    
                    <!-- Address Book Panel (Placeholder - can be expanded) -->
                    <div class="info-panel">
                        <div class="info-panel-header">
                            <h3>Account Status</h3>
                        </div>
                        <div class="info-panel-body">
                            <div class="address-columns">
                                <div class="address-column">
                                    <h4>Email Status</h4>
                                    <p class="address-name">
                                        <?php if ($user['email_verified']): ?>
                                        <span class="status-verified"><i class="bi bi-check-circle-fill"></i> Verified</span>
                                        <?php else: ?>
                                        <span class="status-unverified"><i class="bi bi-exclamation-circle-fill"></i> Not Verified</span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="address-details"><?php echo htmlspecialchars($user['email']); ?></p>
                                    <?php if (!$user['email_verified']): ?>
                                    <a href="index.php?page=profile&action=verify_email" class="verify-now-btn">
                                        <i class="bi bi-shield-check"></i> Verify Now
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div class="address-column">
                                    <h4>Phone Status</h4>
                                    <p class="address-name">
                                        <?php if ($user['phone_verified']): ?>
                                        <span class="status-verified"><i class="bi bi-check-circle-fill"></i> Verified</span>
                                        <?php else: ?>
                                        <span class="status-unverified"><i class="bi bi-exclamation-circle-fill"></i> Not Verified</span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="address-details"><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></p>
                                    <?php if (!$user['phone_verified'] && $user['phone']): ?>
                                    <a href="index.php?page=profile&action=verify_phone" class="verify-now-btn">
                                        <i class="bi bi-shield-check"></i> Verify Now
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders Section -->
                <div class="recent-orders-panel">
                    <div class="panel-header">
                        <h3>Recent Orders</h3>
                        <?php if (!empty($recentOrders)): ?>
                        <a href="index.php?page=orders" class="view-all-link">View All <i class="bi bi-arrow-right"></i></a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($recentOrders)): ?>
                    <div class="empty-orders">
                        <i class="bi bi-bag-x"></i>
                        <p>No orders yet</p>
                        <a href="index.php?page=menu" class="btn-browse">Browse Menu</a>
                    </div>
                    <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Placed On</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td class="order-date"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                <td class="order-items">
                                    <div class="items-preview">
                                        <?php
                                        // Show item summary or placeholder icons
                                        $items = explode(', ', $order['items_summary'] ?? '');
                                        $itemCount = count($items);
                                        ?>
                                        <span class="item-count"><?php echo $itemCount; ?> item<?php echo $itemCount > 1 ? 's' : ''; ?></span>
                                    </div>
                                </td>
                                <td class="order-total">₱<?php echo number_format($order['total'], 2); ?></td>
                                <td class="order-action">
                                    <a href="index.php?page=orders" class="manage-link">MANAGE</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<style>
/* ========================================
   BAKE & TAKE - PROFILE PAGE STYLES
   Warm, bakery-inspired aesthetic
   ======================================== */

.lazada-profile-section {
    background: var(--cream);
    min-height: calc(100vh - 200px);
    padding: 2rem 0;
}

.lazada-profile-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

/* ========== SIDEBAR ========== */
.profile-sidebar {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 0;
    box-shadow: var(--shadow-sm);
    height: fit-content;
    position: sticky;
    top: 100px;
    overflow: hidden;
}

.sidebar-user-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, var(--cream) 0%, var(--accent) 100%);
    border-bottom: 1px solid var(--cream-dark);
}

.sidebar-avatar {
    width: 55px;
    height: 55px;
    background: var(--gradient-warm);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: var(--shadow-md);
}

.sidebar-avatar i {
    font-size: 1.75rem;
    color: var(--white);
}

.sidebar-user-info {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 0;
}

.sidebar-greeting {
    font-weight: 600;
    color: var(--dark);
    font-size: 1rem;
    font-family: var(--font-display);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.7rem;
    color: #16a34a;
    background: rgba(22, 163, 74, 0.12);
    padding: 0.25rem 0.6rem;
    border-radius: 20px;
    width: fit-content;
    font-weight: 500;
}

.verified-badge i {
    font-size: 0.7rem;
}

.sidebar-nav {
    padding: 0.75rem 0;
}

.nav-section {
    border-bottom: 1px solid var(--cream-dark);
    padding: 0.5rem 0;
}

.nav-section:last-child {
    border-bottom: none;
}

.nav-section-title {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--dark);
    margin: 0;
    font-family: var(--font-display);
}

.nav-section-title i {
    color: var(--primary-dark);
    font-size: 1.1rem;
}

.nav-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-links li a {
    display: block;
    padding: 0.65rem 1.5rem 0.65rem 3rem;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.9rem;
    transition: var(--transition);
    border-left: 3px solid transparent;
}

.nav-links li a:hover {
    color: var(--primary-dark);
    background: var(--accent);
    border-left-color: var(--primary);
}

.nav-links li a.active {
    color: var(--primary-dark);
    background: var(--accent);
    font-weight: 500;
    border-left-color: var(--primary);
}

.nav-logout {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.85rem 1.5rem;
    color: #dc2626;
    text-decoration: none;
    font-size: 0.9rem;
    transition: var(--transition);
}

.nav-logout:hover {
    background: #fef2f2;
    color: #b91c1c;
}

/* ========== MAIN CONTENT ========== */
.profile-main-content {
    min-width: 0;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--dark);
    margin: 0 0 1.75rem 0;
    font-family: var(--font-display);
}

/* Alert Styles */
.alert-lazada {
    padding: 1rem 1.25rem;
    border-radius: var(--radius-md);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.95rem;
}

.alert-lazada i {
    font-size: 1.25rem;
    flex-shrink: 0;
}

.alert-lazada.alert-success {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}

.alert-lazada.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.alert-lazada.alert-info {
    background: #dbeafe;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

/* Info Panels Grid */
.info-panels-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.info-panel {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    transition: var(--transition);
}

.info-panel:hover {
    box-shadow: var(--shadow-md);
}

.info-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.15rem 1.5rem;
    background: linear-gradient(135deg, var(--cream) 0%, rgba(212, 165, 116, 0.1) 100%);
    border-bottom: 1px solid var(--cream-dark);
}

.info-panel-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark);
    margin: 0;
    font-family: var(--font-display);
}

.edit-link {
    color: var(--primary-dark);
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: var(--transition);
}

.edit-link:hover {
    color: var(--secondary);
}

.info-panel-body {
    padding: 1.5rem;
}

.profile-name {
    font-weight: 600;
    color: var(--dark);
    margin: 0 0 0.35rem 0;
    font-size: 1.05rem;
}

.profile-email {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0 0 1.25rem 0;
}

.marketing-prefs {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.9rem;
    color: var(--text-secondary);
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-dark);
}

/* Address Columns */
.address-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.address-column h4 {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-light);
    text-transform: uppercase;
    margin: 0 0 0.75rem 0;
    letter-spacing: 0.5px;
}

.address-name {
    font-weight: 500;
    color: var(--dark);
    margin: 0 0 0.35rem 0;
    font-size: 0.95rem;
}

.address-details {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0;
    line-height: 1.5;
}

.status-verified {
    color: #16a34a;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.status-unverified {
    color: #d97706;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

/* Verify Now Button */
.verify-now-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: 0.75rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    color: white;
    font-size: 0.8rem;
    font-weight: 600;
    border-radius: var(--radius-md);
    text-decoration: none;
    transition: var(--transition);
    box-shadow: 0 2px 8px rgba(22, 163, 74, 0.25);
}

.verify-now-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35);
}

.verify-now-btn i {
    font-size: 0.9rem;
}

/* Verification Info Box */
.verification-info-box {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #bbf7d0;
    border-radius: var(--radius-md);
    margin-bottom: 1.5rem;
}

.verification-info-box > i {
    font-size: 2rem;
    color: #16a34a;
    flex-shrink: 0;
}

.verification-info-box strong {
    display: block;
    color: #15803d;
    margin-bottom: 0.35rem;
    font-size: 1.05rem;
}

.verification-info-box p {
    color: #166534;
    margin: 0;
    font-size: 0.95rem;
}

/* OTP Actions */
.otp-actions {
    display: flex;
    gap: 1.5rem;
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--cream-dark);
}

.verify-phone-form .form-actions {
    margin-top: 1rem;
}

/* Email Verification Styles */
.verification-info-box.email {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border: 1px solid #bfdbfe;
}

.verification-info-box.email > i {
    color: #2563eb;
}

.verification-info-box.email strong {
    color: #1d4ed8;
}

.verification-info-box.email p {
    color: #1e40af;
}

.email-verify-instructions {
    margin-top: 1.5rem;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-radius: var(--radius-md);
    border-left: 3px solid #2563eb;
}

.email-verify-instructions p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.email-verify-instructions i {
    color: #2563eb;
}

/* Recent Orders Panel */
.recent-orders-panel {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.15rem 1.5rem;
    background: linear-gradient(135deg, var(--cream) 0%, rgba(212, 165, 116, 0.1) 100%);
    border-bottom: 1px solid var(--cream-dark);
}

.panel-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark);
    margin: 0;
    font-family: var(--font-display);
}

.view-all-link {
    color: var(--primary-dark);
    text-decoration: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-weight: 500;
    transition: var(--transition);
}

.view-all-link:hover {
    color: var(--secondary);
}

/* Orders Table */
.orders-table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th {
    text-align: left;
    padding: 0.85rem 1.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--cream-dark);
}

.orders-table td {
    padding: 1.1rem 1.5rem;
    font-size: 0.95rem;
    color: var(--text-primary);
    border-bottom: 1px solid var(--cream-dark);
    vertical-align: middle;
}

.orders-table tbody tr:last-child td {
    border-bottom: none;
}

.orders-table tbody tr:hover {
    background: var(--accent);
}

.orders-table .order-number {
    font-weight: 600;
    color: var(--secondary);
    font-family: var(--font-display);
}

.orders-table .order-date {
    color: var(--text-secondary);
}

.orders-table .order-items {
    color: var(--text-secondary);
}

.items-preview {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.item-count {
    font-size: 0.9rem;
}

.orders-table .order-total {
    font-weight: 600;
    color: var(--secondary);
}

.manage-link {
    color: var(--primary-dark);
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    transition: var(--transition);
}

.manage-link:hover {
    color: var(--secondary);
}

/* Empty Orders */
.empty-orders {
    text-align: center;
    padding: 3.5rem 1.5rem;
    color: var(--text-secondary);
}

.empty-orders i {
    font-size: 3.5rem;
    color: var(--cream-dark);
    margin-bottom: 1.25rem;
    display: block;
}

.empty-orders p {
    margin: 0 0 1.25rem 0;
    font-size: 1rem;
}

.btn-browse {
    display: inline-block;
    padding: 0.75rem 2rem;
    background: var(--gradient-warm);
    color: var(--white);
    text-decoration: none;
    border-radius: 50px;
    font-size: 0.95rem;
    font-weight: 500;
    transition: var(--transition);
}

.btn-browse:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-glow);
    color: var(--white);
}

/* ========== CONTENT CARD (Edit Views) ========== */
.content-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.content-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.75rem;
    background: linear-gradient(135deg, var(--cream) 0%, var(--accent) 100%);
    border-bottom: 1px solid var(--cream-dark);
}

.content-card-header h2 {
    font-size: 1.35rem;
    font-weight: 600;
    color: var(--dark);
    margin: 0;
    font-family: var(--font-display);
}

.back-link {
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    transition: var(--transition);
}

.back-link:hover {
    color: var(--primary-dark);
}

.content-card-body {
    padding: 2rem;
}

/* ========== EDIT PROFILE FORM ========== */
.edit-profile-form {
    max-width: 750px;
}

.form-row {
    display: grid;
    gap: 1.75rem;
    margin-bottom: 1.75rem;
}

.form-row.three-cols {
    grid-template-columns: repeat(3, 1fr);
}

.form-row.two-cols {
    grid-template-columns: repeat(2, 1fr);
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-field label {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-secondary);
}

.change-link {
    color: var(--primary-dark);
    text-decoration: none;
    font-size: 0.8rem;
    margin-left: 0.5rem;
    font-weight: 500;
}

.change-link:hover {
    color: var(--secondary);
    text-decoration: underline;
}

.required {
    color: #dc2626;
}

.form-input {
    padding: 0.75rem 1rem;
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-md);
    font-size: 0.95rem;
    color: var(--text-primary);
    transition: var(--transition);
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.15);
}

.input-with-clear {
    position: relative;
    display: flex;
    align-items: center;
}

.input-with-clear .form-input {
    flex: 1;
    padding-right: 2.5rem;
}

.clear-btn {
    position: absolute;
    right: 0.75rem;
    background: none;
    border: none;
    color: var(--text-light);
    cursor: pointer;
    padding: 0.25rem;
    transition: var(--transition);
}

.clear-btn:hover {
    color: var(--text-secondary);
}

.static-value {
    padding: 0.75rem 0;
    font-size: 0.95rem;
    color: var(--text-primary);
}

.birthday-selects {
    display: flex;
    gap: 0.75rem;
}

.select-input {
    padding: 0.75rem 1rem;
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-md);
    font-size: 0.95rem;
    color: var(--text-primary);
    background: var(--white);
    cursor: pointer;
    min-width: 90px;
    transition: var(--transition);
}

.select-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.15);
}

.select-input.full-width {
    width: 100%;
}

.form-actions {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    margin-top: 2.5rem;
}

.btn-save-changes {
    padding: 1rem 2.5rem;
    background: var(--gradient-warm);
    color: var(--white);
    border: none;
    border-radius: 50px;
    font-size: 0.95rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: var(--transition);
}

.btn-save-changes:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-glow);
}

.btn-cancel {
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.95rem;
    transition: var(--transition);
}

.btn-cancel:hover {
    color: var(--primary-dark);
}

/* Security Notice */
.security-notice {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 1.1rem 1.25rem;
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.08) 0%, rgba(13, 110, 253, 0.04) 100%);
    border: 1px solid rgba(13, 110, 253, 0.2);
    border-radius: var(--radius-md);
    margin-bottom: 1.75rem;
    font-size: 0.95rem;
    color: #084298;
}

.security-notice i {
    font-size: 1.35rem;
    color: #0d6efd;
}

.form-hint {
    font-size: 0.85rem;
    color: var(--text-light);
    margin-top: 0.35rem;
}

/* Phone Input Wrapper */
.phone-input-wrapper {
    display: flex;
    align-items: center;
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--white);
    transition: var(--transition);
}

.phone-input-wrapper:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.15);
}

.phone-prefix {
    padding: 0.75rem 1rem;
    background: var(--cream);
    border-right: 2px solid var(--cream-dark);
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 0.95rem;
}

.phone-input-wrapper .phone-input {
    border: none !important;
    flex: 1;
    box-shadow: none !important;
}

.phone-input-wrapper .phone-input:focus {
    outline: none;
}

/* OTP Input Group - Individual Boxes */
.otp-input-group {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    align-items: center;
    margin-top: 0.75rem;
}

.otp-digit-input {
    width: 52px;
    height: 60px;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 700;
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-md);
    transition: all 0.2s ease;
    color: var(--dark);
    background: var(--white);
}

.otp-digit-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(232, 180, 130, 0.2);
    outline: none;
}

.otp-digit-input.filled {
    background: var(--accent);
    border-color: var(--primary);
}

.otp-digit-input.error {
    border-color: #dc3545;
    animation: shake 0.3s ease;
}

@keyframes otpShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.otp-separator {
    font-size: 1.5rem;
    color: var(--text-light);
    padding: 0 0.25rem;
}

@media (max-width: 480px) {
    .otp-digit-input {
        width: 42px;
        height: 50px;
        font-size: 1.25rem;
    }
    
    .otp-input-group {
        gap: 0.35rem;
    }
}

/* Verification Alerts */
.verification-alert {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.35rem;
    border-radius: var(--radius-md);
    margin-bottom: 1.75rem;
}

.verification-alert i {
    font-size: 1.6rem;
    flex-shrink: 0;
    margin-top: 0.1rem;
}

.verification-alert strong {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 1rem;
}

.verification-alert p {
    margin: 0 0 0.35rem 0;
    font-size: 0.95rem;
}

.verification-alert small {
    color: inherit;
    opacity: 0.8;
}

.verification-alert.info {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(13, 110, 253, 0.05) 100%);
    border: 1px solid rgba(13, 110, 253, 0.2);
    color: #084298;
}

.verification-alert.success {
    background: linear-gradient(135deg, rgba(22, 163, 74, 0.1) 0%, rgba(22, 163, 74, 0.05) 100%);
    border: 1px solid rgba(22, 163, 74, 0.2);
    color: #166534;
}

.verification-alert.pending {
    background: linear-gradient(135deg, rgba(217, 119, 6, 0.1) 0%, rgba(217, 119, 6, 0.05) 100%);
    border: 1px solid rgba(217, 119, 6, 0.2);
    color: #92400e;
}

.verification-alert.purple {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
    border: 1px solid rgba(139, 92, 246, 0.2);
    color: #5b21b6;
}

/* Step Indicator */
.step-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.75rem;
    padding: 1.25rem 0;
}

.step-indicator .step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.45rem;
}

.step-indicator .step span {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--cream);
    border: 2px solid var(--cream-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-light);
    transition: var(--transition);
}

.step-indicator .step.active span {
    background: var(--primary);
    border-color: var(--primary);
    color: var(--white);
}

.step-indicator .step.completed span {
    background: #16a34a;
    border-color: #16a34a;
    color: var(--white);
}

.step-indicator .step label {
    font-size: 0.75rem;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.step-indicator .step.active label,
.step-indicator .step.completed label {
    color: var(--dark);
}

.step-indicator .step-line {
    width: 70px;
    height: 3px;
    background: var(--cream-dark);
    margin: 0 0.75rem;
    margin-bottom: 1.35rem;
    border-radius: 2px;
    transition: var(--transition);
}

.step-indicator .step-line.active {
    background: linear-gradient(90deg, #16a34a, var(--primary));
}

/* Recovery Option */
.recovery-option {
    padding: 1.1rem;
    background: var(--cream);
    border-radius: var(--radius-md);
    margin-top: 1.25rem;
    border: 1px dashed var(--cream-dark);
}

.recovery-option p {
    margin: 0 0 0.6rem 0;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.btn-text-link {
    background: none;
    border: none;
    color: var(--primary-dark);
    font-size: 0.9rem;
    cursor: pointer;
    text-decoration: underline;
    padding: 0;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    transition: var(--transition);
}

.btn-text-link:hover {
    color: var(--secondary);
}

.btn-text-link.text-danger {
    color: #dc2626;
}

.btn-text-link.text-danger:hover {
    color: #b91c1c;
}

.btn-outline {
    padding: 0.65rem 1.25rem;
    background: var(--white);
    border: 2px solid var(--primary);
    color: var(--primary-dark);
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: var(--transition);
}

.btn-outline:hover {
    background: var(--primary);
    color: var(--white);
}

.mt-2 { margin-top: 0.5rem; }
.mt-3 { margin-top: 1rem; }
.d-inline { display: inline; }

/* ========== RESPONSIVE ========== */
@media (max-width: 991px) {
    .lazada-profile-layout {
        grid-template-columns: 1fr;
    }
    
    .profile-sidebar {
        position: static;
    }
    
    .info-panels-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row.three-cols,
    .form-row.two-cols {
        grid-template-columns: 1fr;
    }
    
    .address-columns {
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }
}

@media (max-width: 575px) {
    .lazada-profile-section {
        padding: 1rem 0;
    }
    
    .content-card-body,
    .info-panel-body {
        padding: 1.25rem;
    }
    
    .sidebar-user-header {
        padding: 1.25rem;
    }
    
    .orders-table {
        font-size: 0.85rem;
    }
    
    .orders-table th,
    .orders-table td {
        padding: 0.85rem 1rem;
    }
    
    .birthday-selects {
        flex-direction: column;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-save-changes {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phone input formatting
    const phoneInputs = document.querySelectorAll('.phone-input');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            this.value = value;
        });
    });
    
    // OTP individual digit inputs handling
    const otpForms = document.querySelectorAll('.otp-form');
    otpForms.forEach(form => {
        const digitInputs = form.querySelectorAll('.otp-digit-input');
        const hiddenInput = form.querySelector('input[name="otp_code"]');
        
        if (digitInputs.length === 0) return;
        
        // Function to update hidden input with combined value
        const updateHiddenInput = () => {
            const code = Array.from(digitInputs).map(input => input.value).join('');
            if (hiddenInput) hiddenInput.value = code;
        };
        
        // Function to update filled state
        const updateFilledState = (input) => {
            if (input.value) {
                input.classList.add('filled');
            } else {
                input.classList.remove('filled');
            }
        };
        
        digitInputs.forEach((input, index) => {
            // Input event - handle typing
            input.addEventListener('input', function(e) {
                // Only allow digits
                this.value = this.value.replace(/[^0-9]/g, '');
                
                updateFilledState(this);
                updateHiddenInput();
                
                // Auto-advance to next input
                if (this.value && index < digitInputs.length - 1) {
                    digitInputs[index + 1].focus();
                }
            });
            
            // Keydown event - handle backspace and navigation
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace') {
                    if (!this.value && index > 0) {
                        // Move to previous input on backspace if current is empty
                        digitInputs[index - 1].focus();
                        digitInputs[index - 1].value = '';
                        updateFilledState(digitInputs[index - 1]);
                        updateHiddenInput();
                        e.preventDefault();
                    }
                } else if (e.key === 'ArrowLeft' && index > 0) {
                    digitInputs[index - 1].focus();
                } else if (e.key === 'ArrowRight' && index < digitInputs.length - 1) {
                    digitInputs[index + 1].focus();
                }
            });
            
            // Focus event - select all text on focus
            input.addEventListener('focus', function() {
                this.select();
            });
            
            // Paste event - handle paste across all inputs
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                const digits = pastedData.replace(/[^0-9]/g, '').split('').slice(0, 6);
                
                digits.forEach((digit, i) => {
                    if (digitInputs[i]) {
                        digitInputs[i].value = digit;
                        updateFilledState(digitInputs[i]);
                    }
                });
                
                // Focus on next empty or last input
                const nextEmpty = Array.from(digitInputs).find(inp => !inp.value);
                if (nextEmpty) {
                    nextEmpty.focus();
                } else {
                    digitInputs[digitInputs.length - 1].focus();
                }
                
                updateHiddenInput();
            });
        });
        
        // Form validation before submit
        form.addEventListener('submit', function(e) {
            updateHiddenInput();
            const code = hiddenInput ? hiddenInput.value : '';
            if (code.length !== 6) {
                e.preventDefault();
                digitInputs.forEach(input => {
                    input.classList.add('error');
                    setTimeout(() => input.classList.remove('error'), 300);
                });
                const firstEmpty = Array.from(digitInputs).find(inp => !inp.value) || digitInputs[0];
                firstEmpty.focus();
            }
        });
    });
    
    // Password form validation
    const passwordForm = document.getElementById('passwordForm');
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
