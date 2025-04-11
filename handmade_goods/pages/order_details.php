<?php
session_start();
require_once __DIR__ . '/../config.php';

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

if (!isset($_GET["order_id"])) {
    die("Invalid order ID.");
}

$user_id = $_SESSION["user_id"];
$user_type = $_SESSION["user_type"];
$order_id = intval($_GET["order_id"]);

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.id, o.total_price, o.status, o.created_at, 
        a.street_address, a.city, a.state, a.postal_code, a.country
    FROM ORDERS o
    LEFT JOIN ADDRESSES a ON o.address_id = a.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();
if (!$order) {
    die("Order not found or you do not have permission to view it.");
}

$formattedDate = date("j F Y", strtotime($order["created_at"]));

$stmt = $conn->prepare("
        SELECT oi.item_name, oi.quantity, oi.price_at_purchase, oi.item_id
        FROM ORDER_ITEMS oi
        WHERE oi.order_id = ?
        ORDER BY oi.item_name
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
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/order_details.css">
</head>

<body>
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>
    <div class="order-details-container">
        <h2 class="text-center">Order Details</h2>
        <div class="order-info">
            <div class="info-row">
                <span class="info-label">Order ID:</span>
                <span class="info-value">#<?= htmlspecialchars($order["id"]) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Order Date:</span>
                <span class="info-value"><?= $formattedDate ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value status <?= strtolower($order["status"]) ?>">
                    <?= htmlspecialchars($order["status"]) ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Price:</span>
                <span class="info-value">$<?= number_format($order["total_price"], 2) ?></span>
            </div>
            <?php if ($order["street_address"]): ?>
                <div class="info-row">
                    <span class="info-label">Shipping Address:</span>
                    <span class="info-value">
                        <?= htmlspecialchars($order["street_address"]) ?>,
                        <?= htmlspecialchars($order["city"]) ?>,
                        <?= htmlspecialchars($order["state"]) ?>
                        <?= htmlspecialchars($order["postal_code"]) ?>,
                        <?= htmlspecialchars($order["country"]) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <h3>Ordered Items</h3>
        <table class="order-items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <?php while ($item = $order_items->fetch_assoc()): ?>
                <tr class="clickable-row"
                    onclick="window.location.href='../pages/product.php?id=<?= $item["item_id"] ?>&from=order_details&order_id=<?= $order["id"] ?>'">
                    <td><?= htmlspecialchars($item["item_name"]) ?></td>
                    <td><?= $item["quantity"] ?></td>
                    <td>$<?= number_format($item["price_at_purchase"], 2) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>


        <div class="action-buttons">
            <a href="../pages/profile.php" class="m-btn b" id="back-btn">Back</a>
            <?php if ($order["status"] === 'Cancelled'): ?>
                <form method="POST" action="delete_order.php" class="delete-form"
                    onsubmit="return confirm('Are you sure you want to delete this order? This canno't be undone.');">
                    <input type="hidden" name="order_id" value="<?= $order["id"] ?>">
                    <button type="submit" class="btn btn-outline-secondary" id="delete-btn">
                        Delete Order
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </div>

    </div>
    <?php include __DIR__ . '/../assets/html/footer.php'; ?>
</body>

</html>