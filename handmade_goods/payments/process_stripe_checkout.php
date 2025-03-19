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

    // Verify order belongs to user
    $stmt = $conn->prepare("
        SELECT o.id, o.total_price, o.status, i.name as item_name, oi.quantity, oi.price_at_purchase
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN items i ON oi.item_id = i.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->bind_param("ii", $orderId, $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $line_items = [];
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => round($row['price_at_purchase'] * 100),
                'product_data' => [
                    'name' => $row['item_name'],
                ],
            ],
            'quantity' => $row['quantity'],
        ];
        $total = $row['total_price'];
    }
    $stmt->close();

    if (empty($line_items)) {
        throw new Exception("Order not found or empty");
    }

    // Add shipping as a separate line item
    $line_items[] = [
        'price_data' => [
            'currency' => 'usd',
            'unit_amount' => 799,
            'product_data' => [
                'name' => 'Shipping',
            ],
        ],
        'quantity' => 1,
    ];

    // Add tax as a separate line item
    $tax_amount = round($total * 0.075 * 100);
    $line_items[] = [
        'price_data' => [
            'currency' => 'usd',
            'unit_amount' => $tax_amount,
            'product_data' => [
                'name' => 'Sales Tax (7.5%)',
            ],
        ],
        'quantity' => 1,
    ];

    // Get the base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $base_url = $protocol . $_SERVER['HTTP_HOST'] . '/cosc-360-project/handmade_goods';

    // Create Checkout Session using our minimal API implementation
    $session = $stripe->createCheckoutSession([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => $base_url . '/pages/order_confirmation.php?order_id=' . $orderId,
        'cancel_url' => $base_url . '/pages/basket.php',
        'metadata' => [
            'order_id' => $orderId
        ]
    ]);

    if (isset($session['error'])) {
        throw new Exception($session['error']['message']);
    }

    echo json_encode([
        'success' => true,
        'url' => $session['url']
    ]);

} catch (Exception $e) {
    error_log('Stripe Checkout Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}