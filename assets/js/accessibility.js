// Accessibility enhancements for feed validator

/**
 * Sets loading state on submit button
 */
function setLoadingState(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.setAttribute('aria-busy', 'true');
        submitBtn.textContent = submitBtn.getAttribute('data-aria-busy-text') || 'Validating...';
        submitBtn.disabled = true;
    }
}

/**
 * Resets loading state on submit button
 */
function resetLoadingState(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.setAttribute('aria-busy', 'false');
        submitBtn.textContent = 'Validate Feed';
        submitBtn.disabled = false;
    }
}

/**
 * Shows an error message for a specific field
 */
function showFieldError(fieldId, message) {
    const errorDiv = document.getElementById(fieldId + '-error');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        errorDiv.setAttribute('aria-live', 'assertive');
    }
}

/**
 * Hides error message for a specific field
 */
function hideFieldError(fieldId) {
    const errorDiv = document.getElementById(fieldId + '-error');
    if (errorDiv) {
        errorDiv.textContent = '';
        errorDiv.style.display = 'none';
    }
}

/**
 * Clears all field errors
 */
function clearAllFieldErrors(form) {
    const errorDivs = form.querySelectorAll('.wpmr-pfv-error');
    errorDivs.forEach(div => {
        div.textContent = '';
        div.style.display = 'none';
    });
}

/**
 * Validates form fields and shows appropriate errors
 */
function validateFormFields(form) {
    let isValid = true;
    let firstErrorField = null;

    // Clear previous errors
    clearAllFieldErrors(form);

    // Validate URL
    const urlField = form.querySelector('#wpmr-pfv-url');
    if (urlField && urlField.required) {
        if (!urlField.value.trim()) {
            showFieldError('wpmr-pfv-url', 'Feed URL is required.');
            isValid = false;
            if (!firstErrorField) firstErrorField = urlField;
        } else if (!isValidUrl(urlField.value.trim())) {
            showFieldError('wpmr-pfv-url', 'Please enter a valid URL starting with http:// or https://.');
            isValid = false;
            if (!firstErrorField) firstErrorField = urlField;
        }
    }

    // Validate email (if present)
    const emailField = form.querySelector('#wpmr-pfv-email');
    if (emailField && emailField.required) {
        if (!emailField.value.trim()) {
            showFieldError('wpmr-pfv-email', 'Email address is required.');
            isValid = false;
            if (!firstErrorField) firstErrorField = emailField;
        } else if (!isValidEmail(emailField.value.trim())) {
            showFieldError('wpmr-pfv-email', 'Please enter a valid email address.');
            isValid = false;
            if (!firstErrorField) firstErrorField = emailField;
        }
    }

    // Validate consent (if present)
    const consentField = form.querySelector('#wpmr-pfv-consent');
    if (consentField && consentField.required && !consentField.checked) {
        showFieldError('wpmr-pfv-consent', 'You must consent to receive the validation report via email.');
        isValid = false;
        if (!firstErrorField) firstErrorField = consentField;
    }

    // Focus first error field
    if (firstErrorField) {
        setTimeout(() => {
            firstErrorField.focus();
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }

    return isValid;
}

/**
 * Simple URL validation
 */
function isValidUrl(string) {
    try {
        const url = new URL(string);
        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch (_) {
        return false;
    }
}

/**
 * Simple email validation
 */
function isValidEmail(string) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(string);
}

/**
 * Announces content changes to screen readers
 */
function announceToScreenReader(message, priority = 'polite') {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', priority);
    announcement.setAttribute('aria-atomic', 'true');
    announcement.style.position = 'absolute';
    announcement.style.left = '-10000px';
    announcement.style.width = '1px';
    announcement.style.height = '1px';
    announcement.style.overflow = 'hidden';

    document.body.appendChild(announcement);
    announcement.textContent = message;

    setTimeout(() => {
        document.body.removeChild(announcement);
    }, 1000);
}

/**
 * Handles keyboard navigation for better accessibility
 */
function enhanceKeyboardNavigation(form) {
    const focusableElements = form.querySelectorAll('input, button, select, textarea');

    focusableElements.forEach(element => {
        element.addEventListener('keydown', function(e) {
            // Enter key on inputs should not submit if there are validation errors
            if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'submit') {
                e.preventDefault();
                // Move to next field
                const currentIndex = Array.from(focusableElements).indexOf(e.target);
                const nextElement = focusableElements[currentIndex + 1];
                if (nextElement) {
                    nextElement.focus();
                }
            }
        });
    });
}

/**
 * Initializes accessibility enhancements
 */
function initAccessibility(form) {
    // Enhance keyboard navigation
    enhanceKeyboardNavigation(form);

    // Add form validation on submit
    form.addEventListener('submit', function(e) {
        if (!validateFormFields(form)) {
            e.preventDefault();
            announceToScreenReader('Form contains errors. Please correct them and try again.', 'assertive');
            return false;
        }
    });

    // Clear errors when user starts typing
    const inputs = form.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const errorId = this.id + '-error';
            hideFieldError(this.id.replace('-', '-'));
        });
    });

    // Handle CAPTCHA errors
    const captchaContainer = form.querySelector('#wpmr-pfv-captcha-group');
    if (captchaContainer) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    const errorElement = captchaContainer.querySelector('.error, .invalid');
                    if (errorElement) {
                        showFieldError('wpmr-pfv-captcha', errorElement.textContent || 'CAPTCHA verification failed.');
                    }
                }
            });
        });
        observer.observe(captchaContainer, { childList: true, subtree: true });
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.wpmr-pfv-form');
    forms.forEach(form => {
        initAccessibility(form);
    });
});
