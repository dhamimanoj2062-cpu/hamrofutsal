// js/validation.js
// Client-side form validation for auth pages

document.addEventListener('DOMContentLoaded', function () {
    
    // --- Elements ---
    const registerForm = document.getElementById('registerForm');
    const resetForm = document.getElementById('resetForm');
    
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    
    const submitBtn = document.getElementById('submitBtn');
    
    // Message containers
    const emailMsg = document.getElementById('email-msg');
    const phoneMsg = document.getElementById('phone-msg');
    const matchMsg = document.getElementById('password-match-msg');
    const strengthContainer = document.getElementById('password-strength-container');
    const reqList = document.getElementById('password-requirements');
    
    // --- Validation State ---
    let isEmailValid = true;
    let isPhoneValid = true;
    let isPasswordValid = true;
    let isMatchValid = true;
    
    // Initialize state based on which form we're on
    if (registerForm) {
        isEmailValid = emailInput.value !== '' ? validateEmail(emailInput.value) : false;
        isPhoneValid = phoneInput.value !== '' ? validatePhone(phoneInput.value) : false;
        isPasswordValid = false;
        isMatchValid = false;
        checkFormValidity();
    } else if (resetForm) {
        isPasswordValid = false;
        isMatchValid = false;
        checkFormValidity();
    }

    // --- Validation Functions ---
    
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function validatePhone(phone) {
        // Nepal mobile: exactly 10 digits, starts with 97 or 98
        const cleanPhone = phone.replace(/[\s\-]/g, '');
        const re = /^(97|98)\d{8}$/;
        return re.test(cleanPhone);
    }
    
    function checkPasswordStrength(password) {
        const requirements = {
            length: password.length >= 8,
            upper: /[A-Z]/.test(password),
            lower: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*()_\-+=\[\]{};:,.<>?\/\\|`~]/.test(password)
        };
        
        let score = 0;
        for (let key in requirements) {
            if (requirements[key]) score++;
        }
        
        return { requirements, score };
    }
    
    // --- Event Listeners ---
    
    if (emailInput && emailMsg) {
        emailInput.addEventListener('input', function() {
            if (this.value === '') {
                emailMsg.textContent = '';
                emailMsg.className = 'validation-hint';
                isEmailValid = false;
            } else if (validateEmail(this.value)) {
                emailMsg.textContent = 'Valid email format.';
                emailMsg.className = 'validation-hint success';
                isEmailValid = true;
            } else {
                emailMsg.textContent = 'Please enter a valid email address.';
                emailMsg.className = 'validation-hint error';
                isEmailValid = false;
            }
            checkFormValidity();
        });
    }
    
    if (phoneInput && phoneMsg) {
        phoneInput.addEventListener('input', function() {
            // Remove non-numeric chars
            this.value = this.value.replace(/\D/g, '');
            
            if (this.value === '') {
                phoneMsg.textContent = '';
                phoneMsg.className = 'validation-hint';
                isPhoneValid = false;
            } else if (validatePhone(this.value)) {
                phoneMsg.textContent = 'Valid Nepal mobile number.';
                phoneMsg.className = 'validation-hint success';
                isPhoneValid = true;
            } else {
                phoneMsg.textContent = 'Must be 10 digits starting with 97 or 98.';
                phoneMsg.className = 'validation-hint error';
                isPhoneValid = false;
            }
            checkFormValidity();
        });
    }
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password === '') {
                if (strengthContainer) strengthContainer.innerHTML = '';
                if (reqList) reqList.style.display = 'none';
                isPasswordValid = false;
            } else {
                if (reqList) reqList.style.display = 'block';
                const { requirements, score } = checkPasswordStrength(password);
                
                // Update requirements list
                if (reqList) {
                    document.getElementById('req-length').innerHTML = requirements.length ? '✅ At least 8 characters' : '❌ At least 8 characters';
                    document.getElementById('req-upper').innerHTML = requirements.upper ? '✅ 1 uppercase letter' : '❌ 1 uppercase letter';
                    document.getElementById('req-lower').innerHTML = requirements.lower ? '✅ 1 lowercase letter' : '❌ 1 lowercase letter';
                    document.getElementById('req-number').innerHTML = requirements.number ? '✅ 1 number' : '❌ 1 number';
                    document.getElementById('req-special').innerHTML = requirements.special ? '✅ 1 special character' : '❌ 1 special character';
                    
                    for (let key in requirements) {
                        const el = document.getElementById('req-' + key);
                        if (el) {
                            el.className = requirements[key] ? 'req-item pass' : 'req-item';
                        }
                    }
                }
                
                // Update strength bar
                if (strengthContainer) {
                    let color = '#ef4444'; // Red
                    let text = 'Weak';
                    let width = '33%';
                    
                    if (score >= 3 && score < 5) {
                        color = '#f59e0b'; // Amber
                        text = 'Fair';
                        width = '66%';
                    } else if (score === 5) {
                        color = '#10b981'; // Green
                        text = 'Strong';
                        width = '100%';
                    }
                    
                    strengthContainer.innerHTML = `
                        <div style="height: 6px; background-color: #e5e7eb; border-radius: 3px; overflow: hidden; margin-bottom: 5px;">
                            <div style="height: 100%; width: ${width}; background-color: ${color}; transition: all 0.3s ease;"></div>
                        </div>
                        <div style="font-size: 0.8rem; color: ${color}; font-weight: 500;">Password Strength: ${text}</div>
                    `;
                }
                
                isPasswordValid = score === 5;
            }
            
            // Re-trigger confirm match check if confirm input has value
            if (confirmInput && confirmInput.value !== '') {
                confirmInput.dispatchEvent(new Event('input'));
            } else {
                checkFormValidity();
            }
        });
    }
    
    if (confirmInput && passwordInput && matchMsg) {
        confirmInput.addEventListener('input', function() {
            if (this.value === '') {
                matchMsg.textContent = '';
                matchMsg.className = 'validation-hint';
                isMatchValid = false;
            } else if (this.value === passwordInput.value) {
                matchMsg.textContent = 'Passwords match.';
                matchMsg.className = 'validation-hint success';
                isMatchValid = true;
            } else {
                matchMsg.textContent = 'Passwords do not match.';
                matchMsg.className = 'validation-hint error';
                isMatchValid = false;
            }
            checkFormValidity();
        });
    }
    
    function checkFormValidity() {
        if (!submitBtn) return;
        
        let isValid = true;
        
        if (registerForm) {
            isValid = isEmailValid && isPhoneValid && isPasswordValid && isMatchValid;
        } else if (resetForm) {
            isValid = isPasswordValid && isMatchValid;
        }
        
        if (isValid) {
            submitBtn.removeAttribute('disabled');
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
        }
    }
    
    // Prevent form submission if disabled (extra safeguard)
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            if (submitBtn.hasAttribute('disabled')) {
                e.preventDefault();
            }
        });
    }
    if (resetForm) {
        resetForm.addEventListener('submit', function(e) {
            if (submitBtn.hasAttribute('disabled')) {
                e.preventDefault();
            }
        });
    }
});
