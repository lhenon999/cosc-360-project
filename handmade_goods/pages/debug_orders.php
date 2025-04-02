<?php
session_start();
require_once '../config.php';

// Set debug mode
$_SESSION['debug'] = true;

// Validate user is admin or match specific user ID
if (!isset($_SESSION["user_id"]) || ($_SESSION["user_id"] != 1 && !isset($_SESSION["is_admin"]))) {
    echo "Unauthorized access";
    exit;
}

// Check if order ID is specified
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;

// Get orders with associated addresses
$query = "
    SELECT o.id, o.user_id, o.address_id, o.total_price, o.status, o.created_at,
           a.id as address_table_id, a.street_address, a.city, a.state, a.postal_code, a.country
    FROM orders o
    LEFT JOIN addresses a ON o.address_id = a.id
    WHERE 1=1 ";

if ($order_id) {
    $query .= " AND o.id = " . $order_id;
}

$query .= " ORDER BY o.id DESC LIMIT 10";

$result = $conn->query($query);
$orders = $result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Orders</title>
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
        <h1>Order Debug Information</h1>
        
        <h2>Database Information</h2>
        
        <h3>Recent Orders</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>User ID</th>
                    <th>Address ID</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Address Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $order): ?>
                <tr>
                    <td><?= $order['id'] ?></td>
                    <td><?= $order['user_id'] ?></td>
                    <td><?= $order['address_id'] ?: 'NULL' ?></td>
                    <td>$<?= number_format($order['total_price'], 2) ?></td>
                    <td><?= $order['status'] ?></td>
                    <td><?= $order['created_at'] ?></td>
                    <td>
                        <?php if($order['address_table_id']): ?>
                            <?= htmlspecialchars($order['street_address']) ?><br>
                            <?= htmlspecialchars($order['city']) ?>, 
                            <?= htmlspecialchars($order['state']) ?> 
                            <?= htmlspecialchars($order['postal_code']) ?><br>
                            <?= htmlspecialchars($order['country']) ?>
                        <?php else: ?>
                            No address linked
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3>Debug Query</h3>
        <pre><?= htmlspecialchars($query) ?></pre>
        
        <h3>Raw Order Data</h3>
        <pre><?php print_r($orders); ?></pre>
        
        <h2>Recent Addresses</h2>
        <?php
        $addresses = $conn->query("SELECT * FROM addresses ORDER BY id DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
        ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Address ID</th>
                    <th>User ID</th>
                    <th>Street</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Postal Code</th>
                    <th>Country</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($addresses as $address): ?>
                <tr>
                    <td><?= $address['id'] ?></td>
                    <td><?= $address['user_id'] ?></td>
                    <td><?= htmlspecialchars($address['street_address']) ?></td>
                    <td><?= htmlspecialchars($address['city']) ?></td>
                    <td><?= htmlspecialchars($address['state']) ?></td>
                    <td><?= htmlspecialchars($address['postal_code']) ?></td>
                    <td><?= htmlspecialchars($address['country']) ?></td>
                    <td><?= $address['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 