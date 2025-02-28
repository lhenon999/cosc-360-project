<?php
session_start();
require_once './config.php';

if (!isset($_SESSION["user_id"])) {
    $_SESSION["error"] = "You must be logged in to checkout.";
    header("Location: ../pages/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?) ON DUPLICATE KEY UPDATE id=id");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("
    SELECT ci.item_id, i.name, i.price, ci.quantity
    FROM cart_items ci
    JOIN items i ON ci.item_id = i.id
    JOIN cart c ON ci.cart_id = c.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$subtotal = 0;

while ($row = $result->fetch_assoc()) {
    $cart_items[$row["item_id"]] = $row;
    $subtotal += $row["price"] * $row["quantity"];
}
$stmt->close();

if (empty($cart_items)) {
    $_SESSION["error"] = "Your cart is empty.";
    header("Location: /cosc-360-project/handmade_goods/pages/basket.php");
    exit();
}

$shipping = 7.99;
$taxRate = 0.075;
$tax = round($subtotal * $taxRate, 2);
$total_price = round($subtotal + $shipping + $tax, 2);

$stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'Pending')");
$stmt->bind_param("id", $user_id, $total_price);
$stmt->execute();
$order_id = $stmt->insert_id;
$stmt->close();

foreach ($cart_items as $item) {
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $order_id, $item["item_id"], $item["quantity"], $item["price"]);
    $stmt->execute();
    $stmt->close();
}

$stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = (SELECT id FROM cart WHERE user_id = ? LIMIT 1)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

$_SESSION["message"] = "Your order has been placed successfully!";
header("Location: /cosc-360-project/handmade_goods/pages/order_confirmation.php");
exit();