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
    $_SESSION["error"] = "Invalid request: Missing item ID";
    header("Location: profile.php");
    exit();
}

$item_id = intval($_POST["item_id"]);

// Start transaction to ensure data consistency
$conn->begin_transaction();

try {
    // First check if item exists
    $check_stmt = $conn->prepare("SELECT id FROM ITEMS WHERE id = ?");
    $check_stmt->bind_param("i", $item_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Item with ID $item_id not found");
    }
    $check_stmt->close();

    // Delete from reviews if any
    $stmt = $conn->prepare("DELETE FROM REVIEWS WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
    error_log("Deleted associated reviews");

    // Remove from order_items if any
    $stmt = $conn->prepare("DELETE FROM ORDER_ITEMS WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
    error_log("Deleted associated order items");

    // Remove from cart_items
    $stmt = $conn->prepare("DELETE FROM CART_ITEMS WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
    error_log("Deleted associated cart items");

    // Delete item images if they exist
    $stmt = $conn->prepare("DELETE FROM ITEM_IMAGES WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
    error_log("Deleted associated item images");

    // Finally delete the item itself
    $stmt = $conn->prepare("DELETE FROM ITEMS WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception("Failed to delete item: " . $stmt->error);
    }
    
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    if ($affected_rows === 0) {
        throw new Exception("No items were deleted. Item might have already been removed.");
    }
    
    error_log("Successfully deleted item ID: " . $item_id);

    $conn->commit();
    $_SESSION["success"] = "Product has been successfully deleted.";
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error deleting product: " . $e->getMessage());
    $_SESSION["error"] = "Error deleting product: " . $e->getMessage();
}

// Ensure there are no output buffers before redirecting
while (ob_get_level()) {
    ob_end_clean();
}

header("Location: profile.php#listings");
exit();