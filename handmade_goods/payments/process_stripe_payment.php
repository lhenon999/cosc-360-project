<?php
session_start();
require_once '../config.php';
require_once '../config/stripe.php';

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

    // Create a Checkout Session
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => $amount,
                'product_data' => [
                    'name' => 'Order #' . $orderId,
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/cosc-360-project/handmade_goods/pages/order_confirmation.php?order_id=' . $orderId . '&payment=success',
        'cancel_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/cosc-360-project/handmade_goods/pages/order_confirmation.php?order_id=' . $orderId . '&payment=cancelled',
        'metadata' => [
            'order_id' => $orderId
        ]
    ]);

    echo json_encode([
        'success' => true,
        'url' => $checkout_session->url
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}