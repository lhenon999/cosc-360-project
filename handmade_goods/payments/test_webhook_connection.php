<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';

// Create logs directory if it doesn't exist
$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Define log file
$logFile = $logDir . '/webhook_test.log';

echo "<h1>Webhook Test</h1>";

try {
    // Check if payment_id column exists in orders table
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_id'");
    $paymentIdExists = $result->num_rows > 0;
    
    echo "<p>Payment ID column exists: " . ($paymentIdExists ? 'Yes' : 'No') . "</p>";
    
    if (!$paymentIdExists) {
        echo "<p>Adding payment_id column to orders table...</p>";
        $conn->query("ALTER TABLE orders ADD COLUMN payment_id VARCHAR(255) DEFAULT NULL");
        
        // Verify it was added
        $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_id'");
        $paymentIdExists = $result->num_rows > 0;
        echo "<p>Payment ID column now exists: " . ($paymentIdExists ? 'Yes' : 'No') . "</p>";
    }
    
    // Check if payment_method column exists
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
    $paymentMethodExists = $result->num_rows > 0;
    
    echo "<p>Payment Method column exists: " . ($paymentMethodExists ? 'Yes' : 'No') . "</p>";
    
    if (!$paymentMethodExists) {
        echo "<p>Adding payment_method column to orders table...</p>";
        $conn->query("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL");
        
        // Verify it was added
        $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
        $paymentMethodExists = $result->num_rows > 0;
        echo "<p>Payment Method column now exists: " . ($paymentMethodExists ? 'Yes' : 'No') . "</p>";
    }
    
    // Create a test record to check if we can save to these fields
    $testOrderId = 999999; // Using a high number unlikely to conflict
    
    // Check if test order exists
    $result = $conn->query("SELECT id FROM orders WHERE id = $testOrderId");
    if ($result->num_rows == 0) {
        // Create test order
        echo "<p>Creating test order...</p>";
        $userId = 1; // Assuming user ID 1 exists (admin)
        $conn->query("INSERT INTO orders (id, user_id, total_price, status) VALUES ($testOrderId, $userId, 1.00, 'Pending')");
    }
    
    // Try to update payment fields
    $testPaymentId = "pi_test_" . time();
    $testPaymentMethod = "card";
    
    $stmt = $conn->prepare("UPDATE orders SET payment_id = ?, payment_method = ? WHERE id = ?");
    $stmt->bind_param("ssi", $testPaymentId, $testPaymentMethod, $testOrderId);
    $result = $stmt->execute();
    
    if ($result) {
        echo "<p>Successfully updated payment fields in test order.</p>";
    } else {
        echo "<p>Failed to update payment fields: " . $stmt->error . "</p>";
    }
    
    // Verify data was saved
    $result = $conn->query("SELECT payment_id, payment_method FROM orders WHERE id = $testOrderId");
    if ($row = $result->fetch_assoc()) {
        echo "<p>Verification:<br>
              - Payment ID: " . ($row['payment_id'] ?? 'NULL') . "<br>
              - Payment Method: " . ($row['payment_method'] ?? 'NULL') . "</p>";
              
        if ($row['payment_id'] == $testPaymentId && $row['payment_method'] == $testPaymentMethod) {
            echo "<h2 style='color:green'>TEST PASSED! Database is working correctly.</h2>";
        } else {
            echo "<h2 style='color:red'>TEST FAILED! Data mismatch.</h2>";
        }
    } else {
        echo "<h2 style='color:red'>TEST FAILED! Could not retrieve test order.</h2>";
    }
    
    // Clean up test order (optional)
    // $conn->query("DELETE FROM orders WHERE id = $testOrderId");
    
    echo "<h2>Webhook URL Info</h2>";
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $webhookUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/stripe_webhook.php';
    
    echo "<p>Your webhook URL should be: <code>$webhookUrl</code></p>";
    echo "<p>Please make sure this URL is set in your Stripe Dashboard under Developers > Webhooks.</p>";
    
} catch (Exception $e) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo "<h2 style='color:red'>Error: " . $e->getMessage() . "</h2>";
} 