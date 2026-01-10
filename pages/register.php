<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Create Account</h2>
            <p>Join us and start your baking journey</p>
        </div>
        
        <form action="includes/process_register.php" method="POST" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-control form-control-custom" name="first_name" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="form-control form-control-custom" name="last_name" required>
                </div>
            </div>
            
            <div class="mt-3">
                <label class="form-label">Email Address</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" class="form-control form-control-custom with-icon" name="email" placeholder="your@email.com" required>
                </div>
            </div>
            
            <div class="mt-3">
                <label class="form-label">Password</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" class="form-control form-control-custom with-icon" name="password" placeholder="Min 8 characters" required minlength="8">
                </div>
            </div>
            
            <div class="mt-3">
                <label class="form-label">Confirm Password</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input type="password" class="form-control form-control-custom with-icon" name="confirm_password" placeholder="Repeat password" required>
                </div>
            </div>
            
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">
                    I agree to the <a href="#" class="terms-link">Terms of Service</a> and <a href="#" class="terms-link">Privacy Policy</a>
                </label>
            </div>
            
            <button type="submit" class="btn btn-hero btn-hero-primary w-100 mt-4 mb-3">
                <i class="bi bi-person-plus me-2"></i> Create Account
            </button>
        </form>
        
        <div class="auth-footer">
            Already have an account? <a href="index.php?page=login">Sign in</a>
        </div>
    </div>
</div>

<style>
.auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: linear-gradient(135deg, var(--cream) 0%, var(--accent) 100%);
}

.auth-card {
    background: var(--white);
    padding: 3rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    width: 100%;
    max-width: 480px;
}

.auth-logo {
    font-family: var(--font-display);
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.auth-logo i { font-size: 2rem; color: var(--primary); }

.auth-header { text-align: center; margin-bottom: 2rem; }
.auth-header h2 { color: var(--dark); margin-bottom: 0.5rem; }
.auth-header p { color: var(--text-secondary); }

.input-icon-wrapper { position: relative; }

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
}

.form-control-custom.with-icon { padding-left: 2.75rem; }

.terms-link { color: var(--primary-dark); }
.terms-link:hover { color: var(--secondary); }

.divider {
    text-align: center;
    margin: 1.5rem 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: var(--cream-dark);
}

.divider span {
    background: var(--white);
    padding: 0 1rem;
    color: var(--text-light);
    font-size: 0.9rem;
    position: relative;
}

.social-login { display: flex; justify-content: center; gap: 1rem; }

.btn-social {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 2px solid var(--cream-dark);
    background: var(--white);
    color: var(--text-secondary);
    font-size: 1.25rem;
    cursor: pointer;
    transition: var(--transition);
}

.btn-social:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: var(--accent);
}

.auth-footer {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--cream-dark);
    color: var(--text-secondary);
}

.auth-footer a { color: var(--primary-dark); font-weight: 600; }
.auth-footer a:hover { color: var(--secondary); }
</style>
