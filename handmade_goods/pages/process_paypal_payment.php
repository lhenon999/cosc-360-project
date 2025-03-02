<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION["user_id"]) || !isset($data["orderID"]) || !isset($data["order_id"])) {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit();
}

$order_id = intval($data["order_id"]);
$paypal_order_id = $data["orderID"];

$stmt = $conn->prepare("UPDATE orders SET status = 'Paid' WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$stmt->close();

echo json_encode(["success" => true]);
exit();
