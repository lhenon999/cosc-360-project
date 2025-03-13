document.addEventListener("DOMContentLoaded", function () {
    const tabContents = document.querySelectorAll('.tab-pane');
    tabContents.forEach(content => content.style.display = "none");

    let activeTabId = null;

    setTimeout(() => {
        if (typeof userType !== "undefined" && typeof itemName !== "undefined" && userType === "admin" && itemName) {
            switchTab("listings");
            activeTabId = "listings";

            let listingsSearch = document.getElementById("listingsSearch");
            if (listingsSearch) {
                listingsSearch.value = itemName;
                filterTable('listingsTable', 'listingsSearch');
            }

            history.replaceState(null, null, "#listings");

        } else if (typeof userType !== "undefined" && typeof itemName !== "undefined" && userType === "admin" && userName) {
            switchTab("users");
            activeTabId = "users";

            let userSearch = document.getElementById("userSearch");
            if (userSearch) {
                userSearch.value = userName;
                filterTable('usersTable', 'userSearch');
            }

            history.replaceState(null, null, "#users");
        }

        if (activeTabId) {
            updateSliderPosition(activeTabId);
        }

    }, 300);
});

function switchTab(tabId) {
    const tabLinks = document.querySelectorAll('.tabs-nav a');
    const tabContents = document.querySelectorAll('.tab-pane');
    const slider = document.querySelector(".tab-slider") || document.querySelector(".tab-slider-admin");

    tabLinks.forEach(link => link.classList.remove('active'));
    tabContents.forEach(content => {
        content.classList.remove('active');
        content.style.display = "none";
    });

    let targetLink = document.querySelector(`.tabs-nav a[href="#${tabId}"]`);
    let targetTab = document.getElementById(tabId);

    if (targetLink && targetTab) {
        targetLink.classList.add('active');
        targetTab.classList.add('active');
        targetTab.style.display = "block";

        activeTabId = tabId;

        updateSliderPosition(tabId);
    } else {
        console.error(`Tab with ID "${tabId}" not found.`);
    }
}

function updateSliderPosition(tabId) {
    const tabLinks = document.querySelectorAll(".tabs-nav .tab-link");
    const slider = document.querySelector(".tab-slider") || document.querySelector(".tab-slider-admin");

    if (!slider) {
        console.warn("No slider found. Skipping update.");
        return;
    }

    let targetLink = document.querySelector(`.tabs-nav a[href="#${tabId}"]`);
    if (targetLink) {
        const tabIndex = [...tabLinks].indexOf(targetLink);
        slider.style.transform = `translateX(${tabIndex * 100}%)`;
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const tabLinks = document.querySelectorAll(".tabs-nav .tab-link");
    const slider = document.querySelector(".tab-slider") || document.querySelector(".tab-slider-admin");

    tabLinks.forEach((tab) => {
        tab.addEventListener("mouseover", function () {
            updateSliderPosition(this.getAttribute("href").substring(1));
        });

        tab.addEventListener("click", function (e) {
            e.preventDefault();
            tabLinks.forEach((t) => t.classList.remove("active"));
            this.classList.add("active");

            activeTabId = this.getAttribute("href").substring(1);
            updateSliderPosition(activeTabId);
        });
    });

    document.querySelector(".tabs-nav").addEventListener("mouseleave", function () {
        if (activeTabId) {
            updateSliderPosition(activeTabId);
        }
    });

    if (activeTabId) {
        updateSliderPosition(activeTabId);
    }
});

function filterTable(tableId, searchId) {
    let input = document.getElementById(searchId);
    if (!input) return;

    let filter = input.value.toLowerCase();
    let table = document.getElementById(tableId);
    if (!table) return;

    let rows = table.getElementsByTagName("tr");

    for (let i = 1; i < rows.length; i++) {
        let cells = rows[i].getElementsByTagName("td");
        let rowMatch = false;

        for (let j = 0; j < cells.length; j++) {
            if (cells[j]) {
                let cellText = cells[j].innerText.toLowerCase();
                if (cellText.includes(filter)) {
                    rowMatch = true;
                    break;
                }
            }
        }
        rows[i].style.display = rowMatch ? "" : "none";
    }
}
