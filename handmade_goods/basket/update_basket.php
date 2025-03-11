<?php
session_start();
require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["product_id"], $_POST["quantity"])) {
    $item_id = $_POST["product_id"];
    $quantity = max(1, intval($_POST["quantity"]));

    // Check stock availability first
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
        $user_id = $_SESSION["user_id"];
        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart = $result->fetch_assoc();
        $cart_id = $cart ? $cart["id"] : null;

        if (!$cart_id) {
            $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                die("Error creating cart: " . $stmt->error);
            }
            $cart_id = $stmt->insert_id;
        }

        $stmt = $conn->prepare("SELECT id FROM cart_items WHERE cart_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $cart_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart_item = $result->fetch_assoc();

        if ($cart_item) {
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND item_id = ?");
            $stmt->bind_param("iii", $quantity, $cart_id, $item_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, item_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $cart_id, $item_id, $quantity);
        }

        if (!$stmt->execute()) {
            die("Error updating cart: " . $stmt->error);
        }

        if ($quantity !== intval($_POST["quantity"])) {
            $_SESSION["message"] = "Quantity adjusted to match available stock.";
        }

        $stmt->close();
    } else {
        if (!isset($_SESSION["cart"])) {
            $_SESSION["cart"] = [];
        }
        $_SESSION["cart"][$item_id] = [
            "quantity" => $quantity
        ];
        
        if ($quantity !== intval($_POST["quantity"])) {
            $_SESSION["message"] = "Quantity adjusted to match available stock.";
        }
    }
}

header("Location: ../pages/basket.php");
exit();