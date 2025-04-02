<?php
session_start();
include __DIR__ . '/../config.php';

// echo "Debug: User ID = $user_id <br>";

$isLoggedIn = isset($_SESSION["user_id"]);
if (!$isLoggedIn) {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = $isLoggedIn ? $_SESSION["user_id"] : null;

$shipping = 7.99;
$taxRate = 0.075;

$cart_items = [];

if ($isLoggedIn) {
    $stmt = $conn->prepare("
        SELECT ci.item_id, i.name, i.price, i.img, ci.quantity, i.stock
        FROM CART_ITEMS ci
        JOIN ITEMS i ON ci.item_id = i.id
        JOIN CART c ON ci.cart_id = c.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cart_items[$row["item_id"]] = $row;
    }
    $stmt->close();
} else {
    if (isset($_SESSION["cart"])) {
        foreach ($_SESSION["cart"] as $id => $cart_data) {
            $stmt = $conn->prepare("SELECT id, name, price, img, stock FROM ITEMS WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();
            if ($item) {
                $item["quantity"] = $cart_data["quantity"];
                $cart_items[$id] = $item;
            }
        }
    }
}

$subtotal = array_reduce($cart_items, function ($carry, $item) {
    return $carry + ($item['price'] * $item['quantity']);
}, 0);
$tax = round($subtotal * $taxRate, 2);
$total = $subtotal + $shipping + $tax;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basket - Handmade Goods</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');
    </style>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/basket.css">
    <link rel="stylesheet" href="../assets/css/product_card.css">
</head>

<body>
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>
    <div class="container mt-5">
        <h1>Basket</h1>
        <h4><span class="text-muted"><?= count($cart_items) ?> items</span></h4>

        <div class="row mt-5">
            <div class="col-md-<?= empty($cart_items) ? '12' : '8' ?>">
                <?php if (!empty($cart_items)): ?>
                    <?php foreach ($cart_items as $id => $item): ?>
                        <div class="cart-item d-flex align-items-center p-3 mb-3">
                            <img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
                                class="cart-img">
                            <div class="cart-details ms-3">
                                <h5><?= htmlspecialchars($item['name']) ?></h5>
                                <p class="text-muted">$<?= number_format($item['price'], 2) ?></p>
                                <?php if ($item['stock'] < 5): ?>
                                    <p class="stock-warning">Only <?= $item['stock'] ?> left in stock!</p>
                                <?php endif; ?>
                                <div class="d-flex align-items-center">
                                    <form action="../basket/update_basket.php" method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="product_id" value="<?= $id ?>">
                                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" 
                                               min="1" max="<?= $item['stock'] ?>"
                                               class="form-control quantity-input me-2"
                                               onchange="this.form.submit()">
                                    </form>
                                    <a href="../basket/remove_from_basket.php?id=<?= $id ?>"
                                       class="btn btn-sm btn-outline-danger ms-2">Remove</a>
                                </div>
                            </div>
                            <h5 class="text-end">$<?= number_format($item['price'] * $item['quantity'], 2) ?></h5>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Your basket is empty.</p>
                    <div class="text-center">
                        <a href="../pages/products.php" class="cta d-inline-flex align-items-center hover-raise">
                            <span class="material-symbols-outlined">shoppingmode</span>Browse Products
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($cart_items)): ?>
                <div class="col-md-4">
                    <div class="summary-box p-4">
                        <h4>Order Summary</h4>
                        <div class="summary-item">Subtotal: <span
                                class="float-end">$<?= number_format($subtotal, 2) ?></span></div>
                        <div class="summary-item">Shipping: <span
                                class="float-end">$<?= number_format($shipping, 2) ?></span></div>
                        <div class="summary-item">Tax (7.5%): <span class="float-end">$<?= number_format($tax, 2) ?></span>
                        </div>
                        <hr>
                        <h5 class="summary-total">Total: <span class="float-end">$<?= number_format($total, 2) ?></span>
                        </h5>
                        <?php if (!empty($cart_items)): ?>
                            <form id="placeOrderForm">
                                <button type="submit" class="cta w-100 mt-3">Place Order</button>
                            </form>

                            <script>
                                $(document).ready(function () {
                                    $("#placeOrderForm").submit(function (e) {
                                        e.preventDefault();

                                        $.ajax({
                                            url: "../basket/place_order.php",
                                            type: "POST",
                                            dataType: "json",
                                            success: function (response) {
                                                console.log(response);

                                                if (response.success) {
                                                    alert("Order placed successfully! Order ID: " + response.order_id);
                                                    window.location.href = "../pages/order_confirmation.php?order_id=" + response.order_id;
                                                } else {
                                                    alert("Error: " + response.error);
                                                }
                                            },
                                            error: function (xhr, status, error) {
                                                console.error("AJAX error: ", error);
                                                console.error("Server response: ", xhr.responseText);
                                                alert("Error processing order. Please try again.");
                                            }
                                        });
                                    });
                                });

                            </script>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>

</html>