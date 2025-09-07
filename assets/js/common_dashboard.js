document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const menuToggle = document.getElementById('menu-toggle'); // Get the menu-toggle button

    // Function to set initial state based on screen size
    const setInitialState = () => {
        if (window.innerWidth > 768) {
            // Desktop: Start with sidebar expanded, main content next to it
            sidebar.classList.remove('collapsed', 'open');
            mainContent.classList.remove('shifted'); // No shift initially if expanded
            document.body.classList.remove('no-scroll');
            // Hide menu toggle on desktop
            if (menuToggle) menuToggle.style.display = 'none';
        } else {
            // Mobile: Start with sidebar hidden, main content full width
            sidebar.classList.remove('open', 'collapsed'); 
            mainContent.classList.remove('shifted');
            document.body.classList.remove('no-scroll');
            // Show menu toggle on mobile
            if (menuToggle) menuToggle.style.display = 'block';
        }
    };
    
    // Set initial state on load
    setInitialState();

    // Event listener for menu toggle click
    if (menuToggle) { // Ensure menuToggle exists before adding listener
        menuToggle.addEventListener('click', function() {
            if (window.innerWidth <= 768) { // Mobile view
                sidebar.classList.toggle('open');
                document.body.classList.toggle('no-scroll'); // Prevent body scroll
            } else { // Desktop view
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('shifted');
            }
        });
    }


    // Close sidebar if clicking outside on mobile when open
    window.addEventListener('click', function(event) {
        if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
            // Check if click is outside sidebar and not on the menu toggle itself
            if (!sidebar.contains(event.target) && (!menuToggle || !menuToggle.contains(event.target)) && !event.target.closest('#menu-toggle')) {
                sidebar.classList.remove('open');
                document.body.classList.remove('no-scroll');
            }
        }
    });

    // Adjust sidebar on window resize
    window.addEventListener('resize', function() {
        setInitialState(); // Recalculate state on resize
    });
});