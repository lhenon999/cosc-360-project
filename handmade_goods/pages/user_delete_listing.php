<?php
session_start();
require_once __DIR__ . '/../config.php';

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["item_id"])) {
    header("Location: my_shop.php");
    exit();
}

$item_id = intval($_POST["item_id"]);
$user_id = $_SESSION["user_id"];

// Start transaction to ensure data consistency
$conn->begin_transaction();

try {
    // First check if the item exists and belongs to the user
    $stmt = $conn->prepare("SELECT id FROM ITEMS WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $item_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Listing not found or you don't have permission to delete it.");
    }
    $stmt->close();
    
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
    $stmt = $conn->prepare("DELETE FROM ITEMS WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $item_id, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to delete the listing.");
    }
    $stmt->close();

    $conn->commit();
    $_SESSION["success"] = "Your listing has been successfully deleted.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION["error"] = "Error deleting listing: " . $e->getMessage();
}

header("Location: my_shop.php");
exit(); 