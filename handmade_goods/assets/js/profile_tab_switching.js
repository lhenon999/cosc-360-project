// Tab switching functionality
document.addEventListener('DOMContentLoaded', function () {
    const tabLinks = document.querySelectorAll('.tabs-nav a');
    const tabContents = document.querySelectorAll('.tab-pane');

    function switchTab(e) {
        e.preventDefault();
        const targetId = e.target.getAttribute('href').slice(1);

        const targetTab = document.getElementById(targetId);

        if (!targetTab) {
            console.error(`Tab with ID "${targetId}" not found.`);
            return; // Stop execution if tab does not exist
        }

        // Update active states
        tabLinks.forEach(link => link.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));

        e.target.classList.add('active');
        document.getElementById(targetId).classList.add('active');

        // Update URL hash without scrolling
        history.pushState(null, null, '#' + targetId);
    }

    tabLinks.forEach(link => {
        link.addEventListener('click', switchTab);
    });

    // Handle initial load with hash
    if (window.location.hash) {
        const targetLink = document.querySelector(`a[href="${window.location.hash}"]`);
        if (targetLink) {
            targetLink.click();
        }
    }
});