<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["order_id"])) {
    header("Location: profile.php#orders");
    exit();
}

$user_id = $_SESSION["user_id"];
$order_id = intval($_POST["order_id"]);

// Start transaction
$conn->begin_transaction();

try {
    // First check if the order exists and belongs to the user
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Order not found or you don't have permission to delete it.");
    }
    $stmt->close();

    // Get items to restore stock
    $stmt = $conn->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_to_restore = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Update stock for each item
    foreach ($items_to_restore as $item) {
        $stmt = $conn->prepare("UPDATE items SET stock = stock + ? WHERE id = ?");
        $stmt->bind_param("ii", $item['quantity'], $item['item_id']);
        $stmt->execute();
        $stmt->close();
    }

    // Delete the order (order_items will be deleted automatically via ON DELETE CASCADE)
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to delete the order.");
    }
    $stmt->close();

    $conn->commit();
    $_SESSION["success"] = "Order #" . $order_id . " has been deleted successfully and product stock has been restored.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION["error"] = "Error deleting order: " . $e->getMessage();
}

header("Location: profile.php#orders");
exit();