// Main JavaScript file for BusBD website

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navList = document.querySelector('.nav-list');
    
    if (mobileMenuToggle && navList) {
        mobileMenuToggle.addEventListener('click', function() {
            navList.style.display = navList.style.display === 'flex' ? 'none' : 'flex';
        });
    }

    // Animate statistics on scroll
    const statNumbers = document.querySelectorAll('.stat-number');
    
    function animateStats() {
        statNumbers.forEach(stat => {
            const target = parseInt(stat.getAttribute('data-target'));
            const increment = target / 100;
            let current = 0;
            
            const updateStat = () => {
                current += increment;
                if (current < target) {
                    stat.textContent = Math.ceil(current).toLocaleString();
                    requestAnimationFrame(updateStat);
                } else {
                    stat.textContent = target.toLocaleString();
                }
            };
            
            updateStat();
        });
    }

    // Intersection Observer for stats animation
    const statsSection = document.querySelector('.stats');
    if (statsSection) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateStats();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        observer.observe(statsSection);
    }

    // Book route buttons
    const bookRouteButtons = document.querySelectorAll('.book-route-btn');
    bookRouteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const route = this.getAttribute('data-route');
            // Store route in localStorage for booking page
            localStorage.setItem('selectedRoute', route);
            // Redirect to booking page
            window.location.href = 'booking.html';
        });
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });

    function validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = '#dc2626';
                isValid = false;
            } else {
                field.style.borderColor = '#fed7d7';
            }
        });
        
        return isValid;
    }

    // Add hover effects to cards
    const cards = document.querySelectorAll('.feature-card, .route-card, .recommendation-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Auto-update time displays
    function updateTimeDisplays() {
        const timeElements = document.querySelectorAll('.last-updated');
        timeElements.forEach(element => {
            const currentTime = new Date();
            const timeString = currentTime.toLocaleTimeString();
            element.textContent = `Last updated: ${timeString}`;
        });
    }

    // Update time every minute
    setInterval(updateTimeDisplays, 60000);
    updateTimeDisplays();

    // Initialize tooltips if any
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.position = 'absolute';
            tooltip.style.backgroundColor = '#1f2937';
            tooltip.style.color = 'white';
            tooltip.style.padding = '0.5rem';
            tooltip.style.borderRadius = '5px';
            tooltip.style.fontSize = '0.875rem';
            tooltip.style.zIndex = '1000';
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });

    // Initialize any other interactive elements
    initializeInteractiveElements();
});

function initializeInteractiveElements() {
    // Add any additional interactive element initialization here
    console.log('BusBD website initialized successfully');
}

// Utility functions
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.padding = '1rem';
    notification.style.borderRadius = '5px';
    notification.style.color = 'white';
    notification.style.zIndex = '10000';
    notification.style.backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('bn-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('en-BD', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(date);
}

// Export functions for global use
window.showNotification = showNotification;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;