<?php
require_once __DIR__ . '/../config.php';

$userCountQuery = "SELECT COUNT(*) AS total_users FROM USERS WHERE user_type = 'normal'";
$resultUserCount = $conn->query($userCountQuery);
$totalUsers = $resultUserCount ? $resultUserCount->fetch_assoc()['total_users'] : 0;

$itemCountQuery = "SELECT COUNT(*) AS total_items FROM ITEMS";
$resultItemCount = $conn->query($itemCountQuery);
$totalItems = $resultItemCount ? $resultItemCount->fetch_assoc()['total_items'] : 0;

$queryListings = "SELECT COUNT(*) AS listing_count FROM ITEMS WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
$resultListings = $conn->query($queryListings);
$listingCount = $resultListings ? $resultListings->fetch_assoc()['listing_count'] : 0;

$trendQuery = "SELECT DATE(created_at) AS date, COUNT(*) AS count 
                FROM ACCOUNT_ACTIVITY 
                WHERE event_type = 'login' 
                AND user_id IN (SELECT id FROM USERS WHERE user_type = 'normal')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY DATE(created_at) ASC";
$resultTrend = $conn->query($trendQuery);

$dates = [];
$counts = [];
if ($resultTrend && $resultTrend->num_rows > 0) {
    while ($row = $resultTrend->fetch_assoc()) {
        $dates[] = $row['date'];
        $counts[] = $row['count'];
    }
} else {
    $dates = [date('Y-m-d')];
    $counts = [0];
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$userIdFilter = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';

$activityWhere = "WHERE 1=1";
if ($filter === 'login' || $filter === 'registration') {
    $activityWhere .= " AND event_type = '" . $conn->real_escape_string($filter) . "'";
} elseif ($filter === 'all') {
} else {
    $activityWhere .= " AND 1=0";
}
if (!empty($userIdFilter)) {
    $activityWhere .= " AND user_id = " . intval($userIdFilter);
}
$activityWhere .= " AND user_id IN (SELECT id FROM USERS WHERE user_type = 'normal')";

$activityQuery = "SELECT id, user_id, event_type AS activity_type, ip_address, user_agent, created_at, '' AS details
                FROM ACCOUNT_ACTIVITY $activityWhere";

$reviewsWhere = "WHERE 1=1";
if ($filter === 'review' || $filter === 'all') {
} else {
    $reviewsWhere .= " AND 1=0";
}
if (!empty($userIdFilter)) {
    $reviewsWhere .= " AND user_id = " . intval($userIdFilter);
}
$reviewsWhere .= " AND user_id IN (SELECT id FROM USERS WHERE user_type = 'normal')";

$reviewsQuery = "SELECT id, user_id, 'review' AS activity_type, '' AS ip_address, '' AS user_agent, created_at, comment AS details
                FROM REVIEWS $reviewsWhere";

$listingWhere = "WHERE 1=1";
if ($filter === 'listing' || $filter === 'all') {
    // include listings
} else {
    $listingWhere .= " AND 1=0";
}
if (!empty($userIdFilter)) {
    $listingWhere .= " AND user_id = " . intval($userIdFilter);
}
$listingQuery = "SELECT id, user_id, 'listing' AS activity_type, '' AS ip_address, '' AS user_agent, created_at, name AS details
                FROM ITEMS $listingWhere";

$combinedQuery = "($activityQuery) UNION ALL ($reviewsQuery) UNION ALL ($listingQuery) ORDER BY created_at DESC LIMIT 100";
$resultCombined = $conn->query($combinedQuery);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Account Activity</title>
    <link rel="stylesheet" href="../assets/css/advanced_report.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
</head>

<body>
    <div class="account-activity">
        <div class="top-div">
            <div class="recent-activity">
                <h3>Activity</h3>
                <h5>(Last 24 Hours)</h5>
                <p><strong>New Logins:</strong> <?php echo htmlspecialchars($loginCount); ?></p>
                <p><strong>New Registrations:</strong> <?php echo htmlspecialchars($registrationCount); ?></p>
                <p><strong>New Listings:</strong> <?php echo htmlspecialchars($listingCount); ?></p>
            </div>
            <div class="platform-summary">
                <div class="paltform-summary-info">
                    <h3>Platform Summary</h3>
                    <p><strong>Total Registered Users:</strong> <?php echo htmlspecialchars($totalUsers); ?></p>
                    <p><strong>Total Items Listed:</strong> <?php echo htmlspecialchars($totalItems); ?></p>
                </div>
            </div>
        </div>

        <div class="login-trends">
            <h3>Login Trends</h3>
            <h5>(Last 7 Days)</h5>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <div class="account-activity-table">
            <h3>Account Activity</h3>
            <form id="filterForm" action="/cosc-360-project/handmade_goods/pages/advanced_report.php">
                <label for="filter">Activity Type:</label>
                <select name="filter" id="filter">
                    <option value="all" <?php if (isset($_GET['filter']) && $_GET['filter'] === 'all')
                        echo 'selected'; ?>>All</option>
                    <option value="login" <?php if (isset($_GET['filter']) && $_GET['filter'] === 'login')
                        echo 'selected'; ?>>Login</option>
                    <option value="registration" <?php if (isset($_GET['filter']) && $_GET['filter'] === 'registration')
                        echo 'selected'; ?>>Registration</option>
                    <option value="review" <?php if (isset($_GET['filter']) && $_GET['filter'] === 'review')
                        echo 'selected'; ?>>Review</option>
                    <option value="listing" <?php if (isset($_GET['filter']) && $_GET['filter'] === 'listing')
                        echo 'selected'; ?>>Listing</option>
                </select>
                <label for="user_id">User ID:</label>
                <input type="text" name="user_id" id="user_id"
                    value="<?php echo isset($_GET['user_id']) ? htmlspecialchars($_GET['user_id']) : ''; ?>">
            </form>

            <div class="activity-table-div">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Activity Type</th>
                            <th>Created At</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($resultCombined && $resultCombined->num_rows > 0) {
                            while ($row = $resultCombined->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['activity_type']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['user_agent']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No records found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("filterForm").addEventListener("submit", function (e) {
            e.preventDefault();
        });

        function updateFilters() {
            var filter = document.getElementById("filter").value;
            var userId = document.getElementById("user_id").value;
            var url = new URL(window.location.href);
            url.searchParams.set("filter", filter);
            if (userId.trim() !== "") {
                url.searchParams.set("user_id", userId);
            } else {
                url.searchParams.delete("user_id");
            }
            window.location.href = url.href;
        }

        document.getElementById("filter").addEventListener("change", updateFilters);
        document.getElementById("user_id").addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                updateFilters();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const dates = <?php echo json_encode($dates); ?>;
            const counts = <?php echo json_encode($counts); ?>;
            const monthAbbrev = {
                "01": "Jan", "02": "Feb", "03": "Mar", "04": "Apr",
                "05": "May", "06": "Jun", "07": "Jul", "08": "Aug",
                "09": "Sep", "10": "Oct", "11": "Nov", "12": "Dec"
            };
            const ctx = document.getElementById('trendChart').getContext('2d');
            const trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Logins',
                        data: counts,
                        borderColor: '#426B1F',
                        borderWidth: 2,
                        fill: false,
                        pointRadius: 5,
                        pointBackgroundColor: '#426B1F'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true },
                        datalabels: {
                            color: '#000',
                            align: 'top',
                            anchor: 'end',
                            font: { size: 12 },
                            formatter: function (value) {
                                return value;
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: { display: false },
                            ticks: {
                                autoSkip: true,
                                maxTicksLimit: 7,
                                color: "#666",
                                font: { size: 12 },
                                callback: function (value, index) {
                                    const label = this.chart.data.labels[index];
                                    if (label && label.length >= 10) {
                                        const month = label.substring(5, 7);
                                        const day = label.substring(8, 10);
                                        const abbrev = monthAbbrev[month] || month;
                                        return abbrev + '-' + day;
                                    }
                                    return label;
                                }
                            }
                        },
                        y: {
                            display: true,
                            grid: { display: false },
                            ticks: {
                                beginAtZero: true,
                                color: "#666",
                                font: { size: 12 },
                                callback: function (value) {
                                    return Number(value).toFixed(0);
                                }
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        });
    </script>
</body>

</html>
