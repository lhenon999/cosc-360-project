<?php
if (basename($_SERVER['PHP_SELF']) === 'link_address.php') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    require_once __DIR__ . '/../config.php';
    
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $logFile = $logDir . '/address_linking.log';
    
    echo "<h1>Link Address to Order</h1>";
    
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
    $address_id = isset($_GET['address_id']) ? intval($_GET['address_id']) : null;
    
    try {
        if (!$order_id || !$address_id) {
            throw new Exception("Missing order_id or address_id parameters");
        }
        
        $stmt = $conn->prepare("SELECT * FROM ORDERS WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Order not found");
        }
        
        $order = $result->fetch_assoc();

        $stmt = $conn->prepare("SELECT * FROM ADDRESSES WHERE id = ?");
        $stmt->bind_param("i", $address_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Address not found");
        }
        
        $address = $result->fetch_assoc();
        
        $stmt = $conn->prepare("UPDATE ORDERS SET address_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $address_id, $order_id);
        
        if ($stmt->execute()) {
            echo "<p style='color:green;font-weight:bold;'>Successfully linked address to order!</p>";
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Manually linked address ID $address_id to order ID $order_id\n", FILE_APPEND);
            
            echo "<h2>Updated Order Details</h2>";
            echo "<ul>";
            echo "<li>Order ID: " . $order['id'] . "</li>";
            echo "<li>User ID: " . $order['user_id'] . "</li>";
            echo "<li>New Address ID: " . $address_id . "</li>";
            echo "</ul>";
            
            echo "<h2>Address Details</h2>";
            echo "<ul>";
            echo "<li>Address ID: " . $address['id'] . "</li>";
            echo "<li>Street: " . $address['street_address'] . "</li>";
            echo "<li>City: " . $address['city'] . "</li>";
            echo "<li>State: " . $address['state'] . "</li>";
            echo "<li>Postal Code: " . $address['postal_code'] . "</li>";
            echo "<li>Country: " . $address['country'] . "</li>";
            echo "</ul>";
            
            echo "<p><a href='check_order_address.php?order_id=$order_id'>Back to Order Details</a></p>";
        } else {
            throw new Exception("Failed to link address: " . $stmt->error);
        }
    } catch (Exception $e) {
        echo "<h2 style='color:red'>Error: " . $e->getMessage() . "</h2>";
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
        
        echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
    }
}
?> 