// Premier Bank - Elegant Interactions

document.addEventListener('DOMContentLoaded', function() {
    initializeSessionMonitor();
    initializeAnimations();
    initializeFormValidation();
});

// Session timeout monitor
function initializeSessionMonitor() {
    let sessionTimeout;
    const timeoutDuration = 60000; // 60 seconds
    
    function resetTimer() {
        clearTimeout(sessionTimeout);
        sessionTimeout = setTimeout(handleSessionTimeout, timeoutDuration);
    }
    
    function handleSessionTimeout() {
        window.location.href = '/premier-bank/entrance/exit.php?timeout=1';
    }
    
    // Reset timer on user activity
    ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
        document.addEventListener(event, resetTimer);
    });
    
    resetTimer();
}

// Smooth animations
function initializeAnimations() {
    // Animate balance display
    const balanceElements = document.querySelectorAll('.balance-amount');
    balanceElements.forEach(el => {
        if (el.dataset.amount) {
            animateValue(el, 0, parseFloat(el.dataset.amount), 2000);
        }
    });
    
    // Fade in elements
    const fadeElements = document.querySelectorAll('.fade-in');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    fadeElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
}

// Animate number values
function animateValue(element, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const current = progress * (end - start) + start;
        element.textContent = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2
        }).format(current);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('.form-elegant');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const amountFields = form.querySelectorAll('input[type="number"]');
            let isValid = true;
            
            amountFields.forEach(field => {
                if (field.value && parseFloat(field.value) <= 0) {
                    showError(field, 'Amount must be greater than zero');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

// Show error message
function showError(element, message) {
    const existingError = element.parentElement.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.cssText = `
        color: var(--error-red);
        font-size: 0.8rem;
        margin-top: 5px;
        letter-spacing: 1px;
    `;
    errorDiv.textContent = message;
    element.parentElement.appendChild(errorDiv);
    element.style.borderColor = 'var(--error-red)';
    
    setTimeout(() => {
        errorDiv.remove();
        element.style.borderColor = '';
    }, 3000);
}

// Circular progress animation
function updateCircularProgress(percentage) {
    const circle = document.querySelector('.progress-circle');
    if (circle) {
        const radius = circle.r.baseVal.value;
        const circumference = radius * 2 * Math.PI;
        circle.style.strokeDasharray = `${circumference} ${circumference}`;
        circle.style.strokeDashoffset = circumference;
        
        const offset = circumference - (percentage / 100) * circumference;
        circle.style.strokeDashoffset = offset;
    }
}

// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copied to clipboard');
    });
}

// Show notification
function showNotification(message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--midnight-black);
        color: var(--marble-white);
        padding: 15px 25px;
        font-family: 'Playfair Display', Georgia, serif;
        letter-spacing: 1px;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.style.opacity = '1', 100);
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Confirm before important actions
function confirmAction(message) {
    return confirm(message);
}

// Format date elegantly
function formatDate(dateString) {
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('en-US', options);
}