<?php
session_start();
include '../config.php'; // Ensure database connection
include '../assets/html/navbar.php';

// Check if cart exists in session
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Define shipping and tax rates
$shipping = 3.99;
$taxRate = 0.075; // 7.5%

// Calculate total cost
$subtotal = array_reduce($cart, function ($carry, $item) {
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
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/basket.css">
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>

    <div class="container mt-5">
        <h1>Basket <span class="text-muted"><?= count($cart) ?> items</span></h1>

        <div class="row">
            <!-- Cart Items -->
            <div class="col-md-8">
                <?php if (!empty($cart)): ?>
                    <?php foreach ($cart as $id => $item): ?>
                        <div class="cart-item d-flex align-items-center p-3 mb-3">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-img">
                            <div class="cart-details flex-grow-1">
                                <h5><?= htmlspecialchars($item['name']) ?></h5>
                                <p class="text-muted">$<?= number_format($item['price'], 2) ?></p>
                                <form action="update_cart.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?= $id ?>">
                                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" class="quantity-input">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
                                    <a href="remove_from_cart.php?id=<?= $id ?>" class="btn btn-sm btn-outline-danger">Remove</a>
                                </form>
                            </div>
                            <h5 class="text-end">$<?= number_format($item['price'] * $item['quantity'], 2) ?></h5>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Your basket is empty.</p>
                <?php endif; ?>
            </div>

            <!-- Order Summary -->
            <div class="col-md-4">
                <div class="summary-box p-4">
                    <h4>Order Summary</h4>
                    <p>Subtotal: <span class="float-end">$<?= number_format($subtotal, 2) ?></span></p>
                    <p>Shipping: <span class="float-end">$<?= number_format($shipping, 2) ?></span></p>
                    <p>Tax (7.5%): <span class="float-end">$<?= number_format($tax, 2) ?></span></p>
                    <hr>
                    <h5>Total: <span class="float-end">$<?= number_format($total, 2) ?></span></h5>
                    <a href="checkout.php" class="btn btn-success w-100 mt-3">Continue to Payment →</a>
                </div>
            </div>
        </div>
    </div>

    <?php include "../assets/html/footer.php"; ?>

</body>
</html>