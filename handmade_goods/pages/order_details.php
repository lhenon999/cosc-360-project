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
    <title>Order Details</title>
    <link rel="stylesheet" href="../assets/css/globals.css">
</head>
<body>
    <div class="container">
        <h2>Order Details</h2>
        <p><strong>Order ID:</strong> #<?= $order["id"] ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($order["status"]) ?></p>
        <p><strong>Total Price:</strong> $<?= number_format($order["total_price"], 2) ?></p>
        <p><strong>Order Date:</strong> <?= $order["created_at"] ?></p>

        <h3>Items</h3>
        <table class="table table-bordered">
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

        <a href="../pages/profile.php" class="btn btn-secondary">Back to Profile</a>
    </div>
</body>
</html>
