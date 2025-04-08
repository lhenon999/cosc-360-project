document.addEventListener("DOMContentLoaded", function () {
    const tabLinks = document.querySelectorAll(".tabs-nav a");
    const tabContents = document.querySelectorAll(".tab-pane");

    function switchTab(targetId) {
        const targetTab = document.getElementById(targetId);
        if (!targetTab) return;

        tabContents.forEach(tab => {
            tab.style.transition = "none";
            tab.style.opacity = "0";
            tab.style.transform = "translateX(50px)";
            tab.style.visibility = "hidden";
            tab.style.display = "none";
            tab.classList.remove("active");
        });

        targetTab.style.display = "block";
        targetTab.style.visibility = "visible";
        targetTab.style.opacity = "0";
        targetTab.style.transform = "translateX(50px)";
        targetTab.offsetHeight; // force reflow
        targetTab.style.transition = "opacity 0.6s ease-in-out, transform 0.6s ease-out";
        targetTab.style.opacity = "1";
        targetTab.style.transform = "translateX(0)";
        targetTab.classList.add("active");

        history.pushState(null, null, `#${targetId}`);

        tabLinks.forEach(link => link.classList.remove("active"));
        const navLink = document.querySelector(`.tabs-nav a[href="#${targetId}"]`);
        if (navLink) {
            navLink.classList.add("active");
        }
    }

    tabLinks.forEach(link => {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            const targetId = this.getAttribute("href").substring(1);
            switchTab(targetId);
        });
    });

    function setActiveTabFromURL() {
        if (window.location.hash) {
            const targetId = window.location.hash.substring(1);
            switchTab(targetId);
        } else {
            switchTab(tabContents[0].id);
        }
    }
    setActiveTabFromURL();

    window.switchToActivity = function() {
        switchTab("activity");
    };

    window.switchToReviews = function() {
        switchTab("reviews");
    };
});

//slider behaviours
document.addEventListener("DOMContentLoaded", function () {
    const tabLinks = document.querySelectorAll(".tabs-nav .tab-link");
    const slider = document.querySelector(".tab-slider") || document.querySelector(".tab-slider-admin");
    let activeTab = document.querySelector(".tabs-nav .tab-link.active");

    if (!slider) {
        console.warn("Warning: No .tab-slider found, skipping slider updates.");
        return;
    }

    function updateSliderPosition(tab) {
        if (tab && slider) {
            const tabIndex = [...tabLinks].indexOf(tab);
            slider.style.transform = `translateX(${tabIndex * 100}%)`;
        }
    }

    tabLinks.forEach((tab) => {
        tab.addEventListener("mouseover", function () {
            updateSliderPosition(this);
        });

        tab.addEventListener("click", function (e) {
            e.preventDefault();

            tabLinks.forEach((t) => t.classList.remove("active"));
            this.classList.add("active");

            activeTab = this;
            updateSliderPosition(activeTab);
        });
    });

    document.querySelector(".tabs-nav").addEventListener("mouseleave", function () {
        updateSliderPosition(activeTab);
    });

    updateSliderPosition(activeTab);
});


$(document).ready(function () {
    $('.m-btn.g').on('click', function (e) {
        e.preventDefault();
        $('#activity').load('my_activity.php', function (response, status, xhr) {
            if (status === "error") {
                $('#activity').html('<p>Error loading activity content.</p>');
            }
            window.switchToActivity();
        });
    });

    $('#activity .m-btn').on('click', function (e) {
        e.preventDefault();
        console.log("Back button clicked.");
        window.switchToReviews();
    });
});
