<?php
// Test script for direct testing of process_stripe_checkout.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Use a default user ID for testing
}

require_once 'config.php';
require_once 'config/stripe.php';

// Get the first available order for testing
$stmt = $conn->prepare("SELECT o.id, o.total_price, u.email 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id
                        WHERE o.user_id = ? AND o.address_id IS NOT NULL
                        LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No orders found for testing. Please create an order with a shipping address first.");
}

$order = $result->fetch_assoc();
$testOrderId = $order['id'];
$userEmail = $order['email'];
$totalPrice = $order['total_price'];

echo "Found test order #$testOrderId for user $userEmail with price \${$totalPrice}\n\n";

// Define necessary inputs for the process_stripe_checkout.php script
$inputData = [
    'order_id' => $testOrderId
];

echo "Sending request to process_stripe_checkout.php with data: " . json_encode($inputData) . "\n\n";

// Simulate a POST request with JSON content type
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

// Put the JSON input into the php://input stream
$inputJson = json_encode($inputData);
$GLOBALS['HTTP_RAW_POST_DATA'] = $inputJson;

// Start output buffering to capture the response
ob_start();

// Define a custom function to replace file_get_contents('php://input')
function custom_file_get_contents($path) {
    global $inputJson;
    if ($path === 'php://input') {
        return $inputJson;
    }
    return file_get_contents($path);
}

// Override the file_get_contents function
function file_get_contents($path) {
    return custom_file_get_contents($path);
}

// Include the script
include 'payments/process_stripe_checkout.php';

// Get the output
$output = ob_get_clean();

echo "Raw Response:\n";
echo $output . "\n\n";

// Try to decode the response as JSON
$responseData = json_decode($output, true);

if (json_last_error() === JSON_ERROR_NONE) {
    echo "Decoded Response:\n";
    print_r($responseData);
    
    if (isset($responseData['success']) && $responseData['success']) {
        echo "\nCheckout session created successfully!\n";
        echo "Session ID: " . $responseData['session_id'] . "\n";
        echo "Checkout URL: " . $responseData['checkout_url'] . "\n";
    } else {
        echo "\nError creating checkout session: " . ($responseData['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "Error decoding JSON response: " . json_last_error_msg() . "\n";
} 