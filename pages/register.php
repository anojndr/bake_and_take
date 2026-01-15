<?php $flash = getFlashMessage(); ?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Create Account</h2>
            <p>Join us and start your baking journey</p>
        </div>
        
        <?php if ($flash): ?>
        <div class="alert-custom alert-<?php echo $flash['type']; ?>">
            <i class="bi bi-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'info' ? 'info-circle' : 'exclamation-circle'); ?>"></i>
            <?php echo $flash['message']; ?>
        </div>
        <?php endif; ?>
        
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
            
            <!-- Verification Method Selection -->
            <div class="mt-4">
                <label class="form-label fw-semibold">Choose Verification Method</label>
                <div class="verification-options">
                    <div class="verification-option selected" data-method="email">
                        <input type="radio" name="verification_method" value="email" id="verify_email" checked>
                        <label for="verify_email" class="verification-option-label">
                            <div class="verification-icon">
                                <i class="bi bi-envelope-check"></i>
                            </div>
                            <div class="verification-info">
                                <span class="verification-title">Email Verification</span>
                                <span class="verification-desc">We'll send a verification link to your email</span>
                            </div>
                        </label>
                    </div>
                    <div class="verification-option" data-method="phone">
                        <input type="radio" name="verification_method" value="phone" id="verify_phone">
                        <label for="verify_phone" class="verification-option-label">
                            <div class="verification-icon">
                                <i class="bi bi-phone-vibrate"></i>
                            </div>
                            <div class="verification-info">
                                <span class="verification-title">Phone Verification</span>
                                <span class="verification-desc">We'll send an OTP code to your phone</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Email Field (always shown, required) -->
            <div class="mt-3" id="email-field">
                <label class="form-label">Email Address</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" class="form-control form-control-custom with-icon" name="email" id="email" placeholder="your@email.com" required>
                </div>
            </div>
            
            <!-- Phone Field (always required) -->
            <div class="mt-3" id="phone-field">
                <label class="form-label">Phone Number</label>
                <div class="phone-input-wrapper">
                    <span class="phone-prefix">+63</span>
                    <input type="tel" class="form-control form-control-custom phone-input" name="phone" id="phone" placeholder="9XX XXX XXXX" maxlength="10" pattern="9[0-9]{9}" required>
                </div>
                <small class="text-muted">Enter your 10-digit Philippine mobile number (must start with 9)</small>
                <div class="invalid-feedback" id="phone-error"></div>
            </div>
            
            <div class="mt-3">
                <label class="form-label">Password</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" class="form-control form-control-custom with-icon has-toggle" name="password" id="reg_password" placeholder="Strong password required" required minlength="8">
                    <button type="button" class="password-toggle" onclick="togglePassword('reg_password')" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="password-requirements mt-2" id="password-requirements">
                    <small class="text-muted">Password must contain:</small>
                    <ul class="password-checklist mb-0 ps-3">
                        <li id="req-length" class="text-muted"><small>At least 8 characters</small></li>
                        <li id="req-upper" class="text-muted"><small>An uppercase letter (A-Z)</small></li>
                        <li id="req-lower" class="text-muted"><small>A lowercase letter (a-z)</small></li>
                        <li id="req-number" class="text-muted"><small>A number (0-9)</small></li>
                        <li id="req-special" class="text-muted"><small>A special character (!@#$%^&*...)</small></li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-3">
                <label class="form-label">Confirm Password</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input type="password" class="form-control form-control-custom with-icon has-toggle" name="confirm_password" id="reg_confirm_password" placeholder="Repeat password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('reg_confirm_password')" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">
                    I agree to the <a href="index.php?page=terms-of-service" class="terms-link" target="_blank" rel="noopener noreferrer">Terms of Service</a> and <a href="index.php?page=privacy-policy" class="terms-link" target="_blank" rel="noopener noreferrer">Privacy Policy</a>
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
    padding-top: 100px;
    background: linear-gradient(135deg, var(--cream) 0%, var(--accent) 100%);
}

.auth-card {
    background: var(--white);
    padding: 3rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    width: 100%;
    max-width: 520px;
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

/* Password Requirements Checklist */
.password-checklist {
    list-style: none;
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

.password-checklist li {
    padding: 0.15rem 0;
    transition: color 0.2s ease;
}

.password-checklist li.text-success {
    color: #16a34a !important;
}

/* Validation States */
.form-control-custom.is-valid {
    border-color: #16a34a;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2316a34a' d='M2.3 6.73.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1rem;
    padding-right: 2.5rem;
}

.form-control-custom.is-invalid {
    border-color: #dc2626;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' fill='none' stroke='%23dc2626'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc2626' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1rem;
    padding-right: 2.5rem;
}

.form-control-custom.has-toggle.is-valid,
.form-control-custom.has-toggle.is-invalid {
    background-position: right 2.75rem center;
    padding-right: 4.5rem;
}

.invalid-feedback {
    display: block;
    color: #dc2626;
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

/* Verification Options Styles */
.verification-options {
    display: flex;
    gap: 1rem;
    margin-top: 0.75rem;
}

.verification-option {
    flex: 1;
    position: relative;
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-lg);
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--white);
}

.verification-option:hover {
    border-color: var(--primary);
    background: var(--accent);
}

.verification-option.selected {
    border-color: var(--primary);
    background: linear-gradient(135deg, var(--accent) 0%, rgba(232, 180, 130, 0.2) 100%);
    box-shadow: 0 4px 12px rgba(232, 180, 130, 0.25);
}

.verification-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.verification-option-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    margin: 0;
}

.verification-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--cream);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.verification-option.selected .verification-icon {
    background: var(--primary);
    color: var(--white);
}

.verification-icon i {
    font-size: 1.25rem;
    color: var(--primary-dark);
}

.verification-option.selected .verification-icon i {
    color: var(--white);
}

.verification-info {
    display: flex;
    flex-direction: column;
}

.verification-title {
    font-weight: 600;
    color: var(--dark);
    font-size: 0.95rem;
}

.verification-desc {
    font-size: 0.8rem;
    color: var(--text-light);
    margin-top: 0.15rem;
}

/* Responsive styles for verification options */
@media (max-width: 576px) {
    .verification-options {
        flex-direction: column;
    }
    
    .verification-option {
        padding: 0.75rem;
    }
    
    .verification-icon {
        width: 40px;
        height: 40px;
    }
    
    .verification-icon i {
        font-size: 1rem;
    }
}

/* Phone Input with Prefix */
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const verificationOptions = document.querySelectorAll('.verification-option');
    const phoneField = document.getElementById('phone-field');
    const phoneInput = document.getElementById('phone');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('reg_password');
    const registerForm = document.getElementById('registerForm');
    
    verificationOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Update selected state
            verificationOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            
            // Check the radio input
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
        });
    });
    
    // Also handle direct radio change
    document.querySelectorAll('input[name="verification_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const option = this.closest('.verification-option');
            verificationOptions.forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
        });
    });
    
    // Phone number validation
    function validatePhone(value) {
        const digits = value.replace(/[^0-9]/g, '');
        return digits.length === 10 && digits.charAt(0) === '9';
    }
    
    // Phone number input formatting - only allow numbers
    phoneInput.addEventListener('input', function(e) {
        // Remove any non-numeric characters
        let value = this.value.replace(/[^0-9]/g, '');
        
        // Limit to 10 digits
        if (value.length > 10) {
            value = value.slice(0, 10);
        }
        
        this.value = value;
        
        // Validate and show feedback
        const phoneError = document.getElementById('phone-error');
        if (value.length > 0) {
            if (!validatePhone(value)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                if (value.charAt(0) !== '9') {
                    phoneError.textContent = 'Phone number must start with 9';
                } else {
                    phoneError.textContent = 'Phone number must be 10 digits';
                }
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                phoneError.textContent = '';
            }
        } else {
            this.classList.remove('is-invalid', 'is-valid');
            phoneError.textContent = '';
        }
    });
    
    // Prevent non-numeric key presses
    phoneInput.addEventListener('keypress', function(e) {
        if (!/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
            e.preventDefault();
        }
    });
    
    // Email validation
    emailInput.addEventListener('input', function() {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (this.value.length > 0) {
            if (emailPattern.test(this.value)) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            }
        } else {
            this.classList.remove('is-invalid', 'is-valid');
        }
    });
    
    // Password strength validation
    function checkPasswordStrength(password) {
        return {
            length: password.length >= 8,
            upper: /[A-Z]/.test(password),
            lower: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password)
        };
    }
    
    function updatePasswordUI(checks) {
        const requirements = [
            { id: 'req-length', check: checks.length },
            { id: 'req-upper', check: checks.upper },
            { id: 'req-lower', check: checks.lower },
            { id: 'req-number', check: checks.number },
            { id: 'req-special', check: checks.special }
        ];
        
        requirements.forEach(req => {
            const element = document.getElementById(req.id);
            if (req.check) {
                element.classList.remove('text-muted', 'text-danger');
                element.classList.add('text-success');
                element.innerHTML = '<small><i class="bi bi-check-circle-fill me-1"></i>' + element.textContent.trim() + '</small>';
            } else {
                element.classList.remove('text-success');
                element.classList.add('text-muted');
                element.innerHTML = '<small>' + element.textContent.trim() + '</small>';
            }
        });
    }
    
    passwordInput.addEventListener('input', function() {
        const checks = checkPasswordStrength(this.value);
        updatePasswordUI(checks);
        
        const allValid = Object.values(checks).every(v => v);
        if (this.value.length > 0) {
            if (allValid) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            }
        } else {
            this.classList.remove('is-invalid', 'is-valid');
        }
    });
    
    // Form submission validation
    registerForm.addEventListener('submit', function(e) {
        let isValid = true;
        let errorMessage = '';
        
        // Validate email
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(emailInput.value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address.';
            emailInput.classList.add('is-invalid');
        }
        
        // Validate phone
        if (!validatePhone(phoneInput.value)) {
            isValid = false;
            errorMessage = 'Please enter a valid Philippine mobile number (10 digits starting with 9).';
            phoneInput.classList.add('is-invalid');
        }
        
        // Validate password strength
        const checks = checkPasswordStrength(passwordInput.value);
        const allValid = Object.values(checks).every(v => v);
        if (!allValid) {
            isValid = false;
            errorMessage = 'Password must contain uppercase, lowercase, numbers, and special characters.';
            passwordInput.classList.add('is-invalid');
        }
        
        if (!isValid) {
            e.preventDefault();
            alert(errorMessage);
        }
    });
});
</script>
