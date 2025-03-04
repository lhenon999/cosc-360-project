<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

$order_id = isset($_POST["order_id"]) ? intval($_POST["order_id"]) : null;
$user_id = intval($_SESSION["user_id"]);

if (!$order_id) {
    $_SESSION["error"] = "Invalid order ID.";
    header("Location: order_confirmation.php");
    exit();
}

$stmt = $conn->prepare("SELECT total_price, status FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION["error"] = "Order not found.";
    header("Location: order_confirmation.php");
    exit();
}

$total_price = $order["total_price"];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank you for your order!</title>

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
    <link rel="stylesheet" href="../assets/css/order_confirmation.css">
    <style>
        .hidden {
            display: none;
        }

        .payment-form {
            max-width: 500px;
            margin: auto;
        }

        .paypal-button {
            background-color: #ffc439;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include '../assets/html/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Complete Your Payment</h1>
        <p class="text-center">Order ID: <strong>#<?= $order_id ?></strong></p>
        <p class="text-center">Total Amount: <strong>$<?= number_format($total_price, 2) ?></strong></p>

        <form id="payment-form" action="process_payment_handler.php" method="POST" class="payment-form">
            <input type="hidden" name="order_id" value="<?= $order_id ?>">

            <label for="payment_method"><strong>Select Payment Method:</strong></label>
            <select id="payment_method" name="payment_method" class="form-control mt-2">
                <option value="credit_card">Credit Card</option>
                <option value="paypal">PayPal</option>
            </select>

            <div id="credit-card-form" class="mt-4">
                <label for="card_number">Card Number:</label>
                <input type="text" name="card_number" id="card_number" class="form-control"
                    placeholder="1234 5678 9012 3456" required>

                <label for="expiry_date" class="mt-2">Expiration Date:</label>
                <input type="month" name="expiry_date" id="expiry_date" class="form-control" required>

                <label for="cvv" class="mt-2">CVV:</label>
                <input type="text" name="cvv" id="cvv" class="form-control" placeholder="123" required>

                <button type="submit" class="cta hover-raise w-100 mt-4">
                    <span class="material-symbols-outlined">credit_card</span> Pay with Credit Card
                </button>
            </div>

            <div id="paypal-button-container" class="hidden mt-4 text-center">
                <button type="button" id="paypal-button" class="paypal-button">
                    Pay with PayPal
                </button>
                <p class="text-muted mt-2">You will be redirected to PayPal to complete your payment.</p>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('payment_method').addEventListener('change', function () {
            let method = this.value;
            document.getElementById('credit-card-form').classList.toggle('hidden', method !== 'credit_card');
            document.getElementById('paypal-button-container').classList.toggle('hidden', method !== 'paypal');
        });

        document.getElementById('paypal-button').addEventListener('click', function () {
            window.location.href = "order_confirmation.php?order_id=<?= $order_id ?>&payment=paypal";
        });
    </script>

</body>

</html>