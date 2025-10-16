// Toggle sub-navigation - THIS MUST BE FIRST
function toggleSubNav(element, event) {
    // Stop the event from bubbling up
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    
    element.classList.toggle('active');
    const subNav = element.nextElementSibling;
    if (subNav) {
        subNav.classList.toggle('active');
    }
    
    // Prevent any other handlers from firing
    return false;
}

// Mobile menu functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    if (mobileMenuBtn && sidebar && overlay) {
        // Toggle mobile menu
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
        
        // ONLY close sidebar for regular nav items and sub-nav items
        // Do NOT add listeners to .nav-parent items
        const regularNavItems = document.querySelectorAll('.nav-item:not(.nav-parent)');
        regularNavItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });
        
        // Close sidebar when clicking sub-nav items
        const subNavItems = document.querySelectorAll('.sub-nav-item');
        subNavItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });
    }
    
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
        }
    });
});

function PDFUpload() {
    window.location.href = '../Pages/PDFUpload.php';
}

function User() {
    window.location.href = '../Navbar/UserProfile.php';
}

function Progress() {
    window.location.href = '../Navbar/ProgressPage.php';
}

function CheezeWhiz() {
    window.location.href = '../Games/CheeseWhiz.php';
}

function HangWhiz() {
    window.location.href = '../Games/HangWhiz.php';
}

function SpeedWhiz() {
    window.location.href = '../Games/SpeedWhiz.php';
}

function Games() {
    window.location.href = '../Pages/Games.php';
}