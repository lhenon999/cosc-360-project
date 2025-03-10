<?php
session_start();
require_once '../config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
// exit();


if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}


$user_id = $_SESSION["user_id"];
$user_type = $_SESSION["user_type"];

$user_id = intval($_SESSION["user_id"]);
$totalEarnings = 3445.67;
$stmt = $conn->prepare("SELECT name, email, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $profile_picture);
$stmt->fetch();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Handmade Goods</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');
    </style>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
</head>

<body>
    <?php include '../assets/html/navbar.php'; ?>

    <div class="container">
        <h1 class="text-center mt-5"><?php echo ($user_type === 'admin') ? 'Admin Dashboard' : 'My Profile'; ?></h1>

        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-image">
                    <div class="profile-image">
                        <form id="profilePicForm" action="upload_profile_picture.php" method="POST"
                            enctype="multipart/form-data">
                            <input type="file" name="profile_picture" id="profileInput" accept="image/*"
                                style="display: none;">
                            <label for="profileInput">
                                <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture"
                                    id="profilePic">
                            </label>
                        </form>
                    </div>

                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($name); ?></h2>
                    <p><?php echo htmlspecialchars($email); ?></p>
                    <div class="profile-buttons">
                        <a class="cta hover-raise" href="../pages/settings.php"><span
                                class="material-symbols-outlined">settings</span>Settings</a>
                        <?php if ($user_type !== 'admin'): ?>
                            <a class="cta hover-raise" href="../pages/my_shop.php"><span
                                    class="material-symbols-outlined">storefront</span>My Shop</a>
                        <?php endif; ?>

                        <a class="cta hover-raise" href="../logout.php"><span
                                class="material-symbols-outlined">logout</span>Logout</a>
                    </div>
                </div>
            </div>

            <div class="profile-tabs mt-5">
                <nav class="tabs-nav">
                    <?php if ($user_type === 'admin'): ?>
                        <a href="#users" class="active">Users</a>
                        <a href="#listings">Listings</a>
                    <?php else: ?>
                        <a href="#orders" class="active">My Orders</a>
                        <a href="#reviews">My Reviews</a>
                        <a href="#sales">My Sales</a>
                    <?php endif; ?>
                </nav>
                <div class="tab-content">
                    <div id="orders" class="tab-pane active">
                        <h3>My Orders</h3>

                        <?php
                        $stmt = $conn->prepare("
            SELECT id, total_price, status, created_at
            FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0): ?>
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Total Price</th>
                                        <th>Status</th>
                                        <th>Order Date</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?= $order["id"] ?></td>
                                            <td>$<?= number_format($order["total_price"], 2) ?></td>
                                            <td>
                                                <span class="status 
                        <?= strtolower($order["status"]) === 'pending' ? 'status-pending' : '' ?>
                        <?= strtolower($order["status"]) === 'shipped' ? 'status-shipped' : '' ?>
                        <?= strtolower($order["status"]) === 'delivered' ? 'status-delivered' : '' ?>
                        <?= strtolower($order["status"]) === 'cancelled' ? 'status-cancelled' : '' ?>">
                                                    <?= htmlspecialchars($order["status"]) ?>
                                                </span>
                                            </td>
                                            <td><?= $order["created_at"] ?></td>
                                            <td>
                                                <a href="../pages/order_details.php?order_id=<?= $order["id"] ?>"
                                                    class="view-btn">View</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>You have no orders yet.</p>
                        <?php endif; ?>

                        <?php $stmt->close(); ?>
                    </div>
                    <div id="reviews" class="tab-pane">
                        <div class="reviews-containers">
                            <div class="rating-summary">
                                <h3>Review Summary</h3>
                                <div class="rating-overall">
                                    <span class="rating-score">4.1</span>
                                    <span class="stars">★★★★☆</span>
                                    <span class="rating-count">167 reviews</span>
                                </div>

                                <div class="rating-bars">
                                    <div class="rating-row">
                                        <span>5</span>
                                        <div class="bar">
                                            <div class="filled" style="width: 80%;"></div>
                                        </div>
                                    </div>
                                    <div class="rating-row">
                                        <span>4</span>
                                        <div class="bar">
                                            <div class="filled" style="width: 40%;"></div>
                                        </div>
                                    </div>
                                    <div class="rating-row">
                                        <span>3</span>
                                        <div class="bar">
                                            <div class="filled" style="width: 20%;"></div>
                                        </div>
                                    </div>
                                    <div class="rating-row">
                                        <span>2</span>
                                        <div class="bar">
                                            <div class="filled" style="width: 10%;"></div>
                                        </div>
                                    </div>
                                    <div class="rating-row">
                                        <span>1</span>
                                        <div class="bar">
                                            <div class="filled" style="width: 30%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="reviews-summary">
                                <h3>Reviews</h3>
                            </div>
                        </div>
                    </div>

                    <div id="sales" class="tab-pane">
                        <div class="sales-summary">
                            <h3>Total Earnings</h3>
                            <canvas id="earningsChart"></canvas>
                            <p>Total Earnings: $<span id="totalEarnings"><?= number_format($totalEarnings, 2) ?></span>
                            </p>
                        </div>
                        <h3>Sales History</h3>

                        <?php
                        // Fetch sales where the logged-in user is the seller
                        $stmt = $conn->prepare("
                        SELECT id, buyer_id, total_price, status, created_at 
                        FROM orders 
                        WHERE seller_id = ? 
                        ORDER BY created_at DESC
                        ");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $sales_result = $stmt->get_result();

                        if ($sales_result->num_rows > 0): ?>
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Sale ID</th>
                                        <th>Buyer</th>
                                        <th>Total Price</th>
                                        <th>Status</th>
                                        <th>Sale Date</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($sale = $sales_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?= $sale["id"] ?></td>
                                            <td><?= htmlspecialchars($sale["buyer_id"]) ?></td>
                                            <td>$<?= number_format($sale["total_price"], 2) ?></td>
                                            <td>
                                                <span class="status 
                            <?= strtolower($sales["status"]) === 'pending' ? 'status-pending' : '' ?>
                            <?= strtolower($sales["status"]) === 'shipped' ? 'status-shipped' : '' ?>
                            <?= strtolower($sales["status"]) === 'delivered' ? 'status-delivered' : '' ?>
                            <?= strtolower($sales["status"]) === 'cancelled' ? 'status-cancelled' : '' ?>">
                                                    <?= htmlspecialchars($sale["status"]) ?>
                                                </span>
                                            </td>
                                            <td><?= $sale["created_at"] ?></td>
                                            <td>
                                                <a href="../pages/order_details.php?order_id=<?= $sale["id"] ?>"
                                                    class="view-btn">View</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No sales recorded yet.</p>
                        <?php endif; ?>
                        <?php $stmt->close(); ?>
                    </div>

                </div>
            </div>
        </div>

        <script>
            document.getElementById("profileInput").addEventListener("change", function () {
                document.getElementById("profilePicForm").submit();
            });
        </script>
        <!-- <script>
            $(document).ready(function () {
                function activateTab(tabId) {
                    $(".tabs-nav a").removeClass("active");
                    $(".tab-pane").removeClass("active").hide();

                    // Activate the correct tab
                    $('.tabs-nav a[href="' + tabId + '"]').addClass("active");
                    $(tabId).fadeIn(300).addClass("active");
                }

                // On tab click
                $(".tabs-nav a").click(function (event) {
                    event.preventDefault();

                    var tabId = $(this).attr("href");
                    activateTab(tabId);

                    // Update URL without reloading the page
                    history.pushState(null, null, tabId);

                    // If switching to Sales tab, delay the chart rendering
                    if (tabId === "#sales") {
                        setTimeout(renderEarningsChart, 300);
                    }
                });

                // Handle page refresh with hash
                var initialTab = window.location.hash || "#orders";
                if ($(initialTab).length) {
                    activateTab(initialTab);
                } else {
                    activateTab("#orders"); // Default tab
                }
            });
        </script> -->
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                let earningsChart;

                function renderEarningsChart() {

                    const totalEarnings = parseFloat("<?= $totalEarnings ?>");

                    const ctx = document.getElementById("earningsChart");

                    if (!ctx) {
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

                }

                $(".tabs-nav a").click(function (event) {
                    event.preventDefault();

                    $(".tabs-nav a").removeClass("active");
                    $(".tab-pane").removeClass("active");

                    $(this).addClass("active");
                    var target = $(this).attr("href");
                    $(target).addClass("active");

                    if (target === "#sales") {
                        setTimeout(renderEarningsChart, 300);
                    }
                });

                if ($("#sales").hasClass("active")) {
                    renderEarningsChart();
                }
            });

        </script>
</body>

</html>