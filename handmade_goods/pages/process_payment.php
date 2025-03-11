<?php
session_start();
require_once '../config.php';
require_once '../config/stripe.php';

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
$amount_in_cents = $total_price * 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment - Handmade Goods</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/order_confirmation.css">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .payment-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .order-summary {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .payment-button {
            display: block;
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }
        .payment-button:hover {
            background: #0056b3;
            transform: translateY(-1px);
            color: white;
            text-decoration: none;
        }
        #payment-message {
            color: #dc3545;
            text-align: center;
            margin-top: 1rem;
            display: none;
        }
        .secure-badge {
            text-align: center;
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .loading {
            display: none;
            text-align: center;
            margin-top: 1rem;
        }
        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-left-color: #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <?php include '../assets/html/navbar.php'; ?>

    <div class="container mt-5">
        <div class="payment-container">
            <h2 class="mb-4 text-center">Complete Your Payment</h2>
            
            <div class="order-summary">
                <h5 class="text-center mb-3">Order Summary</h5>
                <p class="mb-2 text-center">Order #<?= $order_id ?></p>
                <p class="mb-0 text-center"><strong>Total Amount: $<?= number_format($total_price, 2) ?></strong></p>
            </div>

            <button id="checkout-button" class="payment-button">
                Proceed to Payment
            </button>

            <div class="loading">
                <div class="spinner"></div>
                <p>Preparing checkout...</p>
            </div>

            <div id="payment-message"></div>

            <div class="secure-badge">
                <svg width="16" height="16" viewBox="0 0 16 16">
                    <path fill="currentColor" d="M8 0L1 3v5c0 4.19 3.05 8.1 7 9 3.95-.9 7-4.81 7-9V3L8 0zm0 7c.83 0 1.5.67 1.5 1.5V11c0 .18-.12.33-.29.42v1.08c0 .28-.22.5-.5.5h-1.42c-.28 0-.5-.22-.5-.5v-1.08c-.17-.09-.29-.24-.29-.42V8.5C6.5 7.67 7.17 7 8 7z"/>
                </svg>
                Secure payment powered by Stripe
            </div>
        </div>
    </div>

    <script>
        const checkoutButton = document.getElementById('checkout-button');
        const loadingElement = document.querySelector('.loading');
        const messageElement = document.getElementById('payment-message');

        checkoutButton.addEventListener('click', async () => {
            checkoutButton.disabled = true;
            loadingElement.style.display = 'block';
            messageElement.style.display = 'none';

            try {
                const response = await fetch("../payments/process_stripe_payment.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ 
                        order_id: <?= $order_id ?>,
                        amount: <?= $amount_in_cents ?>
                    }),
                });

                const data = await response.json();
                
                if (data.success && data.url) {
                    window.location.href = data.url;
                } else {
                    throw new Error(data.error || 'Failed to create checkout session');
                }
            } catch (error) {
                messageElement.textContent = error.message || "An error occurred. Please try again.";
                messageElement.style.display = 'block';
                checkoutButton.disabled = false;
                loadingElement.style.display = 'none';
            }
        });
    </script>
</body>
</html>