<?php
session_start();
require_once '../config.php';
require_once '../config/stripe.php';

// Create logs directory if it doesn't exist
$logDir = dirname(dirname(__FILE__)) . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Define log file
$logFile = $logDir . '/stripe_checkout.log';

// Log function
function logCheckout($message, $data = null) {
    global $logFile;
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= " - " . (is_array($data) || is_object($data) ? json_encode($data) : $data);
    }
    file_put_contents($logFile, $log . "\n", FILE_APPEND);
}

try {
    // Start logging
    logCheckout("Starting checkout process");

    header('Content-Type: application/json');

    if (!isset($_SESSION["user_id"])) {
        throw new Exception("User not authenticated");
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception("Invalid input format: " . file_get_contents('php://input'));
    }
    
    $orderId = $input['order_id'] ?? null;
    if (!$orderId) {
        throw new Exception("Missing order ID");
    }

    logCheckout("Processing order", ['order_id' => $orderId, 'user_id' => $_SESSION["user_id"]]);

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

    // Prepare line items for Stripe
    $line_items = [];
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
    }
    $stmt->close();

    if (empty($line_items)) {
        throw new Exception("Order not found or empty");
    }

    logCheckout("Order items prepared", ['items_count' => count($line_items)]);

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI']));
    
    logCheckout("Base URL determined", ['base_url' => $base_url]);

    // Configure the checkout session
    $shipping_amount = 799; // $7.99
    $session_params = [
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => $base_url . '/pages/order_confirmation.php?order_id=' . $orderId,
        'cancel_url' => $base_url . '/pages/basket.php',
        'metadata' => [
            'order_id' => $orderId
        ],
        'shipping_address_collection' => [
            'allowed_countries' => ['US', 'CA']
        ],
        'shipping_options' => [
            [
                'shipping_rate_data' => [
                    'type' => 'fixed_amount',
                    'fixed_amount' => [
                        'amount' => (int)$shipping_amount,  // Cast to integer to avoid formatting issues
                        'currency' => 'usd',
                    ],
                    'display_name' => 'Standard shipping',
                    'delivery_estimate' => [
                        'minimum' => [
                            'unit' => 'business_day',
                            'value' => 3,
                        ],
                        'maximum' => [
                            'unit' => 'business_day',
                            'value' => 5,
                        ],
                    ],
                ],
            ],
        ],
        'billing_address_collection' => 'required',
        'payment_intent_data' => [
            'metadata' => [
                'order_id' => $orderId
            ]
        ]
    ];

    logCheckout("Creating Stripe checkout session", ['params' => $session_params]);

    // Create Checkout Session with error handling
    try {
        if (class_exists('\Stripe\Stripe')) {
            // Using official Stripe PHP SDK
            $session = $stripe->checkout->sessions->create($session_params);
        } else {
            // Using fallback implementation
            $session = $stripe->createCheckoutSession($session_params);
        }
        
        if (!$session || empty($session->url)) {
            throw new Exception("Failed to create checkout session - no URL returned");
        }

        logCheckout("Checkout session created successfully", ['session_id' => $session->id]);
        
        echo json_encode([
            'success' => true,
            'url' => $session->url
        ]);

    } catch (Exception $e) {
        logCheckout("Stripe API Error", ['error' => $e->getMessage()]);
        throw new Exception("Failed to create checkout session: " . $e->getMessage());
    }

} catch (Exception $e) {
    logCheckout("Error", ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}