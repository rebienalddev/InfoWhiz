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
    
    // Function to check if we're in mobile view
    function isMobileView() {
        return window.innerWidth <= 768;
    }
    
    // Toggle mobile menu - only works if elements exist
    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (isMobileView()) {
                sidebar.classList.toggle('active');
                if (overlay) overlay.classList.toggle('active');
            }
        });
    }
    
    // Overlay click handler
    if (overlay && sidebar) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
    
    // Close sidebar for regular nav items (not nav-parent items)
    const regularNavItems = document.querySelectorAll('.nav-item:not(.nav-parent)');
    regularNavItems.forEach(item => {
        item.addEventListener('click', function() {
            if (isMobileView() && sidebar) {
                sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
            }
        });
    });
    
    // Close sidebar when clicking sub-nav items
    const subNavItems = document.querySelectorAll('.sub-nav-item');
    subNavItems.forEach(item => {
        item.addEventListener('click', function() {
            if (isMobileView() && sidebar) {
                sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
            }
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (!isMobileView() && sidebar) {
            sidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
        }
    });
});

function ChatBot() {
    window.location.href = '../Pages/ChatBot.php';
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

function PDFLibrary() {
    window.location.href = '../Pages/PDFLibrary.php';
}

function ChatBot2() {
    window.location.href = '../Pages/ChatBot.php';
}




function HomePage() {
    window.location.href = '../Pages/HomePage.php';
}

function GameLibrary() {
    window.location.href = '../Pages/GameLibrary.php';
}