// js/main.js
// Client-side interactions for HAMROFUTSAL

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Menu Navigation Toggle
    const mobileToggle = document.getElementById('mobile-toggle');
    const navLinks = document.getElementById('nav-links');
    const navButtons = document.getElementById('nav-buttons');

    if (mobileToggle) {
        mobileToggle.addEventListener('click', function () {
            // Toggle active/show classes on click
            navLinks.classList.toggle('show');
            
            // Adjust the toggle button visual state (change to X icon or active look)
            this.classList.toggle('active');
            
            // Toggle visibility of navigation action buttons
            if (navButtons) {
                navButtons.classList.toggle('show');
            }
        });
    }

    // 2. Automatically hide flash messages after 5 seconds to keep dashboard clean
    const flashAlert = document.getElementById('flash-alert');
    if (flashAlert) {
        setTimeout(function () {
            // Smoothly fade out using CSS opacity (student-friendly transition)
            flashAlert.style.transition = 'opacity 0.5s ease';
            flashAlert.style.opacity = '0';
            setTimeout(function () {
                flashAlert.style.display = 'none';
            }, 500);
        }, 5000);
    }
});
