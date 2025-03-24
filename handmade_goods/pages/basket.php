<?php
session_start();
require_once '../config.php';
require_once '../config/stripe.php';

// echo "Debug: User ID = $user_id <br>";

$isLoggedIn = isset($_SESSION["user_id"]);
if (!$isLoggedIn) {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = $isLoggedIn ? $_SESSION["user_id"] : null;

// Check if we need to restore a cart from a canceled checkout
if (isset($_SESSION['pending_order_cart']) && isset($_SESSION['pending_order_id'])) {
    // Get the user's cart ID
    $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $cart = $result->fetch_assoc();
        $cart_id = $cart['id'];
    } else {
        // Create a new cart if one doesn't exist
        $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_id = $conn->insert_id;
    }
    $stmt->close();
    
    // Restore saved cart items
    foreach ($_SESSION['pending_order_cart'] as $item_id => $item_data) {
        $quantity = $item_data['quantity'];
        
        // Check if item already exists in cart
        $stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE cart_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $cart_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing item
            $existing = $result->fetch_assoc();
            $new_quantity = $existing['quantity'] + $quantity;
            
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND item_id = ?");
            $stmt->bind_param("iii", $new_quantity, $cart_id, $item_id);
            $stmt->execute();
        } else {
            // Add new item to cart
            $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, item_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $cart_id, $item_id, $quantity);
            $stmt->execute();
        }
    }
    
    // Update order status to canceled
    $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_SESSION['pending_order_id'], $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Clear the pending order data
    unset($_SESSION['pending_order_cart']);
    unset($_SESSION['pending_order_id']);
}

// Get existing user addresses
$addresses = [];
if ($isLoggedIn) {
    $stmt = $conn->prepare("
        SELECT id, street_address, city, state, postal_code, country 
        FROM addresses 
        WHERE user_id = ? 
        ORDER BY id DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Make Stripe publishable key available to JavaScript
$stripe_publishable_key = 'pk_test_51R1OROBSlWUNcExMybmLKuUOMFFhHJ7ZYoaNOHG6XvbnoqxRyQxkLJcf2hgNuIvgd3d03CPS5DvOStmYzOoP80c100G4jIbM8r';

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
$total = $subtotal + $tax;  // Removed shipping from here since it's handled by Stripe
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
    <?php 
    // Replace direct Stripe JS inclusion with our ensure_stripe_js function
    echo ensure_stripe_js(); 
    ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/basket.css">
    <link rel="stylesheet" href="../assets/css/product_card.css">
    <link rel="stylesheet" href="../assets/css/form.css">
    <style>
        .address-container {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }
        
        .address-option {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            position: relative;
        }
        
        .address-option input {
            margin-right: 15px;
            margin-top: 5px;
        }
        
        .address-option.selected {
            border-color: #4CAF50;
            background-color: #f0f9f0;
        }
        
        .address-actions {
            position: absolute;
            right: 10px;
            top: 10px;
        }
        
        .delete-address-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .delete-address-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .delete-address-btn .material-symbols-outlined {
            font-size: 18px;
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
        
        .hidden {
            display: none;
        }
        
        /* Confirmation modal styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: none;
        }
        
        .modal-dialog {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            z-index: 1001;
            width: 90%;
            max-width: 400px;
        }
        
        .modal-header {
            margin-bottom: 15px;
        }
        
        .modal-footer {
            margin-top: 20px;
            text-align: right;
        }
        
        .modal-footer button {
            margin-left: 10px;
        }
    </style>
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
                                <?php if ($item['stock'] <= 0): ?>
                                    <p class="out-of-stock-warning">Out of stock!</p>
                                <?php elseif ($item['stock'] < 5): ?>
                                    <p class="stock-warning">Only <?= $item['stock'] ?> left in stock!</p>
                                <?php endif; ?>
                                <div class="d-flex align-items-center">
                                    <form action="../basket/update_basket.php" method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="product_id" value="<?= $id ?>">
                                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" 
                                               min="1" max="<?= $item['stock'] ?>"
                                               class="form-control quantity-input me-2"
                                               <?= ($item['stock'] <= 0) ? 'disabled' : '' ?>
                                               onchange="this.form.submit()">
                                    </form>
                                    <a href="../basket/remove_from_basket.php?id=<?= $id ?>"
                                       class="btn btn-sm btn-outline-danger ms-2">Remove</a>
                                </div>
                            </div>
                            <h5 class="text-end">$<?= number_format($item['price'] * $item['quantity'], 2) ?></h5>
                        </div>
                    <?php endforeach; ?>

                    <div class="address-container mt-4">
                        <h3>Shipping Address</h3>
                        
                        <?php if (count($addresses) > 0): ?>
                            <form id="address-selection-form">
                                <?php foreach ($addresses as $index => $address): ?>
                                    <label class="address-option <?php echo $index === 0 ? 'selected' : ''; ?>">
                                        <input type="radio" name="address_option" value="existing_<?php echo $address['id']; ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                        <div>
                                            <strong><?php echo htmlspecialchars($address['street_address']); ?></strong><br>
                                            <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                            <?php echo htmlspecialchars($address['country']); ?>
                                        </div>
                                        <div class="address-actions">
                                            <button type="button" class="delete-address-btn" data-address-id="<?php echo $address['id']; ?>">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                                
                                <label class="address-option <?php echo count($addresses) === 0 ? 'selected' : ''; ?>">
                                    <input type="radio" name="address_option" value="new" <?php echo count($addresses) === 0 ? 'checked' : ''; ?>>
                                    <div>
                                        <strong>Use a new shipping address</strong>
                                    </div>
                                </label>
                            </form>
                        <?php else: ?>
                            <p>Please enter your shipping address to continue.</p>
                            <form id="address-selection-form">
                                <label class="address-option selected">
                                    <input type="radio" name="address_option" value="new" checked>
                                    <div>
                                        <strong>Use a new shipping address</strong>
                                    </div>
                                </label>
                            </form>
                        <?php endif; ?>
                        
                        <div class="address-form <?php echo (count($addresses) > 0) ? 'hidden' : ''; ?>" id="new-address-form">
                            <h4>New Shipping Address</h4>
                            <form id="address-form">
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

                                <div class="mt-3">
                                    <button type="button" id="save-address" class="btn btn-primary">Save Address</button>
                                </div>
                            </form>
                        </div>
                    </div>
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
                            <form id="placeOrderForm" class="mb-3">
                                <button type="submit" class="cta hover-raise w-100">
                                    <span class="material-symbols-outlined">shopping_cart_checkout</span>
                                    Place Order
                                </button>
                            </form>

                            <script>
                                // Use the global stripe instance that's properly initialized with fallback
                                $(document).ready(function () {
                                    $("#placeOrderForm").submit(function (e) {
                                        e.preventDefault();
                                        const button = $(this).find('button');
                                        button.prop('disabled', true);
                                        button.html('<div class="spinner-border spinner-border-sm" role="status"></div> Processing...');

                                        // Check if address is selected
                                        const selectedOption = $('input[name="address_option"]:checked').val();
                                        
                                        if (!selectedOption) {
                                            alert('Please select a shipping address');
                                            button.prop('disabled', false);
                                            button.html('<span class="material-symbols-outlined">shopping_cart_checkout</span> Place Order');
                                            return;
                                        }

                                        // If "new" address is selected but the form is visible
                                        if (selectedOption === 'new' && !$('#new-address-form').hasClass('hidden')) {
                                            // Check if there are any saved addresses
                                            if ($('.address-option').length <= 1) {
                                                alert('Please add a shipping address to continue');
                                            } else {
                                                alert('Please save your shipping address or select an existing one');
                                            }
                                            button.prop('disabled', false);
                                            button.html('<span class="material-symbols-outlined">shopping_cart_checkout</span> Place Order');
                                            return;
                                        }

                                        // Extract address ID from the selected option if it exists
                                        let addressId = null;
                                        if (selectedOption.startsWith('existing_')) {
                                            addressId = selectedOption.split('_')[1];
                                        }

                                        console.log("Selected address option:", selectedOption);
                                        console.log("Extracted address ID:", addressId);

                                        // First create the order
                                        $.ajax({
                                            url: "../basket/place_order.php",
                                            type: "POST",
                                            dataType: "json",
                                            data: { 
                                                address_id: addressId,
                                                address_option: selectedOption 
                                            },
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest'
                                            },
                                            success: function (response) {
                                                if (response.success) {
                                                    // Then redirect to Stripe Checkout
                                                    $.ajax({
                                                        url: "../payments/process_stripe_checkout.php",
                                                        type: "POST",
                                                        contentType: "application/json",
                                                        headers: {
                                                            'X-Requested-With': 'XMLHttpRequest'
                                                        },
                                                        data: JSON.stringify({ order_id: response.order_id }),
                                                        success: function (checkoutResponse) {
                                                            if (checkoutResponse.success && checkoutResponse.checkout_url) {
                                                                window.location.href = checkoutResponse.checkout_url;
                                                            } else {
                                                                alert("Checkout error: " + (checkoutResponse.error || "Unknown error"));
                                                                button.prop('disabled', false);
                                                                button.html('<span class="material-symbols-outlined">shopping_cart_checkout</span> Place Order');
                                                            }
                                                        },
                                                        error: function (xhr, status, error) {
                                                            let errorMessage = "Error creating checkout session. Please try again.";
                                                            
                                                            try {
                                                                // Try to parse the error response as JSON
                                                                const errorData = JSON.parse(xhr.responseText);
                                                                if (errorData && errorData.error) {
                                                                    errorMessage = "Error: " + errorData.error;
                                                                }
                                                            } catch (e) {
                                                                console.error("Error parsing error response:", e);
                                                                console.error("Raw response:", xhr.responseText);
                                                            }
                                                            
                                                            console.error("Checkout error details:", {
                                                                status: xhr.status,
                                                                statusText: xhr.statusText,
                                                                responseText: xhr.responseText
                                                            });
                                                            
                                                            alert(errorMessage);
                                                            button.prop('disabled', false);
                                                            button.html('<span class="material-symbols-outlined">shopping_cart_checkout</span> Place Order');
                                                        }
                                                    });
                                                } else {
                                                    alert("Order error: " + (response.error || "Unknown error"));
                                                    button.prop('disabled', false);
                                                    button.html('<span class="material-symbols-outlined">shopping_cart_checkout</span> Place Order');
                                                }
                                            },
                                            error: function (xhr, status, error) {
                                                console.error("Order error details:", {
                                                    status: xhr.status,
                                                    statusText: xhr.statusText,
                                                    responseText: xhr.responseText
                                                });
                                                alert("Error processing order. Please try again.");
                                                button.prop('disabled', false);
                                                button.html('<span class="material-symbols-outlined">shopping_cart_checkout</span> Place Order');
                                            }
                                        });
                                    });

                                    // Handle quantity changes
                                    $(".quantity-input").change(function() {
                                        const itemId = $(this).data("item-id");
                                        const quantity = $(this).val();
                                        
                                        $.ajax({
                                            url: "../basket/update_basket.php",
                                            type: "POST",
                                            data: {
                                                item_id: itemId,
                                                quantity: quantity
                                            },
                                            success: function() {
                                                location.reload();
                                            }
                                        });
                                    });

                                    // Handle item removal
                                    $(".remove-item").click(function() {
                                        const itemId = $(this).data("item-id");
                                        
                                        $.ajax({
                                            url: "../basket/remove_from_basket.php",
                                            type: "POST",
                                            data: {
                                                item_id: itemId
                                            },
                                            success: function() {
                                                location.reload();
                                            }
                                        });
                                    });
                                });
                            </script>
                            <script>
                                $(document).ready(function () {
                                    // Initialize form visibility based on selected option on page load
                                    const selectedOption = $('input[name="address_option"]:checked').val();
                                    if (selectedOption === 'new') {
                                        $('#new-address-form').removeClass('hidden');
                                    } else {
                                        $('#new-address-form').addClass('hidden');
                                    }
                                    
                                    // Toggle address form visibility based on selection
                                    $('input[name="address_option"]').change(function() {
                                        if (this.value === 'new') {
                                            $('#new-address-form').removeClass('hidden');
                                        } else {
                                            $('#new-address-form').addClass('hidden');
                                        }
                                        
                                        // Add selected class to parent
                                        $('.address-option').removeClass('selected');
                                        $(this).closest('.address-option').addClass('selected');
                                    });
                                    
                                    // Save address form submission
                                    $('#save-address').click(function() {
                                        const form = document.getElementById('address-form');
                                        
                                        if (!form.checkValidity()) {
                                            form.reportValidity();
                                            return;
                                        }
                                        
                                        const button = $(this);
                                        button.prop('disabled', true);
                                        button.html('Saving...');
                                        
                                        const formData = new FormData(form);
                                        const addressData = {};
                                        for (let [key, value] of formData.entries()) {
                                            addressData[key] = value;
                                        }
                                        
                                        // Save address to database
                                        $.ajax({
                                            url: '../basket/save_address.php',
                                            type: 'POST',
                                            contentType: 'application/json',
                                            data: JSON.stringify(addressData),
                                            success: function(response) {
                                                if (response.success) {
                                                    const address = response.address;
                                                    
                                                    // Create new radio option with the new address
                                                    const newOption = `
                                                        <label class="address-option selected">
                                                            <input type="radio" name="address_option" value="existing_${address.id}" checked>
                                                            <div>
                                                                <strong>${address.street_address}</strong><br>
                                                                ${address.city}, ${address.state} ${address.postal_code}<br>
                                                                ${address.country}
                                                            </div>
                                                            <div class="address-actions">
                                                                <button type="button" class="delete-address-btn" data-address-id="${address.id}">
                                                                    <span class="material-symbols-outlined">delete</span>
                                                                </button>
                                                            </div>
                                                        </label>
                                                    `;
                                                    
                                                    // Insert at the beginning of the form (before the "Use a new address" option)
                                                    $('#address-selection-form label:last-child').before(newOption);
                                                    
                                                    // Explicitly check the new radio button
                                                    $(`input[value="existing_${address.id}"]`).prop('checked', true);
                                                    
                                                    // Hide the form and clear it
                                                    $('#new-address-form').addClass('hidden');
                                                    form.reset();
                                                    
                                                    // Remove the 'selected' class from all other options
                                                    $('.address-option').removeClass('selected');
                                                    $(`input[value="existing_${address.id}"]`).closest('.address-option').addClass('selected');
                                                    
                                                    // Update the radio button event handlers
                                                    $('input[name="address_option"]').off('change').on('change', function() {
                                                        if (this.value === 'new') {
                                                            $('#new-address-form').removeClass('hidden');
                                                        } else {
                                                            $('#new-address-form').addClass('hidden');
                                                        }
                                                        
                                                        $('.address-option').removeClass('selected');
                                                        $(this).closest('.address-option').addClass('selected');
                                                    });
                                                    
                                                    // Display success message
                                                    alert('Address saved successfully!');
                                                    
                                                } else {
                                                    alert('Error saving address: ' + (response.error || 'Unknown error'));
                                                }
                                                
                                                button.prop('disabled', false);
                                                button.html('Save Address');
                                            },
                                            error: function() {
                                                alert('Server error while saving address. Please try again.');
                                                button.prop('disabled', false);
                                                button.html('Save Address');
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

<!-- Confirmation Modal -->
<div class="modal-backdrop" id="deleteConfirmationModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Address</h5>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this address?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelDeleteBtn">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let addressToDelete = null;
        let addressOptionElement = null;

        // Handle cancel button in modal
        $('#cancelDeleteBtn').click(function() {
            $('#deleteConfirmationModal').fadeOut(200);
            addressToDelete = null;
            addressOptionElement = null;
        });
        
        // Use event delegation for dynamically added delete buttons
        $(document).on('click', '.delete-address-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            addressToDelete = $(this).data('address-id');
            addressOptionElement = $(this).closest('.address-option');
            
            // Show confirmation modal
            $('#deleteConfirmationModal').fadeIn(200);
        });
        
        // Handle confirm delete button in modal
        $('#confirmDeleteBtn').click(function() {
            if (!addressToDelete) {
                $('#deleteConfirmationModal').fadeOut(200);
                return;
            }
            
            const button = $(this);
            button.prop('disabled', true);
            button.text('Deleting...');
            
            // Send delete request to server
            $.ajax({
                url: '../basket/delete_address.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    address_id: addressToDelete
                }),
                success: function(response) {
                    $('#deleteConfirmationModal').fadeOut(200);
                    button.prop('disabled', false);
                    button.text('Delete');
                    
                    if (response.success) {
                        // Check if the deleted address was selected
                        const wasSelected = addressOptionElement.hasClass('selected');
                        
                        // Remove the address from DOM
                        addressOptionElement.slideUp(300, function() {
                            // After animation complete, remove element
                            $(this).remove();
                            
                            // If there are no more addresses except the "Use a new address" option
                            if ($('.address-option').length <= 1) {
                                // Select the "new address" option and show the form
                                $('input[value="new"]').prop('checked', true);
                                $('#new-address-form').removeClass('hidden');
                                $('.address-option:last').addClass('selected');
                            } else if (wasSelected) {
                                // If the deleted address was selected, select the first one
                                const firstRadio = $('input[name="address_option"]:first');
                                firstRadio.prop('checked', true);
                                firstRadio.closest('.address-option').addClass('selected');
                            }
                        });
                    } else {
                        alert('Error deleting address: ' + (response.error || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error details:", xhr.responseText);
                    $('#deleteConfirmationModal').fadeOut(200);
                    alert('Server error while deleting address. Please try again.');
                    button.prop('disabled', false);
                    button.text('Delete');
                },
                complete: function() {
                    addressToDelete = null;
                    addressOptionElement = null;
                }
            });
        });
        
        // Close modal when clicking outside
        $('#deleteConfirmationModal').click(function(e) {
            if (e.target === this) {
                $(this).fadeOut(200);
                addressToDelete = null;
                addressOptionElement = null;
            }
        });
    });
</script>

</html>