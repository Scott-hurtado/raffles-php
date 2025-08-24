// Admin Login Controller
class AdminLoginController {
    constructor() {
        this.form = document.getElementById('adminLoginForm');
        this.usernameInput = document.getElementById('username');
        this.passwordInput = document.getElementById('password');
        this.togglePasswordBtn = document.getElementById('togglePassword');
        this.rememberMeCheckbox = document.getElementById('rememberMe');
        this.loginBtn = document.getElementById('loginBtn');
        this.errorMessage = document.getElementById('errorMessage');

        this.initializeEventListeners();
        this.initializeValidation();
    }

    initializeEventListeners() {
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));

        // Password toggle
        this.togglePasswordBtn.addEventListener('click', () => this.togglePasswordVisibility());

        // Input validation on blur
        this.usernameInput.addEventListener('blur', () => this.validateUsername());
        this.passwordInput.addEventListener('blur', () => this.validatePassword());

        // Real-time input feedback
        this.usernameInput.addEventListener('input', () => this.clearValidationState(this.usernameInput));
        this.passwordInput.addEventListener('input', () => this.clearValidationState(this.passwordInput));

        // Keyboard navigation
        this.form.addEventListener('keydown', (e) => this.handleKeyNavigation(e));

        // Remember me functionality
        this.loadRememberedCredentials();
    }

    initializeValidation() {
        // Initialize form validation states
        this.validation = {
            username: false,
            password: false
        };

        this.hideError();
    }

    handleFormSubmit(event) {
        event.preventDefault();
        
        // Validate form
        const isUsernameValid = this.validateUsername();
        const isPasswordValid = this.validatePassword();

        if (!isUsernameValid || !isPasswordValid) {
            this.showError('Por favor, completa todos los campos correctamente.');
            return;
        }

        this.performLogin();
    }

    validateUsername() {
        const username = this.usernameInput.value.trim();
        const formGroup = this.usernameInput.closest('.form-group');

        if (!username) {
            this.setValidationState(formGroup, 'error');
            this.validation.username = false;
            return false;
        }

        if (username.length < 3) {
            this.setValidationState(formGroup, 'error');
            this.validation.username = false;
            return false;
        }

        this.setValidationState(formGroup, 'success');
        this.validation.username = true;
        return true;
    }

    validatePassword() {
        const password = this.passwordInput.value;
        const formGroup = this.passwordInput.closest('.form-group');

        if (!password) {
            this.setValidationState(formGroup, 'error');
            this.validation.password = false;
            return false;
        }

        if (password.length < 6) {
            this.setValidationState(formGroup, 'error');
            this.validation.password = false;
            return false;
        }

        this.setValidationState(formGroup, 'success');
        this.validation.password = true;
        return true;
    }

    setValidationState(formGroup, state) {
        formGroup.classList.remove('error', 'success');
        if (state !== 'clear') {
            formGroup.classList.add(state);
        }
    }

    clearValidationState(input) {
        const formGroup = input.closest('.form-group');
        this.setValidationState(formGroup, 'clear');
    }

    async performLogin() {
        const username = this.usernameInput.value.trim();
        const password = this.passwordInput.value;
        const remember = this.rememberMeCheckbox.checked;

        this.setLoadingState(true);
        this.hideError();

        try {
            // Simulate API call
            const response = await this.makeLoginRequest(username, password, remember);

            if (response.success) {
                this.handleLoginSuccess(response, remember);
            } else {
                this.handleLoginError(response.message);
            }
        } catch (error) {
            this.handleLoginError('Error de conexión. Inténtalo de nuevo.');
            console.error('Login error:', error);
        } finally {
            this.setLoadingState(false);
        }
    }

    async makeLoginRequest(username, password, remember) {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 2000));

        // Mock authentication logic
        const mockValidCredentials = {
            'admin': 'admin123',
            'superadmin': 'super123',
            'moderator': 'mod123'
        };

        if (mockValidCredentials[username] && mockValidCredentials[username] === password) {
            return {
                success: true,
                user: {
                    id: Math.floor(Math.random() * 1000),
                    username: username,
                    role: username === 'superadmin' ? 'Super Admin' : username === 'admin' ? 'Admin' : 'Moderator',
                    token: 'mock_jwt_token_' + Date.now()
                }
            };
        }

        return {
            success: false,
            message: 'Credenciales incorrectas. Verifica tu usuario y contraseña.'
        };
    }

    handleLoginSuccess(response, remember) {
        console.log('Login successful:', response);

        // Store user data
        sessionStorage.setItem('admin_user', JSON.stringify(response.user));
        
        if (remember) {
            this.saveCredentials();
        } else {
            this.clearSavedCredentials();
        }

        // Show success feedback
        this.showSuccessMessage('¡Login exitoso! Redirigiendo...');

        // Redirect after delay
        setTimeout(() => {
            window.location.href = '/admin/dashboard';
        }, 1500);
    }

    handleLoginError(message) {
        this.showError(message);
        this.passwordInput.value = '';
        this.passwordInput.focus();
    }

    setLoadingState(isLoading) {
        this.loginBtn.disabled = isLoading;
        
        if (isLoading) {
            this.loginBtn.classList.add('loading');
        } else {
            this.loginBtn.classList.remove('loading');
        }

        // Disable form inputs during loading
        this.usernameInput.disabled = isLoading;
        this.passwordInput.disabled = isLoading;
        this.rememberMeCheckbox.disabled = isLoading;
    }

    togglePasswordVisibility() {
        const isPassword = this.passwordInput.type === 'password';
        
        this.passwordInput.type = isPassword ? 'text' : 'password';
        
        // Update icon
        const eyeIcon = this.togglePasswordBtn.querySelector('.eye-icon');
        if (isPassword) {
            eyeIcon.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94L17.94 17.94z"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19L9.9 4.24z"/>
                <circle cx="12" cy="12" r="3"/>
            `;
        } else {
            eyeIcon.innerHTML = `
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            `;
        }
    }

    showError(message) {
        const errorText = this.errorMessage.querySelector('.error-text');
        errorText.textContent = message;
        this.errorMessage.style.display = 'flex';
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            this.hideError();
        }, 5000);
    }

    hideError() {
        this.errorMessage.style.display = 'none';
    }

    showSuccessMessage(message) {
        // Create temporary success message
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.innerHTML = `
            <svg class="success-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22,4 12,14.01 9,11.01"/>
            </svg>
            <span class="success-text">${message}</span>
        `;

        // Add styles
        successDiv.style.cssText = `
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease-out;
        `;

        const successIcon = successDiv.querySelector('.success-icon');
        successIcon.style.cssText = `
            width: 20px;
            height: 20px;
            color: #10b981;
            flex-shrink: 0;
        `;

        const successText = successDiv.querySelector('.success-text');
        successText.style.cssText = `
            color: #10b981;
            font-size: 0.9rem;
            font-weight: 500;
        `;

        // Insert before login button
        this.form.insertBefore(successDiv, this.loginBtn);

        // Remove after 3 seconds
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.remove();
            }
        }, 3000);
    }

    saveCredentials() {
        const credentials = {
            username: this.usernameInput.value.trim(),
            timestamp: Date.now()
        };
        localStorage.setItem('admin_remembered_credentials', JSON.stringify(credentials));
    }

    loadRememberedCredentials() {
        const saved = localStorage.getItem('admin_remembered_credentials');
        if (saved) {
            try {
                const credentials = JSON.parse(saved);
                const daysSinceLogin = (Date.now() - credentials.timestamp) / (1000 * 60 * 60 * 24);
                
                // Only remember for 30 days
                if (daysSinceLogin < 30) {
                    this.usernameInput.value = credentials.username;
                    this.rememberMeCheckbox.checked = true;
                } else {
                    this.clearSavedCredentials();
                }
            } catch (error) {
                console.error('Error loading saved credentials:', error);
                this.clearSavedCredentials();
            }
        }
    }

    clearSavedCredentials() {
        localStorage.removeItem('admin_remembered_credentials');
    }

    handleKeyNavigation(event) {
        // Enter key handling
        if (event.key === 'Enter') {
            const activeElement = document.activeElement;
            
            if (activeElement === this.usernameInput) {
                event.preventDefault();
                this.passwordInput.focus();
            } else if (activeElement === this.passwordInput) {
                event.preventDefault();
                if (!this.loginBtn.disabled) {
                    this.form.dispatchEvent(new Event('submit'));
                }
            }
        }

        // Escape key to clear form
        if (event.key === 'Escape') {
            this.clearForm();
        }
    }

    clearForm() {
        this.usernameInput.value = '';
        this.passwordInput.value = '';
        this.rememberMeCheckbox.checked = false;
        this.hideError();
        
        // Clear validation states
        this.form.querySelectorAll('.form-group').forEach(group => {
            this.setValidationState(group, 'clear');
        });
        
        this.usernameInput.focus();
    }

    // Public methods for external access
    getFormData() {
        return {
            username: this.usernameInput.value.trim(),
            password: this.passwordInput.value,
            remember: this.rememberMeCheckbox.checked
        };
    }

    setFormData(data) {
        if (data.username) this.usernameInput.value = data.username;
        if (data.password) this.passwordInput.value = data.password;
        if (typeof data.remember === 'boolean') this.rememberMeCheckbox.checked = data.remember;
    }

    resetForm() {
        this.clearForm();
    }

    showCustomError(message) {
        this.showError(message);
    }
}

// Utility functions
const AdminLoginUtils = {
    // Validate email format
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // Validate username format
    isValidUsername(username) {
        const usernameRegex = /^[a-zA-Z0-9_-]{3,20}$/;
        return usernameRegex.test(username);
    },

    // Password strength checker
    checkPasswordStrength(password) {
        const checks = {
            length: password.length >= 8,
            lowercase: /[a-z]/.test(password),
            uppercase: /[A-Z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        const score = Object.values(checks).filter(Boolean).length;
        
        return {
            score,
            checks,
            strength: score < 2 ? 'weak' : score < 4 ? 'medium' : 'strong'
        };
    },

    // Debounce function for input validation
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Generate secure random token
    generateToken(length = 32) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the admin login controller
    window.adminLogin = new AdminLoginController();
    
    // Add additional styles for animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .success-message {
            animation: slideDown 0.3s ease-out;
        }
    `;
    document.head.appendChild(style);
    
    // Set focus to username input
    setTimeout(() => {
        const usernameInput = document.getElementById('username');
        if (usernameInput && !usernameInput.value) {
            usernameInput.focus();
        }
    }, 100);
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AdminLoginController, AdminLoginUtils };
}