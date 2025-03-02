<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug: Process Payment Page Loaded.<br>";
var_dump($_POST);
exit();

require_once '../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["order_id"])) {
    $_SESSION["error"] = "Invalid payment request.";
    header("Location: order_confirmation.php");
    exit();
}

$order_id = intval($_POST["order_id"]);
$user_id = intval($_SESSION["user_id"]);

$stmt = $conn->prepare("SELECT total_price, status FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION["error"] = "Order not found.";
    header("Location: order_confirmation.php");
    exit();
}

sleep(2); 
$is_payment_successful = true;

if ($is_payment_successful) {
    $stmt = $conn->prepare("UPDATE orders SET status = 'Processing' WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION["message"] = "Payment successful! Your order is now being processed.";
    header("Location: order_confirmation.php");
    exit();
} else {
    $_SESSION["error"] = "Payment failed. Please try again.";
    header("Location: order_confirmation.php");
    exit();
}
?>
