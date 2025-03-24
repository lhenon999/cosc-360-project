function filterTable(tableId, searchId) {
    let input = document.getElementById(searchId);
    let filter = input.value.toLowerCase();
    let table = document.getElementById(tableId);
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
