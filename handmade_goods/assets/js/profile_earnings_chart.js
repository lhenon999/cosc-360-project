document.addEventListener("DOMContentLoaded", function () {
    let earningsChart;

    function renderEarningsChart() {
        if (typeof totalEarnings === "undefined") {
            console.error("totalEarnings is undefined");
            return;
        }

        const ctx = document.getElementById("earningsChart");
        if (!ctx) {
            console.error("Canvas element with ID 'earningsChart' not found.");
            return;
        }

        if (typeof earningsChart !== "undefined" && earningsChart !== null) {
            earningsChart.destroy();
        }

        earningsChart = new Chart(ctx.getContext("2d"), {
            type: "doughnut",
            data: {
                labels: ["Earnings", "Remaining"],
                datasets: [{
                    data: [totalEarnings, Math.max(200 - totalEarnings, 0)],
                    backgroundColor: ["#2d5a27", "#e0e0e0"],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "70%",
                plugins: {
                    legend: { display: false },
                }
            }
        });

        console.log("Chart rendered successfully with earnings:", totalEarnings);
    }

    const salesTab = document.querySelector('a[href="#sales"]');
    if (salesTab) {
        salesTab.addEventListener("click", function () {
            setTimeout(renderEarningsChart, 100);
        });
    } else {
        console.error("Sales tab link not found.");
    }

    if (document.getElementById("sales")?.classList.contains("active")) {
        renderEarningsChart();
    }
});
