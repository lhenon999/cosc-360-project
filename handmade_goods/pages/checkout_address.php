<?php
session_start();
require_once '../config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Get order ID from query parameter
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

// Verify order belongs to user
$stmt = $conn->prepare("
    SELECT id, user_id FROM orders 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $orderId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

// Redirect if order not found or doesn't belong to user
if (!$order) {
    header('Location: ../pages/profile.php');
    exit;
}

// Get existing user addresses
$stmt = $conn->prepare("
    SELECT id, street_address, city, state, postal_code, country 
    FROM addresses 
    WHERE user_id = ? 
    ORDER BY id DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = "Shipping Address";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Handmade Goods</title>
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/basket.css">
    <link rel="stylesheet" href="../assets/css/form.css">
    <style>
        .address-container {
            display: flex;
            flex-direction: column;
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .address-option {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
        }
        
        .address-option input {
            margin-right: 15px;
            margin-top: 5px;
        }
        
        .address-option.selected {
            border-color: #4CAF50;
            background-color: #f0f9f0;
        }
        
        .address-form {
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-row > div {
            flex: 1;
        }
        
        .form-actions {
            margin-top: 20px;
            text-align: right;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <?php include '../assets/html/navbar.php'; ?>

    <div class="container">
        <h1><?php echo $pageTitle; ?></h1>
        
        <div class="address-container">
            <?php if (count($addresses) > 0): ?>
                <h2>Select a shipping address</h2>
                
                <form id="address-selection-form">
                    <?php foreach ($addresses as $index => $address): ?>
                        <label class="address-option">
                            <input type="radio" name="address_option" value="existing_<?php echo $address['id']; ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                            <div>
                                <strong><?php echo htmlspecialchars($address['street_address']); ?></strong><br>
                                <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                <?php echo htmlspecialchars($address['country']); ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                    
                    <label class="address-option">
                        <input type="radio" name="address_option" value="new" <?php echo count($addresses) === 0 ? 'checked' : ''; ?>>
                        <div>
                            <strong>Use a new shipping address</strong>
                        </div>
                    </label>
                </form>
            <?php else: ?>
                <p>Please enter your shipping address to continue.</p>
            <?php endif; ?>
            
            <div class="address-form <?php echo count($addresses) > 0 ? 'hidden' : ''; ?>" id="new-address-form">
                <h3>New Shipping Address</h3>
                <form id="checkout-form">
                    <div class="form-row">
                        <div>
                            <label for="address-line1">Address Line 1 *</label>
                            <input type="text" id="address-line1" name="line1" required placeholder="Street address, P.O. box">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div>
                            <label for="address-line2">Address Line 2</label>
                            <input type="text" id="address-line2" name="line2" placeholder="Apartment, suite, unit, building, floor, etc.">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div>
                            <label for="address-city">City *</label>
                            <input type="text" id="address-city" name="city" required>
                        </div>
                        
                        <div>
                            <label for="address-state">State/Province *</label>
                            <input type="text" id="address-state" name="state" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div>
                            <label for="address-postal">ZIP/Postal Code *</label>
                            <input type="text" id="address-postal" name="postal_code" required>
                        </div>
                        
                        <div>
                            <label for="address-country">Country *</label>
                            <select id="address-country" name="country" required>
                                <option value="US">United States</option>
                                <option value="CA">Canada</option>
                                <option value="GB">United Kingdom</option>
                                <option value="AU">Australia</option>
                                <option value="FR">France</option>
                                <option value="DE">Germany</option>
                                <option value="JP">Japan</option>
                                <option value="IT">Italy</option>
                                <option value="ES">Spain</option>
                                <option value="NZ">New Zealand</option>
                                <option value="MX">Mexico</option>
                                <option value="SG">Singapore</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn" onclick="window.location.href='basket.php'">Back to Cart</button>
                <button type="button" class="btn btn-primary" id="continue-to-payment">Continue to Payment</button>
            </div>
        </div>
    </div>

    <?php include '../assets/html/footer.php'; ?>

    <script>
        // Show/hide new address form based on selection
        document.querySelectorAll('input[name="address_option"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.address-option').forEach(option => {
                    option.classList.remove('selected');
                });
                this.closest('.address-option').classList.add('selected');
                
                if (this.value === 'new') {
                    document.getElementById('new-address-form').classList.remove('hidden');
                } else {
                    document.getElementById('new-address-form').classList.add('hidden');
                }
            });
        });

        // Pre-select the first option
        const firstAddressOption = document.querySelector('input[name="address_option"]');
        if (firstAddressOption) {
            firstAddressOption.closest('.address-option').classList.add('selected');
        }
        
        // Handle continue to payment button
        document.getElementById('continue-to-payment').addEventListener('click', function() {
            const orderId = <?php echo $orderId; ?>;
            const selectedOption = document.querySelector('input[name="address_option"]:checked').value;
            
            if (selectedOption === 'new') {
                // Validate the form
                const form = document.getElementById('checkout-form');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                
                // Collect form data
                const formData = new FormData(form);
                const addressData = {};
                for (let [key, value] of formData.entries()) {
                    addressData[key] = value;
                }
                
                // Send to payment processor
                fetch('../payments/process_stripe_checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        address: addressData
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.url) {
                        window.location.href = data.url;
                    } else {
                        alert('Error: ' + (data.error || 'Something went wrong'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error processing payment request');
                });
            } else if (selectedOption.startsWith('existing_')) {
                // Get the existing address ID
                const addressId = selectedOption.split('_')[1];
                
                // Get the existing address data
                <?php 
                echo "const addresses = " . json_encode($addresses) . ";\n"; 
                ?>
                
                const selectedAddress = addresses.find(addr => addr.id == addressId);
                if (selectedAddress) {
                    // Convert to the format expected by the API
                    const addressData = {
                        line1: selectedAddress.street_address.split(',')[0],
                        line2: selectedAddress.street_address.includes(',') ? 
                               selectedAddress.street_address.split(',').slice(1).join(',').trim() : '',
                        city: selectedAddress.city,
                        state: selectedAddress.state,
                        postal_code: selectedAddress.postal_code,
                        country: selectedAddress.country
                    };
                    
                    // Send to payment processor
                    fetch('../payments/process_stripe_checkout.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            order_id: orderId,
                            address: addressData
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.url) {
                            window.location.href = data.url;
                        } else {
                            alert('Error: ' + (data.error || 'Something went wrong'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error processing payment request');
                    });
                }
            }
        });
    </script>
</body>
</html>