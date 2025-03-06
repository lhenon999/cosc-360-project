<?php
session_start();
include '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("
    SELECT ci.item_id, i.price, ci.quantity 
    FROM cart_items ci
    JOIN items i ON ci.item_id = i.id
    JOIN cart c ON ci.cart_id = c.id
    WHERE c.user_id = ?
");

if (!$stmt) {
    echo json_encode(["error" => "SQL Error: " . $conn->error]);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$total_price = 0;

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total_price += $row["price"] * $row["quantity"];
}
$stmt->close();

if (empty($cart_items)) {
    echo json_encode(["error" => "Your basket is empty"]);
    exit();
}

$shipping = 7.99;
$taxRate = 0.075;
$tax = round($total_price * $taxRate, 2);
$final_total = $total_price + $shipping + $tax;

$stmt = $conn->prepare("INSERT INTO ORDERS (user_id, total_price, status) VALUES (?, ?, 'Pending')");
if (!$stmt) {
    echo json_encode(["error" => "SQL Error: " . $conn->error]);
    exit();
}
$stmt->bind_param("id", $user_id, $final_total);
$stmt->execute();
$order_id = $stmt->insert_id;
$stmt->close();

foreach ($cart_items as $item) {
    $stmt = $conn->prepare("INSERT INTO ORDER_ITEMS (order_id, item_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(["error" => "SQL Error: " . $conn->error]);
        exit();
    }
    $stmt->bind_param("iiid", $order_id, $item["item_id"], $item["quantity"], $item["price"]);
    $stmt->execute();
    $stmt->close();
}

$stmt = $conn->prepare("
    DELETE ci FROM cart_items ci
    JOIN cart c ON ci.cart_id = c.id
    WHERE c.user_id = ?
");
if (!$stmt) {
    echo json_encode(["error" => "SQL Error: " . $conn->error]);
    exit();
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(["success" => "Order placed successfully", "order_id" => $order_id]);
?>
