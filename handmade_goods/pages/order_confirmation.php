<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION["user_id"])) {
    $_SESSION["error"] = "You must be logged in to view this page.";
    header("Location: ../pages/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

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

if (!$order) {
    $_SESSION["error"] = "No recent orders found.";
    header("Location: basket.php");
    exit();
}

$order_id = $order["id"];
$total_price = $order["total_price"];
$status = $order["status"];
$order_date = date("F j, Y, g:i a", strtotime($order["created_at"]));

$stmt = $conn->prepare("
    SELECT oi.item_id, oi.item_name, i.img, oi.quantity, oi.price_at_purchase
    FROM order_items oi
    LEFT JOIN items i ON oi.item_id = i.id
    WHERE oi.order_id = ?
    ORDER BY oi.item_name
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
    <title>Thank you for your order!</title>

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
    <link rel="stylesheet" href="../assets/css/order_confirmation.css">
</head>

<body>
    <?php include '../assets/html/navbar.php'; ?>
    <div class="container mt-5">
        <h1>Thank You for Your Order!</h1>
        <p>Your order has been placed successfully.</p>

        <div class="order-summary mt-5">
            <h4>Order ID: #<?= $order_id ?></h4>
            <p class="mt-4"><strong>Date:</strong> <?= $order_date ?></p>
            <p><strong>Status:</strong> <span><?= $status ?></span></p>
            <h4 class="mt-4"><strong>Total Amount:</strong> <span
                    class="total-price">$<?= number_format($total_price, 2) ?></span></h4>
            <div class="ending-div mt-5">
                <form action="process_payment.php" method="POST">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    <button type="submit" class="cta hover-raise">
                        <span class="material-symbols-outlined">payment</span> Process Payment
                    </button>
                </form>
            </div>
        </div>



        <h3 class="mt-5">Items Ordered:</h3>
        <div class="row mt-4">
            <?php foreach ($order_items as $item): ?>
                <div class="col-md-6 mb-4">
                    <div class="order-item d-flex align-items-center">
                        <img src="<?= htmlspecialchars($item['img'] ?? '../assets/images/product_images/default.webp') ?>" 
                             alt="<?= htmlspecialchars($item['item_name']) ?>"
                             class="cart-img me-4">
                        <div class="item-desc">
                            <h5><?= htmlspecialchars($item['item_name']) ?></h5>
                            <p class="mt-4"><strong>Quantity:</strong> <?= $item['quantity'] ?></p>
                            <p><strong>Price:</strong> $<?= number_format($item['price_at_purchase'], 2) ?></p>
                            <p><strong>Item Total:</strong> $<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></p>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
        <div class="ending-div mt-5">
            <a href="products.php" class="cta hover-raise"><span
                    class="material-symbols-outlined">shoppingmode</span>Continue Shopping</a>
        </div>
    </div>
</body>
</html>