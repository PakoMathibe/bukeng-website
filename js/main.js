// Bukeng Main JavaScript

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
    
    // Close mobile menu when clicking a link
    const mobileLinks = document.querySelectorAll('#mobile-menu a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
        });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '#/') {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
    
    // Add active class to current nav item
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('nav a');
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        if (linkPage === currentPage) {
            link.classList.add('font-bold', 'text-black');
        }
    });
    
    // Create and add scroll to top button
    createScrollToTopButton();
});

// Create Scroll to Top Button
function createScrollToTopButton() {
    // Check if button already exists
    if (document.querySelector('.scroll-top-btn')) return;
    
    // Create button element
    const scrollBtn = document.createElement('button');
    scrollBtn.className = 'scroll-top-btn';
    scrollBtn.setAttribute('aria-label', 'Scroll to top');
    scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    
    // Add to body
    document.body.appendChild(scrollBtn);
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollBtn.classList.add('show');
        } else {
            scrollBtn.classList.remove('show');
        }
    });
    
    // Scroll to top when clicked
    scrollBtn.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Alternative: Scroll to top with progress indicator
function createProgressScrollButton() {
    if (document.querySelector('.scroll-progress-btn')) return;
    
    // Create button with progress ring
    const scrollBtn = document.createElement('button');
    scrollBtn.className = 'scroll-top-btn scroll-progress-btn';
    scrollBtn.setAttribute('aria-label', 'Scroll to top');
    scrollBtn.innerHTML = `
        <svg class="progress-ring" width="50" height="50" style="position: absolute; top: -2px; left: -2px; transform: rotate(-90deg);">
            <circle class="progress-ring-circle" cx="25" cy="25" r="23" fill="none" stroke="#eab308" stroke-width="2" stroke-dasharray="144.5 144.5" stroke-dashoffset="144.5" />
        </svg>
        <i class="fas fa-arrow-up"></i>
    `;
    
    document.body.appendChild(scrollBtn);
    
    // Update progress ring on scroll
    function updateProgress() {
        const scrollTop = window.pageYOffset;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrollPercent = (scrollTop / docHeight) * 100;
        const circumference = 144.5;
        const offset = circumference - (scrollPercent / 100) * circumference;
        
        const circle = scrollBtn.querySelector('.progress-ring-circle');
        if (circle) {
            circle.style.strokeDashoffset = offset;
        }
        
        if (scrollTop > 300) {
            scrollBtn.classList.add('show');
        } else {
            scrollBtn.classList.remove('show');
        }
    }
    
    window.addEventListener('scroll', updateProgress);
    
    scrollBtn.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    updateProgress();
}

// Form validation helper
function validateForm(formData) {
    const errors = [];
    
    if (!formData.get('full_name') || formData.get('full_name').length < 2) {
        errors.push('Please enter your full name');
    }
    
    const email = formData.get('email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email || !emailRegex.test(email)) {
        errors.push('Please enter a valid email address');
    }
    
    if (!formData.get('location') || formData.get('location').length < 2) {
        errors.push('Please enter your location');
    }
    
    return errors;
}

// Analytics tracking (optional)
function trackEvent(eventName, properties = {}) {
    if (typeof gtag !== 'undefined') {
        gtag('event', eventName, properties);
    }
    console.log('Event tracked:', eventName, properties);
}

// Page view tracking
function trackPageView() {
    const page = window.location.pathname;
    trackEvent('page_view', { page_title: document.title, page_location: page });
}

// Track scroll to top usage
function trackScrollToTop() {
    const scrollBtn = document.querySelector('.scroll-top-btn');
    if (scrollBtn) {
        scrollBtn.addEventListener('click', function() {
            trackEvent('scroll_to_top', {
                page: window.location.pathname,
                scroll_position: window.pageYOffset
            });
        });
    }
}

// Call on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        trackPageView();
        trackScrollToTop();
    });
} else {
    trackPageView();
    trackScrollToTop();
}