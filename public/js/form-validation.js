// Custom form validation
class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        if (!this.form) return;
        
        this.form.noValidate = true; // Disable HTML5 validation
        this.form.addEventListener('submit', this.validate.bind(this));
        
        // Add input event listeners for real-time validation
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', this.validateField.bind(this));
        });
    }
    
    validate(e) {
        e.preventDefault();
        
        let isValid = true;
        const requiredFields = this.form.querySelectorAll('[data-required]');
        
        // Reset previous errors
        this.clearErrors();
        
        // Validate required fields
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        // Validate email fields
        const emailFields = this.form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showError(field, 'Please enter a valid email address');
                isValid = false;
            }
        });
        
        // Validate number fields
        const numberFields = this.form.querySelectorAll('input[type="number"]');
        numberFields.forEach(field => {
            if (field.hasAttribute('min') && parseFloat(field.value) < parseFloat(field.min)) {
                this.showError(field, `Value must be at least ${field.min}`);
                isValid = false;
            }
            if (field.hasAttribute('max') && parseFloat(field.value) > parseFloat(field.max)) {
                this.showError(field, `Value must be at most ${field.max}`);
                isValid = false;
            }
        });
        
        // Validate password match
        const password = this.form.querySelector('input[type="password"]');
        const confirmPassword = this.form.querySelector('input[data-validate-confirm]');
        if (password && confirmPassword && password.value !== confirmPassword.value) {
            this.showError(confirmPassword, 'Passwords do not match');
            isValid = false;
        }
        
        if (isValid) {
            this.form.submit();
        }
        
        return false;
    }
    
    validateField(e) {
        const field = e.target || e;
        const value = field.value.trim();
        let isValid = true;
        
        // Clear previous error
        this.clearError(field);
        
        // Check required fields
        if (field.hasAttribute('data-required') && !value) {
            this.showError(field, 'This field is required');
            isValid = false;
        }
        
        // Check min length
        if (field.hasAttribute('data-min-length') && value.length < parseInt(field.getAttribute('data-min-length'))) {
            this.showError(field, `Must be at least ${field.getAttribute('data-min-length')} characters`);
            isValid = false;
        }
        
        // Check max length
        if (field.hasAttribute('data-max-length') && value.length > parseInt(field.getAttribute('data-max-length'))) {
            this.showError(field, `Must be at most ${field.getAttribute('data-max-length')} characters`);
            isValid = false;
        }
        
        // Check pattern
        if (field.hasAttribute('data-pattern')) {
            const pattern = new RegExp(field.getAttribute('data-pattern'));
            if (value && !pattern.test(value)) {
                this.showError(field, field.getAttribute('data-pattern-message') || 'Invalid format');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    showError(field, message) {
        // Add error class to field
        field.classList.add('is-invalid');
        
        // Create error message element if it doesn't exist
        let errorElement = field.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('invalid-feedback')) {
            errorElement = document.createElement('div');
            errorElement.className = 'invalid-feedback';
            field.parentNode.insertBefore(errorElement, field.nextSibling);
        }
        
        errorElement.textContent = message;
    }
    
    clearError(field) {
        field.classList.remove('is-invalid');
        const errorElement = field.nextElementSibling;
        if (errorElement && errorElement.classList.contains('invalid-feedback')) {
            errorElement.remove();
        }
    }
    
    clearErrors() {
        const errorMessages = this.form.querySelectorAll('.invalid-feedback');
        errorMessages.forEach(error => error.remove());
        
        const invalidFields = this.form.querySelectorAll('.is-invalid');
        invalidFields.forEach(field => field.classList.remove('is-invalid'));
    }
    
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
}

// Auto-initialize on all forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        if (form.id) {
            new FormValidator(form.id);
        }
    });
});
