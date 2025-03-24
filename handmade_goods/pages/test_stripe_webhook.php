<?php
session_start();
require_once '../config.php';
require_once '../config/stripe.php';

// Ensure only admins or developers can access this page
if (!isset($_SESSION["user_id"]) || ($_SESSION["user_id"] != 1 && !isset($_SESSION["is_admin"]))) {
    echo "Unauthorized access";
    exit;
}

// Create logs directory if it doesn't exist
$logDir = dirname(dirname(__FILE__)) . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Define log file
$logFile = $logDir . '/stripe_webhook_test.log';

function log_message($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Initialize variables
$success_message = '';
$error_message = '';

// Check and fix Stripe webhook configuration
function checkStripeWebhookConfiguration() {
    global $conn, $success_message, $error_message;
    
    // 1. Check if payment_id and payment_method columns exist in orders table
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_id'");
    $paymentIdExists = $result->num_rows > 0;
    
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
    $paymentMethodExists = $result->num_rows > 0;
    
    if (!$paymentIdExists || !$paymentMethodExists) {
        try {
            $conn->begin_transaction();
            
            if (!$paymentIdExists) {
                $conn->query("ALTER TABLE orders ADD COLUMN payment_id VARCHAR(255) DEFAULT NULL");
                log_message("Added payment_id column to orders table");
                $success_message .= "Added payment_id column to orders table<br>";
            }
            
            if (!$paymentMethodExists) {
                $conn->query("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL");
                log_message("Added payment_method column to orders table");
                $success_message .= "Added payment_method column to orders table<br>";
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating database schema: " . $e->getMessage();
            log_message("Error: " . $e->getMessage());
        }
    }
}

// Function to simulate a webhook event for testing
function simulateWebhookEvent() {
    global $conn, $stripe_publishable_key, $success_message, $error_message;
    
    // Get the most recent pending order
    $stmt = $conn->prepare("
        SELECT o.id, o.user_id, o.address_id, o.total_price 
        FROM orders o 
        WHERE o.status = 'Pending' AND o.payment_id IS NULL
        ORDER BY o.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error_message = "No pending orders found to simulate webhook event";
        log_message($error_message);
        return;
    }
    
    $order = $result->fetch_assoc();
    $orderId = $order['id'];
    
    // Create a mock Stripe event
    $mockEvent = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_' . substr(md5(uniqid()), 0, 24),
                'metadata' => [
                    'order_id' => $orderId
                ],
                'payment_intent' => 'pi_test_' . substr(md5(uniqid()), 0, 24),
                'payment_method_types' => ['card'],
                'customer_details' => [
                    'email' => 'test@example.com',
                    'name' => 'Test Customer'
                ]
            ]
        ]
    ];
    
    // Update the order directly to simulate webhook processing
    try {
        $conn->begin_transaction();
        
        // Get order items to update inventory
        $stmt = $conn->prepare("
            SELECT oi.item_id, oi.item_name, oi.quantity 
            FROM order_items oi 
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Store stock changes for display
        $stockUpdates = [];
        
        // Update inventory for each item
        foreach ($items as $item) {
            // Get current stock
            $stmt = $conn->prepare("SELECT stock FROM items WHERE id = ?");
            $stmt->bind_param("i", $item['item_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $oldStock = $product['stock'];
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
            
            // Get new stock
            $stmt = $conn->prepare("SELECT stock FROM items WHERE id = ?");
            $stmt->bind_param("i", $item['item_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $newStock = $product['stock'];
            $stmt->close();
            
            $stockUpdates[] = [
                'item_id' => $item['item_id'],
                'item_name' => $item['item_name'],
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'quantity' => $item['quantity']
            ];
        }
        
        // Update order status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = 'Processing', 
                payment_id = ?,
                payment_method = ?
            WHERE id = ?
        ");
        
        $paymentIntent = $mockEvent['data']['object']['payment_intent'];
        $paymentMethod = 'card';
        
        $stmt->bind_param("ssi", $paymentIntent, $paymentMethod, $orderId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update order payment details: " . $stmt->error);
        }
        
        $conn->commit();
        
        // Build success message with inventory details
        $success_message = "<strong>Successfully simulated webhook for Order #$orderId</strong><br>";
        $success_message .= "Order status updated to 'Processing'.<br>";
        $success_message .= "<hr><strong>Inventory Updates:</strong><ul>";
        
        foreach ($stockUpdates as $update) {
            $status = '';
            if ($update['new_stock'] <= 5 && $update['new_stock'] > 0) {
                $status = '<span class="text-warning">Low Stock!</span>';
            } elseif ($update['new_stock'] == 0) {
                $status = '<span class="text-danger">Out of Stock!</span>';
            }
            
            $success_message .= "<li><strong>{$update['item_name']}</strong>: " .
                "Stock changed from {$update['old_stock']} to {$update['new_stock']} " .
                "({$update['quantity']} purchased) $status</li>";
        }
        
        $success_message .= "</ul>";
        
        log_message("Successfully simulated webhook for Order #$orderId with inventory updates");
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error simulating webhook: " . $e->getMessage();
        log_message("Error: " . $e->getMessage());
    }
}

// Function to fix orders with missing address_id
function fixOrderAddresses() {
    global $conn, $success_message, $error_message;
    
    try {
        // Get users with pending orders that have NULL address_id
        $stmt = $conn->prepare("
            SELECT DISTINCT o.user_id
            FROM orders o
            WHERE o.address_id IS NULL
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        
        if (empty($users)) {
            $success_message = "No orders with missing addresses found.";
            return;
        }
        
        $fixedCount = 0;
        
        foreach ($users as $user) {
            $userId = $user['user_id'];
            
            // Get the user's most recent address
            $stmt = $conn->prepare("
                SELECT id 
                FROM addresses 
                WHERE user_id = ? 
                ORDER BY id DESC 
                LIMIT 1
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $address = $result->fetch_assoc();
                $addressId = $address['id'];
                
                // Update all orders for this user where address_id is NULL
                $stmt = $conn->prepare("
                    UPDATE orders 
                    SET address_id = ? 
                    WHERE user_id = ? AND address_id IS NULL
                ");
                $stmt->bind_param("ii", $addressId, $userId);
                $stmt->execute();
                
                $fixedCount += $stmt->affected_rows;
            }
        }
        
        if ($fixedCount > 0) {
            $success_message = "Fixed $fixedCount orders with missing address IDs.";
            log_message("Fixed $fixedCount orders with missing address IDs");
        } else {
            $success_message = "No orders could be fixed. Users might not have any saved addresses.";
            log_message("No orders could be fixed. Users might not have any saved addresses.");
        }
        
    } catch (Exception $e) {
        $error_message = "Error fixing order addresses: " . $e->getMessage();
        log_message("Error: " . $e->getMessage());
    }
}

// Function to test inventory updates manually
function testInventoryUpdate() {
    global $conn, $success_message, $error_message;
    
    if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
        $error_message = "Missing product ID or quantity";
        return;
    }
    
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity <= 0) {
        $error_message = "Quantity must be greater than 0";
        return;
    }
    
    try {
        // Get current product info
        $stmt = $conn->prepare("SELECT id, name, stock FROM items WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error_message = "Product not found";
            return;
        }
        
        $product = $result->fetch_assoc();
        $oldStock = $product['stock'];
        $productName = $product['name'];
        
        // Update stock
        $stmt = $conn->prepare("UPDATE items SET stock = GREATEST(0, stock - ?) WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $product_id);
        $stmt->execute();
        
        // Get updated stock
        $stmt = $conn->prepare("SELECT stock FROM items WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $newStock = $product['stock'];
        
        $status = '';
        if ($newStock <= 5 && $newStock > 0) {
            $status = '<span class="text-warning">Low Stock!</span>';
        } elseif ($newStock === 0) {
            $status = '<span class="text-danger">Out of Stock!</span>';
        }
        
        $success_message = "Successfully updated inventory for <strong>{$productName}</strong>.<br>";
        $success_message .= "Stock changed from {$oldStock} to {$newStock} ({$quantity} units) {$status}";
        
        log_message("Manual inventory update: Product ID {$product_id} ({$productName}): {$oldStock} -> {$newStock}");
        
    } catch (Exception $e) {
        $error_message = "Error updating inventory: " . $e->getMessage();
        log_message("Error: " . $e->getMessage());
    }
}

// Process actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'check_and_fix') {
        checkStripeWebhookConfiguration();
    } elseif ($_POST['action'] === 'simulate_webhook') {
        simulateWebhookEvent();
    } elseif ($_POST['action'] === 'fix_addresses') {
        fixOrderAddresses();
    } elseif ($_POST['action'] === 'test_inventory') {
        testInventoryUpdate();
    } elseif ($_POST['action'] === 'force_update_scarf') {
        // Force update the Knitted Scarf stock (ID 2)
        try {
            $conn->begin_transaction();
            
            // Get current stock of knitted scarf
            $stmt = $conn->prepare("SELECT stock, name FROM items WHERE id = 2");
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $oldStock = $product['stock'];
            $productName = $product['name'];
            
            // Force update to one less
            $newStock = max(0, $oldStock - 1);
            $stmt = $conn->prepare("UPDATE items SET stock = ? WHERE id = 2");
            $stmt->bind_param("i", $newStock);
            $stmt->execute();
            
            $conn->commit();
            
            $success_message = "Successfully forced stock update for {$productName}.<br>";
            $success_message .= "Stock changed from {$oldStock} to {$newStock}";
            
            log_message("Forced stock update for Knitted Scarf: {$oldStock} -> {$newStock}");
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating scarf stock: " . $e->getMessage();
            log_message("Error: " . $e->getMessage());
        }
    }
}

// Get recent orders for display
$orders = $conn->query("
    SELECT o.id, o.user_id, o.address_id, o.total_price, o.status, o.created_at, 
           o.payment_id, o.payment_method
    FROM orders o
    ORDER BY o.id DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Webhook Test</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Test and Debug Stripe Webhook</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Check Webhook Configuration</h5>
                    </div>
                    <div class="card-body">
                        <p>This will check if your Stripe webhook is properly configured.</p>
                        
                        <h6>Webhook URL</h6>
                        <p>Make sure your Stripe webhook is configured with this URL:</p>
                        <code><?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/../payments/stripe_webhook.php') ?></code>
                        
                        <h6>Required Events</h6>
                        <ul>
                            <li><code>checkout.session.completed</code> (most important)</li>
                            <li><code>payment_intent.succeeded</code></li>
                            <li><code>charge.succeeded</code></li>
                        </ul>
                        
                        <form method="post">
                            <input type="hidden" name="action" value="check_and_fix">
                            <button type="submit" class="btn btn-primary">Check Configuration</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Simulate Webhook Event</h5>
                    </div>
                    <div class="card-body">
                        <p>This will simulate a successful checkout session for the most recent pending order.</p>
                        <form method="post">
                            <input type="hidden" name="action" value="simulate_webhook">
                            <button type="submit" class="btn btn-warning">Simulate Webhook</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Fix Missing Order Addresses</h5>
                    </div>
                    <div class="card-body">
                        <p>This will link existing orders with NULL address_id to the most recent address for each user.</p>
                        <form method="post">
                            <input type="hidden" name="action" value="fix_addresses">
                            <button type="submit" class="btn btn-info">Fix Missing Addresses</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Test Inventory Updates</h5>
                    </div>
                    <div class="card-body">
                        <p>Manually test updating inventory for a specific product.</p>
                        <form method="post">
                            <input type="hidden" name="action" value="test_inventory">
                            <div class="mb-3">
                                <label for="product_id" class="form-label">Select Product</label>
                                <select name="product_id" id="product_id" class="form-select" required>
                                    <option value="">-- Select a product --</option>
                                    <?php
                                    // Get all products from the database
                                    $result = $conn->query("SELECT id, name, stock FROM items ORDER BY name");
                                    while ($row = $result->fetch_assoc()) {
                                        $stockStatus = '';
                                        if ($row['stock'] <= 0) {
                                            $stockStatus = ' (Out of Stock)';
                                        } elseif ($row['stock'] <= 5) {
                                            $stockStatus = ' (Low Stock: ' . $row['stock'] . ')';
                                        } else {
                                            $stockStatus = ' (Stock: ' . $row['stock'] . ')';
                                        }
                                        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . $stockStatus . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity to Reduce</label>
                                <input type="number" name="quantity" id="quantity" min="1" value="1" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success">Update Inventory</button>
                        </form>
                        
                        <hr>
                        <h6>Quick Actions</h6>
                        <form method="post" class="mt-3">
                            <input type="hidden" name="action" value="force_update_scarf">
                            <button type="submit" class="btn btn-danger">Force Update Knitted Scarf Stock</button>
                            <small class="d-block text-muted mt-1">This will reduce the Knitted Scarf (ID 2) stock by 1 regardless of any constraints</small>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Database Tables</h5>
                    </div>
                    <div class="card-body">
                        <h6>Orders Table</h6>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Payment ID</th>
                                    <th>Payment Method</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No orders found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?= $order['id'] ?></td>
                                            <td><?= $order['user_id'] ?></td>
                                            <td><?= $order['status'] ?></td>
                                            <td>$<?= number_format($order['total_price'], 2) ?></td>
                                            <td><?= $order['payment_id'] ?: 'NULL' ?></td>
                                            <td><?= $order['payment_method'] ?: 'NULL' ?></td>
                                            <td><?= $order['created_at'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 