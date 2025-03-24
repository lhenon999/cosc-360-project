<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../config.php';

// Add a debug message
echo "Debug: Script started<br>";

// Test if Stripe config exists
if (file_exists(__DIR__ . '/../config/stripe.php')) {
    echo "Debug: Stripe config file exists<br>";
    require_once __DIR__ . '/../config/stripe.php';
    echo "Debug: Stripe config loaded<br>";
} else {
    echo "Debug: Stripe config file NOT found at " . __DIR__ . '/../config/stripe.php' . "<br>";
    die("Stripe configuration file is missing");
}

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$order_id = isset($_POST["order_id"]) ? intval($_POST["order_id"]) : null;
$user_id = intval($_SESSION["user_id"]);

if (!$order_id) {
    $_SESSION["error"] = "Invalid order ID.";
    header("Location: order_confirmation.php");
    exit();
}

$stmt = $conn->prepare("SELECT total_price, status FROM ORDERS WHERE id = ? AND user_id = ?");
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
    <title>Complete Your Payment</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');
    </style>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <?php echo ensure_stripe_js(); ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/order_confirmation.css">
</head>

<body>
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Complete Your Payment</h1>
        <p class="text-center">Order ID: <strong>#<?= $order_id ?></strong></p>
        <p class="text-center">Total Amount: <strong>$<?= number_format($total_price, 2) ?></strong></p>

        <div class="d-flex justify-content-center mt-4">
            <button id="checkout-button" class="cta hover-raise">
                <span class="material-symbols-outlined">credit_card</span>
                Proceed to Checkout
            </button>
        </div>
        
        <div id="loading" class="text-center mt-4 d-none">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Preparing your secure checkout...</p>
        </div>
        
        <div id="error-message" class="alert alert-danger mt-4 d-none"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Use the global stripe instance that's initialized with fallback
            const stripe = window.stripeInstance;
            
            const checkoutButton = document.getElementById('checkout-button');
            const loadingElement = document.getElementById('loading');
            const errorElement = document.getElementById('error-message');
            
            checkoutButton.addEventListener('click', function() {
                // Disable button and show loading
                checkoutButton.disabled = true;
                loadingElement.classList.remove('d-none');
                errorElement.classList.add('d-none');
                
                // Call backend to create Stripe checkout session
                fetch('../payments/process_stripe_checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        order_id: <?= $order_id ?>
                    })
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(session) {
                    if (session.success && session.url) {
                        // Redirect to Stripe Checkout
                        window.location.href = session.url;
                    } else {
                        throw new Error(session.error || 'Unknown error occurred');
                    }
                })
                .catch(function(error) {
                    // Show error and re-enable button
                    errorElement.textContent = 'Payment error: ' + error.message;
                    errorElement.classList.remove('d-none');
                    loadingElement.classList.add('d-none');
                    checkoutButton.disabled = false;
                    console.error('Error:', error);
                });
            });
        });
    </script>

    <?php include __DIR__ . '/../assets/html/footer.php'; ?>
</body>

</html>
