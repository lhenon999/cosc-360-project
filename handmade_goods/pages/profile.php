<?php
session_start();
require_once '../config.php';

$totalEarnings = 3445.67;

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

// Ensure only admin can access admin features
$user_id = $_SESSION["user_id"];
$user_type = $_SESSION["user_type"];

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}

$user_id = intval($_SESSION["user_id"]);
$stmt = $conn->prepare("SELECT name, email, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $profile_picture);
$stmt->fetch();
$stmt->close();

// Get all users if admin - Optimized query
$all_users = [];
if ($user_type === 'admin') {
    $stmt = $conn->prepare("
        SELECT 
            u.id, 
            u.name, 
            u.email, 
            u.user_type, 
            u.created_at,
            (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
            (SELECT COUNT(*) FROM items WHERE user_id = u.id) as total_listings
        FROM users u
        WHERE u.id != ?
        ORDER BY u.created_at DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_users[] = $row;
    }
    $stmt->close();
}
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

                        <a class="cta hover-raise" href="../auth/logout.php"><span
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
                        <a href="#sales">Sales</a>
                    <?php endif; ?>
                </nav>
                <div class="tab-content">
                    <?php if ($user_type === 'admin'): ?>
                        <div id="users" class="tab-pane active">
                            <h3>User Management</h3>
                            <input type="text" id="userSearch" class="form-control mb-3" placeholder="Search users..." onkeyup="filterTable('usersTable', 'userSearch')">
                            <?php if (!empty($all_users)): ?>
                                <table class="users-table" id="usersTable">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Type</th>
                                            <th>Total Orders</th>
                                            <th>Total Listings</th>
                                            <th>Joined Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_users as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user["name"]) ?></td>
                                                <td><?= htmlspecialchars($user["email"]) ?></td>
                                                <td><span
                                                        class="user-type <?= $user["user_type"] ?>"><?= ucfirst(htmlspecialchars($user["user_type"])) ?></span>
                                                </td>
                                                <td><?= $user["total_orders"] ?></td>
                                                <td><?= $user["total_listings"] ?></td>
                                                <td><?= date('M j, Y', strtotime($user["created_at"])) ?></td>
                                                <td>
                                                    <a href="user_profile.php?id=<?= $user["id"] ?>" class="view-btn">View
                                                        Profile</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No users found.</p>
                            <?php endif; ?>
                        </div>
                        <div id="listings" class="tab-pane">
                            <h3>Product Inventory Management</h3>
                            <input type="text" id="listingsSearch" class="form-control mb-3" placeholder="Search listings..." onkeyup="filterTable('listingsTable', 'listingsSearch')">

                            <?php
                            $stmt = $conn->prepare("
                                SELECT i.*, u.name as seller_name, u.email as seller_email,
                                       (SELECT COUNT(*) FROM order_items oi WHERE oi.item_id = i.id) as total_orders
                                FROM items i
                                JOIN users u ON i.user_id = u.id
                                ORDER BY i.stock ASC, i.name ASC
                            ");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0): ?>
                                <table class="inventory-table" id="listingsTable">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Stock</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Total Orders</th>
                                            <th>Seller</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($item = $result->fetch_assoc()): ?>
                                            <tr class="<?= $item['stock'] < 5 ? 'low-stock' : '' ?>">
                                                <td><?= htmlspecialchars($item["name"]) ?></td>
                                                <td>
                                                    <span
                                                        class="stock-level <?= $item['stock'] < 5 ? 'critical' : ($item['stock'] < 10 ? 'warning' : 'good') ?>">
                                                        <?= $item["stock"] ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($item["category"]) ?></td>
                                                <td>$<?= number_format($item["price"], 2) ?></td>
                                                <td><?= $item["total_orders"] ?></td>
                                                <td>
                                                    <a href="user_profile.php?id=<?= $item["user_id"] ?>" class="seller-link">
                                                        <?= htmlspecialchars($item["seller_name"]) ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <a href="user_profile.php?id=<?= $item["user_id"] ?>" class="view-btn">View
                                                        Seller</a>
                                                    <form method="POST" action="delete_listing.php" style="display: inline;"
                                                        onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone and will remove the product from all users\' views.');">
                                                        <input type="hidden" name="item_id" value="<?= $item["id"] ?>">
                                                        <button type="submit" class="delete-btn">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No products found in the inventory.</p>
                            <?php endif;
                            $stmt->close();
                            ?>
                        </div>
                    <?php else: ?>
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
                                                    <span class="status <?= strtolower($order["status"]) ?>">
                                                        <?= htmlspecialchars($order["status"]) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M j, Y', strtotime($order["created_at"])) ?></td>
                                                <td>
                                                    <a href="order_details.php?order_id=<?= $order["id"] ?>"
                                                        class="view-btn">View</a>
                                                    <form method="POST" action="delete_order.php" style="display: inline;"
                                                        onsubmit="return confirm('Are you sure you want to delete this order? This cannot be undone.');">
                                                        <input type="hidden" name="order_id" value="<?= $order["id"] ?>">
                                                        <button type="submit" class="delete-btn">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>You have no orders yet.</p>
                            <?php endif; ?>
                            <?php $stmt->close(); ?>
                        <?php endif; ?>
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

                                <?php if (!empty($all_users)): ?>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>User Type</th>
                                                <th>Total Orders</th>
                                                <th>Total Listings</th>
                                                <th>Joined</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($all_users as $user): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($user["name"]) ?></td>
                                                    <td><?= htmlspecialchars($user["email"]) ?></td>
                                                    <td><span
                                                            class="user-type <?= htmlspecialchars($user["user_type"]) ?>"><?= ucfirst(htmlspecialchars($user["user_type"])) ?></span>
                                                    </td>
                                                    <td><?= htmlspecialchars($user["total_orders"]) ?></td>
                                                    <td><?= htmlspecialchars($user["total_listings"]) ?></td>
                                                    <td><?= date('M j, Y', strtotime($user["created_at"])) ?></td>
                                                    <td>
                                                        <a href="user_profile.php?id=<?= htmlspecialchars($user["id"]) ?>"
                                                            class="view-btn">View Profile</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p>No users found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div id="sales" class="tab-pane">
                        <div class="sales-container">
                            <div class="earnings-summary">
                                <h3>Total Earnings</h3>
                                <canvas id="earningsChart"></canvas>
                                <p>Total Earnings: $<span
                                        id="totalEarnings"><?= number_format($totalEarnings ?? 0, 2) ?></span></p>
                            </div>
                            <div class="sales-summary">
                                <h3>Sales History</h3>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById("profileInput").addEventListener("change", function () {
            document.getElementById("profilePicForm").submit();
        });

        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function () {
            const tabLinks = document.querySelectorAll('.tabs-nav a');
            const tabContents = document.querySelectorAll('.tab-pane');

            function switchTab(e) {
                e.preventDefault();
                const targetId = e.target.getAttribute('href').slice(1);

                const targetTab = document.getElementById(targetId);

                if (!targetTab) {
                    console.error(`Tab with ID "${targetId}" not found.`);
                    return; // Stop execution if tab does not exist
                }

                // Update active states
                tabLinks.forEach(link => link.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                e.target.classList.add('active');
                document.getElementById(targetId).classList.add('active');

                // Update URL hash without scrolling
                history.pushState(null, null, '#' + targetId);
            }

            tabLinks.forEach(link => {
                link.addEventListener('click', switchTab);
            });

            // Handle initial load with hash
            if (window.location.hash) {
                const targetLink = document.querySelector(`a[href="${window.location.hash}"]`);
                if (targetLink) {
                    targetLink.click();
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
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

    </script>

</body>

</html>