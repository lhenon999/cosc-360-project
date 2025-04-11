<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config.php';

// Create logs directory if it doesn't exist
$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Define log file
$logFile = $logDir . '/order_address_check.log';

function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;

echo "<h1>Order Address Check</h1>";

try {
    if (!$order_id) {
        throw new Exception("No order ID provided. Please add ?order_id=X to the URL.");
    }
    
    echo "<p>Checking order ID: $order_id</p>";
    logMessage("Checking order ID: $order_id");
    
    // Get order details
    $stmt = $conn->prepare("SELECT o.*, a.* FROM ORDERS o LEFT JOIN ADDRESSES a ON o.address_id = a.id WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Order not found");
    }
    
    $order = $result->fetch_assoc();
    
    echo "<h2>Order Details</h2>";
    echo "<ul>";
    echo "<li>Order ID: " . $order['id'] . "</li>";
    echo "<li>User ID: " . $order['user_id'] . "</li>";
    echo "<li>Total Price: $" . $order['total_price'] . "</li>";
    echo "<li>Status: " . $order['status'] . "</li>";
    echo "<li>Created: " . $order['created_at'] . "</li>";
    echo "<li>Address ID: " . ($order['address_id'] ? $order['address_id'] : 'None') . "</li>";
    echo "<li>Payment ID: " . ($order['payment_id'] ? $order['payment_id'] : 'None') . "</li>";
    echo "<li>Payment Method: " . ($order['payment_method'] ? $order['payment_method'] : 'None') . "</li>";
    echo "</ul>";
    
    // Check if address is associated
    if ($order['address_id']) {
        echo "<h2>Address Information</h2>";
        echo "<ul>";
        echo "<li>Street: " . $order['street_address'] . "</li>";
        echo "<li>City: " . $order['city'] . "</li>";
        echo "<li>State: " . $order['state'] . "</li>";
        echo "<li>Postal Code: " . $order['postal_code'] . "</li>";
        echo "<li>Country: " . $order['country'] . "</li>";
        echo "</ul>";
        
        logMessage("Order $order_id has address: " . $order['street_address'] . ", " . $order['city'] . ", " . $order['state'] . ", " . $order['postal_code'] . ", " . $order['country']);
    } else {
        echo "<h2 style='color:red'>No Address Found!</h2>";
        logMessage("Order $order_id has no address");
        
        // Check if there's a recent address for this user
        $stmt = $conn->prepare("SELECT * FROM ADDRESSES WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $order['user_id']);
        $stmt->execute();
        $addressResult = $stmt->get_result();
        
        if ($addressResult->num_rows > 0) {
            $address = $addressResult->fetch_assoc();
            
            echo "<h3>Latest address for user:</h3>";
            echo "<ul>";
            echo "<li>Address ID: " . $address['id'] . "</li>";
            echo "<li>Street: " . $address['street_address'] . "</li>";
            echo "<li>City: " . $address['city'] . "</li>";
            echo "<li>State: " . $address['state'] . "</li>";
            echo "<li>Postal Code: " . $address['postal_code'] . "</li>";
            echo "<li>Country: " . $address['country'] . "</li>";
            echo "<li>Created: " . $address['created_at'] . "</li>";
            echo "</ul>";
            
            echo "<p><a href='link_address.php?order_id=" . $order_id . "&address_id=" . $address['id'] . "' style='color:green;font-weight:bold;'>Link this address to the order</a></p>";
            
            logMessage("Found latest address (ID: " . $address['id'] . ") for user " . $order['user_id']);
        } else {
            echo "<p>No addresses found for this user.</p>";
            logMessage("No addresses found for user " . $order['user_id']);
        }
    }
    
    // Check webhook logs
    echo "<h2>Recent Webhook Logs for This Order</h2>";
    $cmd = "grep -i \"order_id\\\":\\\"" . $order_id . "\\\"\" " . $logDir . "/stripe_webhook.log | tail -n 20";
    $output = shell_exec($cmd);
    
    if ($output) {
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "<p>No webhook logs found for this order.</p>";
    }
    
    echo "<h2>Fix Options</h2>";
    echo "<p><a href='test_webhook_connection.php'>Test Webhook Connection</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>Error: " . $e->getMessage() . "</h2>";
    logMessage("Error: " . $e->getMessage());
}

// Also create a simple tool to link an address to an order
echo file_get_contents(__DIR__ . '/link_address.php');
?> 