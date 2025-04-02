<?php
session_start();
require_once '../config.php';
require_once '../stripe/stripe.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION["user_id"])) {
        throw new Exception("User not authenticated");
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['order_id'];
    $amount = $input['amount'];

    // Verify order belongs to user
    $stmt = $conn->prepare("SELECT total_price, status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $orderId, $_SESSION["user_id"]);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        throw new Exception("Order not found");
    }

    if ($order['status'] !== 'Pending') {
        throw new Exception("Order is not in pending status");
    }

    // Create a PaymentIntent with Stripe
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => 'usd',
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
        'metadata' => [
            'order_id' => $orderId
        ]
    ]);

    echo json_encode([
        'success' => true,
        'clientSecret' => $paymentIntent->client_secret
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}