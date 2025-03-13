document.addEventListener("DOMContentLoaded", function () {

    const tabLinks = document.querySelectorAll(".tabs-nav a");
    const tabContents = document.querySelectorAll(".tab-pane");

    function switchTab(event) {
        event.preventDefault();

        const targetId = event.target.getAttribute("href").substring(1);
        const targetTab = document.getElementById(targetId);

        tabLinks.forEach(link => link.classList.remove("active"));
        tabContents.forEach(content => {
            content.style.display = "none";
            content.classList.remove("active");
        });

        event.target.classList.add("active");
        targetTab.style.display = "block";
        targetTab.classList.add("active");

        history.pushState(null, null, `#${targetId}`);
    }

    tabLinks.forEach(link => {
        link.addEventListener("click", switchTab);
    });

    if (window.location.hash) {
        const activeTab = document.querySelector(`a[href="${window.location.hash}"]`);
        if (activeTab) {
            activeTab.classList.add("active");
            document.getElementById(activeTab.getAttribute("href").substring(1)).style.display = "block";
            document.getElementById(activeTab.getAttribute("href").substring(1)).classList.add("active");
        }
    } else {
        tabLinks[0].classList.add("active");
        tabContents[0].style.display = "block";
        tabContents[0].classList.add("active");
    }
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









