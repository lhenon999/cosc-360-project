document.addEventListener("DOMContentLoaded", function () {
    if (typeof userType !== "undefined" && userType === "admin") {
        const currentHash = window.location.hash.substring(1);
        let activeTabId = currentHash || "users";

        document.querySelectorAll('.tab-pane').forEach(content => {
            content.style.display = content.id === activeTabId ? "block" : "none";
        });

        setTimeout(() => {
            if (typeof userType !== "undefined" && typeof itemName !== "undefined" && userType === "admin") {
                if (itemName) {
                    switchTab("listings");
                    let listingsSearch = document.getElementById("listingsSearch");
                    if (listingsSearch) {
                        listingsSearch.value = itemName;
                        filterTable("listingsTable", "listingsSearch");
                    }
                    history.replaceState(null, null, "#listings");
                } else if (userName) {
                    switchTab("users");
                    let userSearch = document.getElementById("userSearch");
                    if (userSearch) {
                        userSearch.value = userName;
                        filterTable("usersTable", "userSearch");
                    }
                    history.replaceState(null, null, "#users");
                }
            } else {
                switchTab(activeTabId);
            }
        }, 300);
    }
});

function switchTab(tabId) {
    if (typeof userType !== "undefined" && userType === "admin") {
        activeTabId = tabId;

        document.querySelectorAll('.tabs-nav a').forEach(link => link.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(content => {
            content.style.display = (content.id === tabId) ? "block" : "none";
            content.classList.toggle('active', content.id === tabId);
        });

        let targetLink = document.querySelector(`.tabs-nav a[href="#${tabId}"]`);
        if (targetLink) {
            targetLink.classList.add('active');
            updateSliderPosition(tabId);
        } else {
            console.error(`Tab with ID "${tabId}" not found.`);
        }
    }
}

function updateSliderPosition(tabId) {
    const tabLinks = document.querySelectorAll(".tabs-nav .tab-link");
    const slider = document.querySelector(".tab-slider-admin");

    if (!slider) {
        return;
    }

    let targetLink = document.querySelector(`.tabs-nav a[href="#${tabId}"]`);
    if (targetLink) {
        const tabIndex = [...tabLinks].indexOf(targetLink);
        slider.style.transform = `translateX(${tabIndex * 100}%)`;
    }
}

document.addEventListener("DOMContentLoaded", function () {
    if (typeof userType !== "undefined" && userType === "admin") {
        const tabLinks = document.querySelectorAll(".tabs-nav .tab-link");

        tabLinks.forEach((tab) => {
            tab.addEventListener("mouseover", function () {
                updateSliderPosition(this.getAttribute("href").substring(1));
            });

            tab.addEventListener("click", function (e) {
                e.preventDefault();
                switchTab(this.getAttribute("href").substring(1));
            });
        });

        document.querySelector(".tabs-nav").addEventListener("mouseleave", function () {
            if (activeTabId) { 
                updateSliderPosition(activeTabId); 
            }
        });

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
        let rowMatch = Array.from(rows[i].getElementsByTagName("td"))
            .some(cell => cell.innerText.toLowerCase().includes(filter));

        rows[i].style.display = rowMatch ? "" : "none";
    }
}
