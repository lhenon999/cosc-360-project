<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$basket_items = [];
$stmt = $conn->prepare("
    SELECT ci.item_id, i.name, i.price, i.img, ci.quantity, i.stock
    FROM cart_items ci
    JOIN items i ON ci.item_id = i.id
    JOIN cart c ON ci.cart_id = c.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $basket_items[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Using Bootstrap for styling -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
</head>
<body>
    <div class="container mt-5">
        <?php if (empty($basket_items)): ?>
            <p>Your basket is empty.</p>
        <?php else: ?>
            <?php 
            $total = 0;
            foreach ($basket_items as $item):
                $total += $item['price'] * $item['quantity'];
            ?>
                <div class="basket-item">
                    <img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <div class="basket-details">
                        <h5><?= htmlspecialchars($item['name']) ?></h5>
                        <p>Price: $<?= number_format($item['price'], 2) ?></p>
                        <p>Quantity: <?= $item['quantity'] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="basket-summary">
                <h4 class="total-label">Total: $<?= number_format($total, 2) ?></h4>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
