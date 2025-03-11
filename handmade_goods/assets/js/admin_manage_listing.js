function switchTab(tabId) {
    const tabLinks = document.querySelectorAll('.tabs-nav a');
    const tabContents = document.querySelectorAll('.tab-pane');

    tabLinks.forEach(link => link.classList.remove('active'));
    tabContents.forEach(content => content.classList.remove('active'));

    let targetLink = document.querySelector(`.tabs-nav a[href="#${tabId}"]`);
    let targetTab = document.getElementById(tabId);

    if (targetLink && targetTab) {
        targetLink.classList.add('active');
        targetTab.classList.add('active');
    } else {
        console.error(`Tab with ID "${tabId}" not found.`);
    }
}

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

// Ensure this script runs after the page is fully loaded
document.addEventListener("DOMContentLoaded", function () {
    setTimeout(() => {
        if (typeof userType !== "undefined" && typeof itemName !== "undefined" && userType === "admin" && itemName) {
            switchTab("listings");

            // Prefill search bar and trigger filtering
            let listingsSearch = document.getElementById("listingsSearch");
            if (listingsSearch) {
                listingsSearch.value = itemName;
                filterTable('listingsTable', 'listingsSearch');
            }

            // Update URL hash to reflect Listings tab without scrolling
            history.replaceState(null, null, "#listings");
        }
    }, 300);
});
