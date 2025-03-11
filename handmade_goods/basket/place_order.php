<?php
session_start();
include '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION["user_id"];

// Start transaction
$conn->begin_transaction();

try {
    // Get cart items with current stock levels and names
    $stmt = $conn->prepare("
        SELECT ci.item_id, i.name, i.price, ci.quantity, i.stock 
        FROM cart_items ci
        JOIN items i ON ci.item_id = i.id
        JOIN cart c ON ci.cart_id = c.id
        WHERE c.user_id = ?
    ");

    if (!$stmt) {
        throw new Exception("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart_items = [];
    $total_price = 0;
    $stock_updates = [];

    while ($row = $result->fetch_assoc()) {
        if ($row['stock'] < $row['quantity']) {
            throw new Exception("Not enough stock available for one or more items in your cart");
        }
        $cart_items[] = $row;
        $total_price += $row["price"] * $row["quantity"];
        $stock_updates[] = [
            'item_id' => $row['item_id'],
            'quantity' => $row['quantity']
        ];
    }
    $stmt->close();

    if (empty($cart_items)) {
        throw new Exception("Your basket is empty");
    }

    $shipping = 7.99;
    $taxRate = 0.075;
    $tax = round($total_price * $taxRate, 2);
    $final_total = $total_price + $shipping + $tax;

    // Create order
    $stmt = $conn->prepare("INSERT INTO ORDERS (user_id, total_price, status) VALUES (?, ?, 'Pending')");
    if (!$stmt) {
        throw new Exception("SQL Error: " . $conn->error);
    }
    $stmt->bind_param("id", $user_id, $final_total);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Add order items with item name
    foreach ($cart_items as $item) {
        $stmt = $conn->prepare("INSERT INTO ORDER_ITEMS (order_id, item_id, item_name, quantity, price_at_purchase) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("SQL Error: " . $conn->error);
        }
        $stmt->bind_param("iisid", $order_id, $item["item_id"], $item["name"], $item["quantity"], $item["price"]);
        $stmt->execute();
        $stmt->close();
    }

    // Update stock levels
    foreach ($stock_updates as $update) {
        $stmt = $conn->prepare("UPDATE items SET stock = stock - ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("SQL Error: " . $conn->error);
        }
        $stmt->bind_param("ii", $update['quantity'], $update['item_id']);
        $stmt->execute();
        $stmt->close();
    }

    // Clear cart
    $stmt = $conn->prepare("
        DELETE ci FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.id
        WHERE c.user_id = ?
    ");
    if (!$stmt) {
        throw new Exception("SQL Error: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // If everything is successful, commit the transaction
    $conn->commit();
    echo json_encode(["success" => "Order placed successfully", "order_id" => $order_id]);
} catch (Exception $e) {
    // If there's an error, rollback changes
    $conn->rollback();
    echo json_encode(["error" => $e->getMessage()]);
}
?>
