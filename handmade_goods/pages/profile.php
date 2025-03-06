<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

session_start();
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}


$user_id = $_SESSION["user_id"];
$user_type = $_SESSION["user_type"];

$user_id = intval($_SESSION["user_id"]);
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
                <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture">
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($name); ?></h2>
                    <p><?php echo htmlspecialchars($email); ?></p>
                    <div class="profile-buttons">
                        <a class="cta hover-raise" href="../pages/settings.php"><span class="material-symbols-outlined">settings</span>Settings</a>
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
                        <a href="#activity">Other Activity</a>
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
                </div>

            </div>
        </div>
    </div>

</body>

</html>