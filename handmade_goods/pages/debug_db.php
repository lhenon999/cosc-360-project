<?php
session_start();
require_once '../config.php';

// Ensure only admins or developers can access this page
if (!isset($_SESSION["user_id"]) || ($_SESSION["user_id"] != 1 && !isset($_SESSION["is_admin"]))) {
    echo "Unauthorized access";
    exit;
}

// Check database tables
function checkTable($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Get recent records from a table
function getRecentRecords($conn, $tableName, $limit = 10) {
    $result = $conn->query("SELECT * FROM $tableName ORDER BY id DESC LIMIT $limit");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get recent orders with related information
function getDetailedOrders($conn, $limit = 10) {
    $query = "
        SELECT o.id, o.user_id, o.address_id, o.total_price, o.status, o.created_at, 
               o.payment_id, o.payment_method,
               COUNT(oi.id) as item_count,
               a.street_address, a.city, a.state, a.postal_code, a.country
        FROM orders o
        LEFT JOIN addresses a ON o.address_id = a.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.id DESC
        LIMIT $limit
    ";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Debug</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .data-table th { background-color: #f2f2f2; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Database Debug Information</h1>
        
        <h2>Table Check</h2>
        <ul>
            <li>Orders table exists: <?= checkTable($conn, 'orders') ? 'Yes' : 'No' ?></li>
            <li>Order_items table exists: <?= checkTable($conn, 'order_items') ? 'Yes' : 'No' ?></li>
            <li>Addresses table exists: <?= checkTable($conn, 'addresses') ? 'Yes' : 'No' ?></li>
            <li>Cart table exists: <?= checkTable($conn, 'cart') ? 'Yes' : 'No' ?></li>
            <li>Cart_items table exists: <?= checkTable($conn, 'cart_items') ? 'Yes' : 'No' ?></li>
        </ul>
        
        <h2>Order Information</h2>
        <h3>Recent Orders</h3>
        <?php $orders = getDetailedOrders($conn); ?>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>User ID</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Items</th>
                    <th>Payment ID</th>
                    <th>Payment Method</th>
                    <th>Address</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($orders)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">No orders found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($orders as $order): ?>
                        <tr>
                            <td><?= $order['id'] ?></td>
                            <td><?= $order['user_id'] ?></td>
                            <td><?= $order['status'] ?></td>
                            <td>$<?= number_format($order['total_price'], 2) ?></td>
                            <td><?= $order['item_count'] ?></td>
                            <td><?= $order['payment_id'] ?: 'NULL' ?></td>
                            <td><?= $order['payment_method'] ?: 'NULL' ?></td>
                            <td>
                                <?php if($order['street_address']): ?>
                                    <?= htmlspecialchars($order['street_address']) ?><br>
                                    <?= htmlspecialchars($order['city']) ?>, 
                                    <?= htmlspecialchars($order['state']) ?> 
                                    <?= htmlspecialchars($order['postal_code']) ?>
                                <?php else: ?>
                                    No address
                                <?php endif; ?>
                            </td>
                            <td><?= $order['created_at'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <h3>Check Stripe Webhook Endpoint</h3>
        <p>Make sure your Stripe webhook is properly configured with this URL:</p>
        <code>
            <?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/../payments/stripe_webhook.php') ?>
        </code>
        
        <h3>Webhook Events</h3>
        <p>The following events should be enabled:</p>
        <ul>
            <li>checkout.session.completed</li>
            <li>payment_intent.succeeded</li>
            <li>charge.succeeded</li>
        </ul>
        
        <h2>Database Columns Check</h2>
        <?php
        $columns = $conn->query("SHOW COLUMNS FROM orders");
        $orderColumns = $columns->fetch_all(MYSQLI_ASSOC);
        ?>
        
        <h3>Orders Table Columns</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Null</th>
                    <th>Key</th>
                    <th>Default</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orderColumns as $column): ?>
                    <tr>
                        <td><?= $column['Field'] ?></td>
                        <td><?= $column['Type'] ?></td>
                        <td><?= $column['Null'] ?></td>
                        <td><?= $column['Key'] ?></td>
                        <td><?= $column['Default'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 