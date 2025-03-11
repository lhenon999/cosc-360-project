<?php
session_start();
include '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["product_id"], $_POST["quantity"])) {
    $item_id = intval($_POST["product_id"]);
    $quantity = max(1, intval($_POST["quantity"]));

    // Check stock availability
    $stmt = $conn->prepare("SELECT stock FROM items WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    if (!$item) {
        $_SESSION["error"] = "Product not found.";
        header("Location: ../pages/basket.php");
        exit();
    }

    // Limit quantity to available stock
    $quantity = min($quantity, $item['stock']);

    if (isset($_SESSION["user_id"])) {
        $user_id = intval($_SESSION["user_id"]);

        // Get or create user's cart
        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart = $result->fetch_assoc();
        $cart_id = $cart ? $cart["id"] : null;
        $stmt->close();

        if (!$cart_id) {
            $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $cart_id = $stmt->insert_id;
            $stmt->close();
        }

        // Check if item is already in cart and get current quantity
        $stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE cart_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $cart_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart_item = $result->fetch_assoc();
        $stmt->close();

        if ($cart_item) {
            // Calculate new total quantity
            $new_quantity = min($cart_item['quantity'] + $quantity, $item['stock']);
            
            // Update existing cart item quantity
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND item_id = ?");
            $stmt->bind_param("iii", $new_quantity, $cart_id, $item_id);
            
            if ($new_quantity !== ($cart_item['quantity'] + $quantity)) {
                $_SESSION["message"] = "Some items not added due to stock limitations.";
            }
        } else {
            // Add new item to cart
            $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, item_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $cart_id, $item_id, $quantity);
            
            if ($quantity !== intval($_POST["quantity"])) {
                $_SESSION["message"] = "Quantity adjusted to match available stock.";
            }
        }

        $stmt->execute();
        $stmt->close();
    } else {
        $_SESSION["error"] = "You must be logged in to add items to the cart.";
        header("Location: ../auth/login.php");
        exit();
    }

    if (!isset($_SESSION["error"])) {
        $_SESSION["message"] = isset($_SESSION["message"]) ? $_SESSION["message"] : "Product added to cart.";
    }
    header("Location: ../pages/basket.php");
    exit();
}
?>