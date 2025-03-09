<?php
session_start();
include '../config.php';

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// include '../config.php';

// var_dump($_SESSION);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["product_id"], $_POST["quantity"])) {
    $item_id = intval($_POST["product_id"]);
    $quantity = max(1, intval($_POST["quantity"]));

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

        // Check if item is already in cart
        $stmt = $conn->prepare("SELECT id FROM cart_items WHERE cart_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $cart_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart_item = $result->fetch_assoc();
        $stmt->close();

        if ($cart_item) {
            // Update existing cart item quantity
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE cart_id = ? AND item_id = ?");
            $stmt->bind_param("iii", $quantity, $cart_id, $item_id);
        } else {
            // Add new item to cart
            $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, item_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $cart_id, $item_id, $quantity);
        }

        $stmt->execute();
        $stmt->close();
    } else {
        $_SESSION["error"] = "You must be logged in to add items to the cart.";
        header("Location: ../pages/login.php");
        exit();
    }

    $_SESSION["message"] = "Product added to cart.";
    header("Location: ../pages/basket.php");
    exit();
}
?>