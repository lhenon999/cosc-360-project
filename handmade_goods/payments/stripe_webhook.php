<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';
require_once '../stripe/stripe.php';

// Create logs directory if it doesn't exist
$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Define log file
$logFile = $logDir . '/stripe_webhook.log';

// Check if payment_id column exists in orders table and add it if missing
function ensurePaymentColumns($conn, $logFile) {
    // Check if payment_id column exists
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_id'");
    $paymentIdExists = $result->num_rows > 0;
    
    if (!$paymentIdExists) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Adding missing payment_id column to orders table\n", FILE_APPEND);
        $conn->query("ALTER TABLE orders ADD COLUMN payment_id VARCHAR(255) DEFAULT NULL");
    }
    
    // Check if payment_method column exists
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
    $paymentMethodExists = $result->num_rows > 0;
    
    if (!$paymentMethodExists) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Adding missing payment_method column to orders table\n", FILE_APPEND);
        $conn->query("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL");
    }
    
    return $paymentIdExists && $paymentMethodExists;
}

// Ensure required columns exist
ensurePaymentColumns($conn, $logFile);

// Enhanced webhook logging function
function logWebhookEvent($event_type, $event_id, $status = 'success', $error = null) {
    global $logFile;
    
    $log_message = sprintf(
        "[%s] %s - Stripe Webhook: [%s] Event: %s (ID: %s) %s",
        date('Y-m-d H:i:s'),
        $_SERVER['REMOTE_ADDR'],
        $status,
        $event_type,
        $event_id,
        $error ? "Error: " . $error : ""
    );
    
    // Add detailed logging for shipping address handling
    if ($event_type === 'checkout.session.completed') {
        $log_message .= "\nSession data: " . print_r($GLOBALS['event']['data']['object'] ?? [], true);
        if (isset($GLOBALS['event']['data']['object']['shipping'])) {
            $log_message .= "\nShipping data: " . print_r($GLOBALS['event']['data']['object']['shipping'], true);
        } else {
            $log_message .= "\nNo shipping data in session";
        }
    }
    
    // Log to both PHP error log and our custom log file
    error_log($log_message);
    file_put_contents($logFile, $log_message . "\n\n", FILE_APPEND);
}

// Verify webhook signature
function verifyWebhookSignature($payload, $sig_header, $secret) {
    global $logFile;
    
    if (empty($sig_header)) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - No signature header found\n", FILE_APPEND);
        return false;
    }
    
    try {
        $timestamp = null;
        $signature = null;
        
        // Parse header
        foreach (explode(',', $sig_header) as $item) {
            if (strpos($item, '=') === false) continue;
            
            list($key, $value) = explode('=', $item, 2);
            $key = trim($key);
            $value = trim($value);
            
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1') {
                $signature = $value;
            }
        }
        
        if (empty($timestamp) || empty($signature)) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Missing timestamp or signature in header: " . $sig_header . "\n", FILE_APPEND);
            return false;
        }
        
        // Check if timestamp is within tolerance (default: 5 minutes)
        $now = time();
        if (($now - $timestamp) > (5 * 60)) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Webhook timestamp too old: " . $timestamp . " (now: " . $now . ")\n", FILE_APPEND);
            return false;
        }
        
        // Create expected signature
        $signed_payload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signed_payload, $secret);
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Expected signature: " . $expected . "\n", FILE_APPEND);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Received signature: " . $signature . "\n", FILE_APPEND);
        
        return hash_equals($expected, $signature);
    } catch (Exception $e) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error verifying webhook signature: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

// Log all incoming requests
file_put_contents($logFile, "\n\n" . date('Y-m-d H:i:s') . " - New webhook request received\n", FILE_APPEND);
file_put_contents($logFile, "Remote IP: " . $_SERVER['REMOTE_ADDR'] . "\n", FILE_APPEND);
file_put_contents($logFile, "HTTP Headers: " . print_r(getallheaders(), true) . "\n", FILE_APPEND);

$payload = @file_get_contents('php://input');
file_put_contents($logFile, "Raw payload: " . $payload . "\n", FILE_APPEND);

// For testing: allow local webhooks without signature during development
$is_dev_mode = ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1');
$webhook_secret = 'whsec_6a9b6dbb671c8fc8cf891c8c609112daa6ccc51bcef292a40895b2566fd379ae';

$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
file_put_contents($logFile, "Received webhook with signature: " . $sig_header . "\n", FILE_APPEND);

try {
    // Handle development mode without signature
    if ($is_dev_mode && empty($sig_header)) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Bypassing signature verification for testing\n", FILE_APPEND);
    } 
    // Verify webhook signature in production
    else if (!empty($sig_header) && !verifyWebhookSignature($payload, $sig_header, $webhook_secret)) {
        throw new Exception('Invalid signature');
    }

    $event = json_decode($payload, true);
    if ($event === null) {
        throw new Exception('Invalid payload: ' . json_last_error_msg());
    }

    logWebhookEvent($event['type'] ?? 'unknown', $event['id'] ?? 'no-id');
    
    // Store event for detailed logging
    $GLOBALS['event'] = $event;

    // Handle the event
    switch ($event['type'] ?? '') {
        case 'checkout.session.completed':
            $session = $event['data']['object'];
            $orderId = $session['metadata']['order_id'] ?? null;
            
            if (!$orderId) {
                throw new Exception("Order ID not found in metadata");
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Get order details to identify the user
                $stmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $result = $stmt->get_result();
                $order = $result->fetch_assoc();
                $stmt->close();

                if (!$order) {
                    throw new Exception("Order not found: " . $orderId);
                }

                file_put_contents($logFile, "Processing order: " . $orderId . ", User ID: " . $order['user_id'] . "\n", FILE_APPEND);
                file_put_contents($logFile, "Full session data: " . json_encode($session) . "\n", FILE_APPEND);

                // Save shipping address if available - more robust extraction
                if (isset($session['shipping']) && is_array($session['shipping'])) {
                    $shipping = $session['shipping'];
                    file_put_contents($logFile, "Shipping data found: " . json_encode($shipping) . "\n", FILE_APPEND);
                    
                    if (isset($shipping['address']) && is_array($shipping['address'])) {
                        $address = $shipping['address'];
                        file_put_contents($logFile, "Address data: " . json_encode($address) . "\n", FILE_APPEND);
                        
                        // Extract all possible fields with defaults
                        $line1 = $address['line1'] ?? '';
                        $line2 = $address['line2'] ?? '';
                        $city = $address['city'] ?? '';
                        $state = $address['state'] ?? '';
                        $postal_code = $address['postal_code'] ?? '';
                        $country = $address['country'] ?? '';
                        
                        // Only proceed if we have the minimum required fields
                        if (!empty($line1) && !empty($city)) {
                            try {
                                $stmt = $conn->prepare("
                                    INSERT INTO addresses (
                                        user_id, 
                                        street_address, 
                                        city, 
                                        state, 
                                        postal_code, 
                                        country
                                    ) VALUES (?, ?, ?, ?, ?, ?)
                                ");
                                
                                // Build street address
                                $street = $line1;
                                if (!empty($line2)) {
                                    $street .= ', ' . $line2;
                                }
                                
                                file_put_contents($logFile, "Inserting address: " . json_encode([
                                    'user_id' => $order['user_id'],
                                    'street' => $street,
                                    'city' => $city,
                                    'state' => $state,
                                    'postal_code' => $postal_code,
                                    'country' => $country
                                ]) . "\n", FILE_APPEND);
                                
                                $stmt->bind_param("isssss", 
                                    $order['user_id'],
                                    $street,
                                    $city,
                                    $state,
                                    $postal_code,
                                    $country
                                );
                                
                                if (!$stmt->execute()) {
                                    file_put_contents($logFile, "Failed to insert address: " . $stmt->error . "\n", FILE_APPEND);
                                } else {
                                    $address_id = $stmt->insert_id;
                                    file_put_contents($logFile, "Successfully inserted address with ID: " . $address_id . "\n", FILE_APPEND);
                                    $stmt->close();
                                    
                                    // Link address to order
                                    if ($address_id) {
                                        try {
                                            $stmt = $conn->prepare("UPDATE orders SET address_id = ? WHERE id = ?");
                                            $stmt->bind_param("ii", $address_id, $orderId);
                                            if (!$stmt->execute()) {
                                                file_put_contents($logFile, "Failed to link address to order: " . $stmt->error . "\n", FILE_APPEND);
                                            } else {
                                                file_put_contents($logFile, "Successfully linked address to order\n", FILE_APPEND);
                                            }
                                            $stmt->close();
                                        } catch (Exception $e) {
                                            file_put_contents($logFile, "Exception linking address to order: " . $e->getMessage() . "\n", FILE_APPEND);
                                        }
                                    }
                                }
                            } catch (Exception $e) {
                                file_put_contents($logFile, "Exception inserting address: " . $e->getMessage() . "\n", FILE_APPEND);
                            }
                        } else {
                            file_put_contents($logFile, "Missing critical address fields: " . json_encode($address) . "\n", FILE_APPEND);
                        }
                    } else {
                        file_put_contents($logFile, "No address object in shipping data\n", FILE_APPEND);
                    }
                } else if (isset($session['customer_details']) && isset($session['customer_details']['address'])) {
                    // Fallback to customer details address if shipping address is not available
                    $address = $session['customer_details']['address'];
                    file_put_contents($logFile, "Using customer_details address as fallback: " . json_encode($address) . "\n", FILE_APPEND);
                    
                    // Extract all possible fields with defaults
                    $line1 = $address['line1'] ?? '';
                    $line2 = $address['line2'] ?? '';
                    $city = $address['city'] ?? '';
                    $state = $address['state'] ?? '';
                    $postal_code = $address['postal_code'] ?? '';
                    $country = $address['country'] ?? '';
                    
                    // Only proceed if we have the minimum required fields
                    if (!empty($line1) && !empty($city)) {
                        try {
                            $stmt = $conn->prepare("
                                INSERT INTO addresses (
                                    user_id, 
                                    street_address, 
                                    city, 
                                    state, 
                                    postal_code, 
                                    country
                                ) VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            
                            // Build street address
                            $street = $line1;
                            if (!empty($line2)) {
                                $street .= ', ' . $line2;
                            }
                            
                            file_put_contents($logFile, "Inserting address from customer_details: " . json_encode([
                                'user_id' => $order['user_id'],
                                'street' => $street,
                                'city' => $city,
                                'state' => $state,
                                'postal_code' => $postal_code,
                                'country' => $country
                            ]) . "\n", FILE_APPEND);
                            
                            $stmt->bind_param("isssss", 
                                $order['user_id'],
                                $street,
                                $city,
                                $state,
                                $postal_code,
                                $country
                            );
                            
                            if (!$stmt->execute()) {
                                file_put_contents($logFile, "Failed to insert address: " . $stmt->error . "\n", FILE_APPEND);
                            } else {
                                $address_id = $stmt->insert_id;
                                file_put_contents($logFile, "Successfully inserted address with ID: " . $address_id . "\n", FILE_APPEND);
                                $stmt->close();
                                
                                // Link address to order
                                if ($address_id) {
                                    try {
                                        $stmt = $conn->prepare("UPDATE orders SET address_id = ? WHERE id = ?");
                                        $stmt->bind_param("ii", $address_id, $orderId);
                                        if (!$stmt->execute()) {
                                            file_put_contents($logFile, "Failed to link address to order: " . $stmt->error . "\n", FILE_APPEND);
                                        } else {
                                            file_put_contents($logFile, "Successfully linked address to order\n", FILE_APPEND);
                                        }
                                        $stmt->close();
                                    } catch (Exception $e) {
                                        file_put_contents($logFile, "Exception linking address to order: " . $e->getMessage() . "\n", FILE_APPEND);
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            file_put_contents($logFile, "Exception inserting address: " . $e->getMessage() . "\n", FILE_APPEND);
                        }
                    }
                } else {
                    file_put_contents($logFile, "No shipping or customer_details address found in session\n", FILE_APPEND);
                }

                // Continue with the rest of the order processing...
                // Get order items to update inventory
                $stmt = $conn->prepare("
                    SELECT oi.item_id, oi.quantity 
                    FROM order_items oi 
                    WHERE oi.order_id = ?
                ");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Update inventory for each item
                foreach ($items as $item) {
                    // Get current stock before update
                    $stmt = $conn->prepare("SELECT stock, name FROM items WHERE id = ?");
                    $stmt->bind_param("i", $item['item_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $product = $result->fetch_assoc();
                    $currentStock = $product['stock'];
                    $productName = $product['name'];
                    $stmt->close();
                    
                    file_put_contents($logFile, "Updating inventory for item ID {$item['item_id']} ({$productName}): Current stock: {$currentStock}, Quantity purchased: {$item['quantity']}\n", FILE_APPEND);
                    
                    // Update the stock
                    $stmt = $conn->prepare("
                        UPDATE items 
                        SET stock = GREATEST(0, stock - ?) 
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ii", $item['quantity'], $item['item_id']);
                    $stmt->execute();
                    
                    // Get new stock after update
                    $stmt = $conn->prepare("SELECT stock FROM items WHERE id = ?");
                    $stmt->bind_param("i", $item['item_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $product = $result->fetch_assoc();
                    $newStock = $product['stock'];
                    
                    file_put_contents($logFile, "Successfully updated inventory for item {$item['item_id']} ({$productName}). Previous stock: {$currentStock}, New stock: {$newStock}\n", FILE_APPEND);
                    
                    $stmt->close();
                }

                // Clear the user's cart
                $stmt = $conn->prepare("
                    DELETE ci FROM cart_items ci
                    JOIN cart c ON ci.cart_id = c.id
                    WHERE c.user_id = ?
                ");
                $stmt->bind_param("i", $order['user_id']);
                $stmt->execute();
                $stmt->close();

                // Update order status
                $stmt = $conn->prepare("
                    UPDATE orders 
                    SET status = 'Processing', 
                        payment_id = ?,
                        payment_method = ?
                    WHERE id = ?
                ");
                
                // First try to get payment_intent from various possible locations in the session object
                $paymentIntent = '';
                if (!empty($session['payment_intent'])) {
                    $paymentIntent = $session['payment_intent'];
                } elseif (!empty($session['id'])) {
                    // Use session ID as fallback
                    $paymentIntent = $session['id'];
                } elseif (!empty($session['object']) && $session['object'] === 'checkout.session' && !empty($session['payment_intent'])) {
                    $paymentIntent = $session['payment_intent'];
                }
                
                // Ensure we have a value for paymentIntent - at minimum use session ID if available
                if (empty($paymentIntent) && !empty($session['id'])) {
                    $paymentIntent = 'cs_' . $session['id'];
                }
                
                // For debugging
                file_put_contents($logFile, "Session data analysis:\n", FILE_APPEND);
                file_put_contents($logFile, "- id: " . ($session['id'] ?? 'NULL') . "\n", FILE_APPEND);
                file_put_contents($logFile, "- payment_intent: " . ($session['payment_intent'] ?? 'NULL') . "\n", FILE_APPEND);
                file_put_contents($logFile, "- object: " . ($session['object'] ?? 'NULL') . "\n", FILE_APPEND);
                
                $paymentMethod = $session['payment_method_types'][0] ?? 'card';
                
                file_put_contents($logFile, "Updating order with payment details: PaymentIntent=" . $paymentIntent . ", Method=" . $paymentMethod . "\n", FILE_APPEND);
                
                $stmt->bind_param("ssi", $paymentIntent, $paymentMethod, $orderId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update order payment details: " . $stmt->error);
                }
                $stmt->close();

                // Commit all database changes
                $conn->commit();
                file_put_contents($logFile, "Transaction committed successfully\n", FILE_APPEND);
                
                // Additional verification
                $stmt = $conn->prepare("SELECT payment_id, payment_method, address_id FROM orders WHERE id = ?");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $result = $stmt->get_result();
                $updatedOrder = $result->fetch_assoc();
                $stmt->close();
                
                file_put_contents($logFile, "Verification after commit - Order #$orderId: payment_id=" . 
                    ($updatedOrder['payment_id'] ?? 'NULL') . ", payment_method=" . 
                    ($updatedOrder['payment_method'] ?? 'NULL') . ", address_id=" . 
                    ($updatedOrder['address_id'] ?? 'NULL') . "\n", FILE_APPEND);
                
            } catch (Exception $e) {
                $conn->rollback();
                file_put_contents($logFile, "Transaction rolled back: " . $e->getMessage() . "\n", FILE_APPEND);
                throw $e;
            }
            break;

        // Handle other event types
        case 'charge.refunded':
            $charge = $event['data']['object'];
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'Refunded'
                WHERE payment_id = ?
            ");
            $stmt->bind_param("s", $charge['payment_intent']);
            $stmt->execute();
            $stmt->close();
            break;

        default:
            logWebhookEvent($event['type'] ?? 'unknown', $event['id'] ?? 'no-id', 'unhandled');
            break;
    }

    http_response_code(200);
    file_put_contents($logFile, "Webhook processed successfully with 200 response\n", FILE_APPEND);
} catch (Exception $e) {
    logWebhookEvent('error', 'N/A', 'failed', $e->getMessage());
    http_response_code(400);
    file_put_contents($logFile, 'Webhook Error: ' . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['error' => $e->getMessage()]);
}