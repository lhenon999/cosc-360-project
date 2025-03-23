<?php
/**
 * Helper script for testing the Stripe webhook locally
 * Run this file to simulate a webhook event
 */

// Define the paths
$webhookJsonPath = __DIR__ . '/test_webhook.json';
$webhookEndpoint = 'http://localhost/cosc-360-project/handmade_goods/payments/stripe_webhook.php';

// Read the webhook data
if (!file_exists($webhookJsonPath)) {
    die('Error: test_webhook.json not found. Please ensure the file exists in the same directory.');
}

$webhookData = file_get_contents($webhookJsonPath);
$jsonData = json_decode($webhookData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die('Error parsing webhook JSON: ' . json_last_error_msg());
}

// Modify order_id if provided
if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $jsonData['data']['object']['metadata']['order_id'] = $_GET['order_id'];
    $webhookData = json_encode($jsonData, JSON_PRETTY_PRINT);
    echo "Using order_id: " . $_GET['order_id'] . "\n";
}

// Initialize cURL session
$ch = curl_init($webhookEndpoint);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $webhookData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($webhookData),
    // We're not setting a Stripe signature since we disabled verification for testing
]);

echo "Sending webhook data to $webhookEndpoint...\n";

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Close the connection
curl_close($ch);

// Output the results
echo "HTTP Status Code: $httpCode\n";

if ($error) {
    echo "Error: $error\n";
} else {
    echo "Response: $response\n";
    echo "\nWebhook test completed. Check the logs/stripe_webhook.log file for detailed results.\n";
    echo "If successful, the address should now be stored in your database.\n";
    echo "\nYou can verify in your database with this SQL query:\n";
    echo "SELECT * FROM addresses ORDER BY id DESC LIMIT 1;\n";
    echo "\nAnd check if it's linked to your order with:\n";
    echo "SELECT o.id, o.address_id, a.street_address, a.city FROM orders o JOIN addresses a ON o.address_id = a.id WHERE o.id = " . ($jsonData['data']['object']['metadata']['order_id'] ?? '1') . ";\n";
}
?>