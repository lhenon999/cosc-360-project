<?php
// Set display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Create fake user ID for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

// Include configuration
require_once 'config.php';
require_once 'config/stripe.php';

// HTML header for better display
echo '<!DOCTYPE html>
<html>
<head>
    <title>Stripe Configuration Check</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Stripe Configuration Check</h1>';

// Check if Stripe is loaded
echo '<h2>1. Stripe Library Check</h2>';
if (class_exists('\Stripe\Stripe')) {
    echo '<p class="success">✓ Stripe PHP SDK is loaded successfully.</p>';
    echo '<p>Using Stripe PHP SDK version: ' . \Stripe\Stripe::VERSION . '</p>';
} else if (isset($stripe) && method_exists($stripe, 'createCheckoutSession')) {
    echo '<p class="warning">⚠ Stripe PHP SDK is not loaded, but a fallback implementation is available.</p>';
} else {
    echo '<p class="error">✗ Stripe is not properly configured. Neither the SDK nor a fallback is available.</p>';
}

// Check API keys
echo '<h2>2. API Key Check</h2>';
$apiKey = $stripe_secret_key ?? 'Not set';
$maskedKey = substr($apiKey, 0, 8) . '...' . substr($apiKey, -4);
echo '<p>Secret Key: ' . $maskedKey . '</p>';

if (!$apiKey || $apiKey == 'Not set') {
    echo '<p class="error">✗ API key is missing.</p>';
} else if (strpos($apiKey, 'sk_test_') === 0) {
    echo '<p class="success">✓ Using test mode API key.</p>';
} else if (strpos($apiKey, 'sk_live_') === 0) {
    echo '<p class="success">✓ Using production mode API key.</p>';
} else {
    echo '<p class="error">✗ API key does not follow the expected format.</p>';
}

// Test creating a checkout session
echo '<h2>3. Checkout Session Test</h2>';

try {
    // Basic checkout session parameters
    $params = [
        'mode' => 'payment',
        'payment_method_types' => ['card'],
        'line_items' => [
            [
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => 2000,
                    'product_data' => [
                        'name' => 'Test Product',
                    ],
                ],
                'quantity' => 1,
            ],
        ],
        'success_url' => 'http://localhost/success',
        'cancel_url' => 'http://localhost/cancel',
    ];
    
    echo '<p>Attempting to create a test checkout session...</p>';
    
    // Try to create a session
    $session = null;
    if (class_exists('\Stripe\Stripe')) {
        $session = $stripe->checkout->sessions->create($params);
    } else if (isset($stripe) && method_exists($stripe, 'createCheckoutSession')) {
        $session = $stripe->createCheckoutSession($params);
    } else {
        throw new Exception("No method available to create checkout session");
    }
    
    // Check session properties
    echo '<p class="success">✓ Test checkout session created successfully!</p>';
    echo '<p>Session ID: ' . (is_object($session) ? $session->id : $session['id']) . '</p>';
    
    // Check URL property
    $hasUrl = false;
    $url = null;
    
    if (is_object($session)) {
        echo '<h3>Session object dump:</h3>';
        echo '<pre>' . print_r($session, true) . '</pre>';
        
        if (isset($session->url)) {
            $hasUrl = true;
            $url = $session->url;
            echo '<p class="success">✓ Session has "url" property.</p>';
        } else if (isset($session->checkout_url)) {
            $hasUrl = true;
            $url = $session->checkout_url;
            echo '<p class="success">✓ Session has "checkout_url" property.</p>';
        } else {
            echo '<p class="error">✗ Session object is missing URL property.</p>';
        }
        
    } else if (is_array($session)) {
        echo '<h3>Session array:</h3>';
        echo '<pre>' . print_r($session, true) . '</pre>';
        
        if (isset($session['url'])) {
            $hasUrl = true;
            $url = $session['url'];
            echo '<p class="success">✓ Session has "url" key.</p>';
        } else if (isset($session['checkout_url'])) {
            $hasUrl = true;
            $url = $session['checkout_url'];
            echo '<p class="success">✓ Session has "checkout_url" key.</p>';
        } else {
            echo '<p class="error">✗ Session array is missing URL key.</p>';
        }
    } else {
        echo '<p class="error">✗ Session is neither an object nor an array.</p>';
    }
    
    if ($hasUrl) {
        echo '<p>Checkout URL: ' . $url . '</p>';
        echo '<p><a href="' . $url . '" target="_blank">Click here to test the checkout session</a></p>';
    }
    
} catch (Exception $e) {
    echo '<p class="error">✗ Error creating checkout session: ' . $e->getMessage() . '</p>';
    echo '<p>Stack trace:</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

// Network connectivity check
echo '<h2>4. Network Connectivity Check</h2>';
$stripeHost = 'api.stripe.com';
$connected = @fsockopen($stripeHost, 443);
if ($connected) {
    fclose($connected);
    echo '<p class="success">✓ Connection to ' . $stripeHost . ' successful.</p>';
} else {
    echo '<p class="error">✗ Could not connect to ' . $stripeHost . '. Check your network connection and firewall settings.</p>';
}

// Direct test of process_stripe_checkout.php
echo '<h2>5. Process Stripe Checkout Script Test</h2>';

try {
    // Mock an order for testing
    // We'll use the first order we find for the current user
    $stmt = $conn->prepare("SELECT id FROM orders WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        $testOrderId = $order['id'];
        
        echo '<p>Found test order ID: ' . $testOrderId . '</p>';
        
        // Set up test data
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        // Save original output buffering state
        $originalBuffer = ob_get_level();
        
        // Start output buffering
        ob_start();
        
        // Route to the process script
        $jsonInput = json_encode(['order_id' => $testOrderId]);
        
        // Create a temporary file with the JSON input
        $tmpfile = tempnam(sys_get_temp_dir(), 'stripe_test');
        file_put_contents($tmpfile, $jsonInput);
        
        // Simulate the request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $GLOBALS['HTTP_RAW_POST_DATA'] = $jsonInput;
        
        // Include the script, capturing output
        include 'payments/process_stripe_checkout.php';
        
        // Get the output
        $output = ob_get_contents();
        
        // Restore original buffer state
        while (ob_get_level() > $originalBuffer) {
            ob_end_clean();
        }
        
        // Clean up
        unlink($tmpfile);
        
        echo '<p>Test completed. Raw output:</p>';
        echo '<pre>' . htmlspecialchars($output) . '</pre>';
        
        // Try to decode the JSON response
        $responseData = json_decode($output, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo '<p>JSON decoded successfully:</p>';
            echo '<pre>' . print_r($responseData, true) . '</pre>';
            
            if (isset($responseData['success']) && $responseData['success']) {
                echo '<p class="success">✓ Checkout processed successfully!</p>';
                
                if (isset($responseData['checkout_url'])) {
                    echo '<p>Checkout URL: ' . $responseData['checkout_url'] . '</p>';
                    echo '<p><a href="' . $responseData['checkout_url'] . '" target="_blank">Click here to test the checkout session</a></p>';
                } else {
                    echo '<p class="error">✗ No checkout URL in response.</p>';
                }
            } else {
                echo '<p class="error">✗ Checkout processing failed: ' . ($responseData['error'] ?? 'Unknown error') . '</p>';
            }
        } else {
            echo '<p class="error">✗ Response is not valid JSON. JSON error: ' . json_last_error_msg() . '</p>';
        }
    } else {
        echo '<p class="warning">⚠ No orders found for testing. Please create an order first.</p>';
    }
} catch (Exception $e) {
    echo '<p class="error">✗ Error testing process_stripe_checkout.php: ' . $e->getMessage() . '</p>';
}

// Finish HTML
echo '</body></html>';
