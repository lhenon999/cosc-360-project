<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

if (!isset($_GET["order_id"])) {
    die("Invalid order ID.");
}

$user_id = $_SESSION["user_id"];
$order_id = intval($_GET["order_id"]);

// Fetch order details
$stmt = $conn->prepare("
    SELECT id, total_price, status, created_at
    FROM orders
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Order not found or you do not have permission to view it.");
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT i.name, oi.quantity, oi.price_at_purchase
    FROM order_items oi
    JOIN items i ON oi.item_id = i.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');
    </style>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/order_details.css">
</head>

<body>
    <?php include '../assets/html/navbar.php'; ?>
    <div class="order-details-container">
        <h2>Order Details</h2>

        <div class="order-info">
            <p><strong>Order ID:</strong> #<?= $order["id"] ?></p>
            <p><strong>Status:</strong>
                <span class="order-status 
                    <?= strtolower($order["status"]) === 'pending' ? 'status-pending' : '' ?>
                    <?= strtolower($order["status"]) === 'shipped' ? 'status-shipped' : '' ?>
                    <?= strtolower($order["status"]) === 'delivered' ? 'status-delivered' : '' ?>
                    <?= strtolower($order["status"]) === 'cancelled' ? 'status-cancelled' : '' ?>">
                    <?= htmlspecialchars($order["status"]) ?>
                </span>
            </p>
            <p><strong>Total Price:</strong> $<?= number_format($order["total_price"], 2) ?></p>
            <p><strong>Order Date:</strong> <?= $order["created_at"] ?></p>
        </div>

        <h3>Items</h3>
        <table class="order-items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price (Each)</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $order_items->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($item["name"]) ?></td>
                        <td><?= $item["quantity"] ?></td>
                        <td>$<?= number_format($item["price_at_purchase"], 2) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a href="../pages/profile.php" class="back-btn">Back to Profile</a>
    </div>
</body>

</html>