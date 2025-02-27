<?php
session_start();
require_once '../config.php';

// Ensure user is logged in
if (!isset($_SESSION["user_id"])) {
    $_SESSION["error"] = "You must be logged in to view this page.";
    header("Location: ../pages/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Retrieve latest order for the user
$stmt = $conn->prepare("
    SELECT id, total_price, status, created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

// If no order is found, redirect to the basket page
if (!$order) {
    $_SESSION["error"] = "No recent orders found.";
    header("Location: basket.php");
    exit();
}

$order_id = $order["id"];
$total_price = $order["total_price"];
$status = $order["status"];
$order_date = date("F j, Y, g:i a", strtotime($order["created_at"]));

// Retrieve order items
$stmt = $conn->prepare("
    SELECT oi.item_id, i.name, i.img, oi.quantity, oi.price_at_purchase
    FROM order_items oi
    JOIN items i ON oi.item_id = i.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>

    <style>@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap');</style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
</head>
<body>
<?php include '../assets/html/navbar.php'; ?>

<div class="container mt-5">
    <h1 class="text-success">Thank You for Your Order!</h1>
    <p>Your order has been placed successfully. Here are your order details:</p>

    <div class="order-summary mt-4 p-4 border rounded">
        <h4>Order ID: #<?= $order_id ?></h4>
        <p><strong>Date:</strong> <?= $order_date ?></p>
        <p><strong>Status:</strong> <span class="text-primary"><?= $status ?></span></p>
        <h5>Total Amount: <span class="text-success">$<?= number_format($total_price, 2) ?></span></h5>
    </div>

    <h3 class="mt-5">Items Ordered:</h3>
    <div class="row">
        <?php foreach ($order_items as $item): ?>
            <div class="col-md-6 mb-4">
                <div class="order-item p-3 border rounded d-flex align-items-center">
                    <img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-img me-3">
                    <div>
                        <h5><?= htmlspecialchars($item['name']) ?></h5>
                        <p>Quantity: <strong><?= $item['quantity'] ?></strong></p>
                        <p>Price: $<?= number_format($item['price_at_purchase'], 2) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="products.php" class="btn btn-primary mt-3">Continue Shopping</a>
</div>

<?php include "../assets/html/footer.php"; ?>

</body>
</html>