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

// Set proper content type for JSON response
header('Content-Type: application/json');

try {
    // Start logging
    logCheckout("Starting checkout process");

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

    // Verify order belongs to user and has an address
    $stmt = $conn->prepare("
        SELECT o.id, o.total_price, o.status, o.address_id,
               a.street_address, a.city, a.state, a.postal_code, a.country
        FROM orders o
        LEFT JOIN addresses a ON o.address_id = a.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->bind_param("ii", $orderId, $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        throw new Exception("Order not found or doesn't belong to you");
    }
    
    if (!$order['address_id']) {
        throw new Exception("No shipping address associated with this order");
    }
    
    // Log order details for debugging
    logCheckout("Order details", $order);

    // Get order items
    $stmt = $conn->prepare("
        SELECT i.name as item_name, oi.quantity, oi.price_at_purchase
        FROM order_items oi
        JOIN items i ON oi.item_id = i.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $orderId);
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
        throw new Exception("Order has no items");
    }

    logCheckout("Order items prepared", ['items_count' => count($line_items)]);

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI']));
    
    logCheckout("Base URL determined", ['base_url' => $base_url]);

    // Configure the checkout session (without shipping address collection)
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
        'shipping_options' => [
            [
                'shipping_rate_data' => [
                    'type' => 'fixed_amount',
                    'fixed_amount' => [
                        'amount' => (int)$shipping_amount,
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
        'payment_intent_data' => [
            'metadata' => [
                'order_id' => $orderId
            ]
        ]
    ];

    logCheckout("Creating Stripe checkout session", ['params' => $session_params]);

    // Create Checkout Session with error handling
    $session = null;
    $errorMessage = '';
    
    try {
        if (class_exists('\Stripe\Stripe')) {
            // Using official Stripe PHP SDK
            logCheckout("Using official Stripe SDK");
            $session = $stripe->checkout->sessions->create($session_params);
        } else {
            // Using fallback implementation
            logCheckout("Using fallback Stripe implementation");
            $session = $stripe->createCheckoutSession($session_params);
        }
        
        if (!$session) {
            throw new Exception("Stripe session creation failed - no response");
        }
        
        if (is_object($session) && !isset($session->url)) {
            throw new Exception("Stripe session created but no URL returned");
        }
        
        if (is_array($session) && !isset($session['url'])) {
            throw new Exception("Stripe session created but no URL returned in array");
        }
        
        // Handle different session response formats (object vs array)
        $sessionId = is_object($session) ? $session->id : $session['id'];
        $checkoutUrl = is_object($session) ? $session->url : $session['url'];
        
        logCheckout("Checkout session created successfully", ['session_id' => $sessionId]);
        
        // Send success response
        echo json_encode([
            'success' => true,
            'session_id' => $sessionId,
            'checkout_url' => $checkoutUrl
        ]);
        
        // Update inventory in background (don't wait for this to complete)
        try {
            // Get order items to update inventory
            $stmt = $conn->prepare("
                SELECT oi.item_id, oi.quantity, i.name, i.stock 
                FROM order_items oi
                JOIN items i ON oi.item_id = i.id
                WHERE oi.order_id = ?
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Create logs directory for inventory updates if it doesn't exist
            $inventoryLogDir = dirname(dirname(__FILE__)) . '/logs';
            if (!is_dir($inventoryLogDir)) {
                mkdir($inventoryLogDir, 0777, true);
            }
            $inventoryLogFile = $inventoryLogDir . '/inventory_updates.log';
            
            // Update inventory for each item
            foreach ($orderItems as $item) {
                // Get current stock
                $stmt = $conn->prepare("SELECT stock FROM items WHERE id = ?");
                $stmt->bind_param("i", $item['item_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();
                $currentStock = $product['stock'];
                $stmt->close();
                
                // Update the stock
                $stmt = $conn->prepare("
                    UPDATE items 
                    SET stock = GREATEST(0, stock - ?) 
                    WHERE id = ?
                ");
                $stmt->bind_param("ii", $item['quantity'], $item['item_id']);
                $stmt->execute();
                $stmt->close();
                
                // Get new stock after update
                $stmt = $conn->prepare("SELECT stock FROM items WHERE id = ?");
                $stmt->bind_param("i", $item['item_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();
                $newStock = $product['stock'];
                $stmt->close();
                
                // Log inventory change
                file_put_contents(
                    $inventoryLogFile, 
                    date('Y-m-d H:i:s') . " - Order #$orderId - Item {$item['item_id']} ({$item['name']}): Stock changed from $currentStock to $newStock\n", 
                    FILE_APPEND
                );
                
                logCheckout("Updated inventory for item {$item['name']}", [
                    'item_id' => $item['item_id'],
                    'old_stock' => $currentStock,
                    'new_stock' => $newStock,
                    'quantity' => $item['quantity']
                ]);
            }
        } catch (Exception $e) {
            // Just log the error, don't stop the checkout process
            logCheckout("Error updating inventory: " . $e->getMessage());
        }

    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        logCheckout("Error in Stripe session creation: " . $errorMessage);
        throw new Exception("Stripe error: " . $errorMessage);
    }

} catch (Exception $e) {
    logCheckout("Error creating checkout session: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    
    // Check for specific error conditions
    $errorMessage = $e->getMessage();
    $specificError = "";
    
    if (strpos($errorMessage, "cURL error") !== false) {
        $specificError = "Network error: Unable to connect to Stripe. Please check your internet connection.";
    } else if (strpos($errorMessage, "SSL certificate problem") !== false) {
        $specificError = "SSL Error: Your server cannot verify Stripe's SSL certificate.";
    } else if (strpos($errorMessage, "API key") !== false) {
        $specificError = "API Key Error: The Stripe API key may be invalid or missing.";
    } else if (strpos($errorMessage, "No such") !== false) {
        $specificError = "Resource Error: A required Stripe resource was not found.";
    } else if (strpos($errorMessage, "class 'Stripe") !== false) {
        $specificError = "Stripe Library Error: The Stripe PHP library is not properly loaded.";
    }
    
    echo json_encode([
        'success' => false,
        'error' => $specificError ?: "Error creating checkout session. Please try again.",
        'detailed_error' => $errorMessage
    ]);
}