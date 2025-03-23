<?php
session_start();
require_once __DIR__ . '/../config.php';

if (isset($_GET["id"])) {
    $id = filter_var($_GET["id"], FILTER_SANITIZE_NUMBER_INT);

    if (isset($_SESSION["user_id"])) {
        $user_id = $_SESSION["user_id"];

        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart = $result->fetch_assoc();
        $cart_id = $cart ? $cart["id"] : null;
        $stmt->close();

        if ($cart_id) {
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND item_id = ?");
            $stmt->bind_param("ii", $cart_id, $id);
            if ($stmt->execute()) {
                $_SESSION["message"] = "Item removed from cart.";
            } else {
                $_SESSION["error"] = "Failed to remove item from cart.";
            }
            $stmt->close();
        }
    } else {
        if (isset($_SESSION["cart"][$id])) {
            unset($_SESSION["cart"][$id]);

            if (empty($_SESSION["cart"])) {
                unset($_SESSION["cart"]);
            }

            $_SESSION["message"] = "Item removed from cart.";
        } else {
            $_SESSION["error"] = "Item not found in cart.";
        }
    }
} else {
    $_SESSION["error"] = "Invalid product ID.";
}

header("Location: ../pages/basket.php");
exit();