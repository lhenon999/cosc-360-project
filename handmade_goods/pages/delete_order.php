<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["order_id"])) {
    header("Location: profile.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$order_id = intval($_POST["order_id"]);

// Use a single query to verify order ownership and get items
$stmt = $conn->prepare("
    SELECT oi.item_id, oi.quantity 
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$items_to_restore = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($items_to_restore)) {
    $_SESSION["error"] = "Order not found or you don't have permission to delete it.";
    header("Location: profile.php");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Batch update stock for all items in a single query
    $update_values = [];
    $update_types = "";
    foreach ($items_to_restore as $item) {
        $update_values[] = $item['quantity'];
        $update_values[] = $item['item_id'];
        $update_types .= "ii";
    }
    
    $placeholders = str_repeat("WHEN id = ? THEN stock + ? ", count($items_to_restore));
    $ids = implode(',', array_column($items_to_restore, 'item_id'));
    
    $stmt = $conn->prepare("
        UPDATE items 
        SET stock = CASE 
            {$placeholders}
            ELSE stock 
        END 
        WHERE id IN ({$ids})
    ");
    $stmt->bind_param($update_types, ...$update_values);
    $stmt->execute();
    $stmt->close();

    // Delete order and its items in one query using foreign key cascade
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $_SESSION["success"] = "Order #" . $order_id . " has been deleted successfully and product stock has been restored.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION["error"] = "Error deleting order: " . $e->getMessage();
}

header("Location: profile.php");
exit();