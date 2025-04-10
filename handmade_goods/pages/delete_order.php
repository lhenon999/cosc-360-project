<?php
session_start();
require_once __DIR__ . '/../config.php';

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
    $stmt = $conn->prepare("SELECT id, status FROM ORDERS WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Order not found or you don't have permission to modify it.");
    }
    
    $order = $result->fetch_assoc();
    $already_cancelled = ($order['status'] === 'Cancelled');
    $stmt->close();

    if ($already_cancelled) {
        // If already cancelled, just delete the order
        $stmt = $conn->prepare("DELETE FROM ORDERS WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to remove the order.");
        }
        $stmt->close();
        $_SESSION["success"] = "Order has been removed successfully.";
    } else {
        // For active orders, change status to Cancelled and restore stock
        // Get items to restore stock
        $stmt = $conn->prepare("SELECT item_id, quantity FROM ORDER_ITEMS WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items_to_restore = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Update stock for each item
        foreach ($items_to_restore as $item) {
            $stmt = $conn->prepare("UPDATE ITEMS SET stock = stock + ? WHERE id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['item_id']);
            $stmt->execute();
            $stmt->close();
        }

        // Update order status to Cancelled
        $status = 'Cancelled';
        $payment_method = 'card'; // Default payment method for cancelled orders
        $stmt = $conn->prepare("UPDATE ORDERS SET status = ?, payment_method = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $status, $payment_method, $order_id, $user_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to cancel the order.");
        }
        $stmt->close();
        $_SESSION["success"] = "Order has been cancelled successfully.";
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION["error"] = "Error: " . $e->getMessage();
}

header("Location: profile.php#orders");
exit();