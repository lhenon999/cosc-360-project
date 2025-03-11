document.addEventListener("DOMContentLoaded", function () {
    let earningsChart;

    function renderEarningsChart() {
        const totalEarnings = parseFloat("<?= $totalEarnings ?>");
        const ctx = document.getElementById("earningsChart");

        if (!ctx) {
            console.error("Canvas element with ID 'earningsChart' not found.");
            return;
        }

        if (earningsChart instanceof Chart) {
            earningsChart.destroy();
        }

        earningsChart = new Chart(ctx.getContext("2d"), {
            type: "doughnut",
            data: {
                labels: ["Earnings", "Remaining"],
                datasets: [{
                    data: [totalEarnings, Math.max(10000 - totalEarnings, 0)],
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

    document.querySelector('a[href="#sales"]').addEventListener("click", function () {
        setTimeout(renderEarningsChart, 100); 
    });

    if (document.getElementById("sales").classList.contains("active")) {
        renderEarningsChart();
    }
});