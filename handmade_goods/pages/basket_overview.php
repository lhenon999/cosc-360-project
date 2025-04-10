<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$basket_user_id = $_SESSION["user_id"];

$basket_items = [];
$stmt = $conn->prepare("
    SELECT ci.item_id, i.name, i.price, i.img, ci.quantity, i.stock
    FROM cart_items ci
    JOIN items i ON ci.item_id = i.id
    JOIN cart c ON ci.cart_id = c.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $basket_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $basket_items[] = $row;
}
$stmt->close();
?>

<div class="mt-3 container">
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
                    <p>
                        Price: $<?= number_format($item['price'], 2) ?>
                        &nbsp;|&nbsp;
                        Quantity: <?= $item['quantity'] ?>
                    </p>

                </div>
            </div>
        <?php endforeach; ?>
        <h4 class="total-label">Total: $<?= number_format($total, 2) ?></h4>
    <?php endif; ?>
</div>