<?php
session_start();
require_once __DIR__ . '/../config.php';

// Check if user is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== 'admin') {
    $_SESSION["error"] = "Unauthorized access";
    header("Location: profile.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["item_id"])) {
    header("Location: profile.php");
    exit();
}

$item_id = intval($_POST["item_id"]);

// Start transaction to ensure data consistency
$conn->begin_transaction();

try {
    // Remove from cart_items first
    $stmt = $conn->prepare("DELETE FROM CART_ITEMS WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();

    // Delete item images if they exist
    $stmt = $conn->prepare("DELETE FROM ITEM_IMAGES WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();

    // Finally delete the item itself
    $stmt = $conn->prepare("DELETE FROM ITEMS WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $_SESSION["success"] = "Product has been successfully deleted.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION["error"] = "Error deleting product: " . $e->getMessage();
}

header("Location: profile.php#listings");
exit();