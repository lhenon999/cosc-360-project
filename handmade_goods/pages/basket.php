<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../stripe/stripe.php';

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
    
    // Clear any success message when restoring a cart from a canceled checkout
    if (isset($_SESSION['success']) && $_SESSION['success'] == "Order placed successfully!") {
        unset($_SESSION['success']);
    }
}

// Get existing user addresses
$addresses = [];
if ($isLoggedIn) {
    $stmt = $conn->prepare("
        SELECT id, street_address, city, state, postal_code, country 
        FROM ADDRESSES 
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
$total = $subtotal + $tax;
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
    echo ensure_stripe_js(); 
    ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/basket.css">
    <link rel="stylesheet" href="../assets/css/product_card.css">
    <link rel="stylesheet" href="../assets/css/form.css">
    <link rel="stylesheet" href="../assets/css/address-form.css">
</head>

<body>
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>
    <div class="container mt-5">
        <h1>Basket</h1>
        <h4><span class="text-muted"><?= count($cart_items) ?> items</span></h4>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php 
        // Check if user account is frozen
        $is_frozen = false;
        if ($isLoggedIn) {
            $stmt = $conn->prepare("SELECT is_frozen FROM USERS WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($is_frozen);
            $stmt->fetch();
            $stmt->close();
            
            if ($is_frozen): ?>
                <div class="alert alert-warning">
                    <strong>Account Notice:</strong> Your account is currently frozen. You can still purchase products from other users, but you cannot sell your own products until your account is unfrozen by an administrator.
                </div>
            <?php endif;
        }
        ?>

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
                                        <div class="invalid-feedback">Address Line 1 is required</div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div>
                                        <label for="address-line2">Address Line 2</label>
                                        <input type="text" id="address-line2" name="line2" placeholder="Apartment, suite, unit, building, floor, etc.">
                                        <div class="invalid-feedback">Address Line 2 is invalid</div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div>
                                        <label for="address-city">City *</label>
                                        <input type="text" id="address-city" name="city" required>
                                        <div class="invalid-feedback">City is required</div>
                                    </div>
                                    
                                    <div>
                                        <label for="address-state">State/Province *</label>
                                        <input type="text" id="address-state" name="state" required>
                                        <div class="invalid-feedback">State/Province is required</div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div>
                                        <label for="address-postal">ZIP/Postal Code *</label>
                                        <input type="text" id="address-postal" name="postal_code" required>
                                        <div class="invalid-feedback">ZIP/Postal Code is required</div>
                                    </div>
                                    
                                    <div>
                                        <label for="address-country">Country *</label>
                                        <select id="address-country" name="country" required>
                                            <option value="">Select a country</option>
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
                                        <div class="invalid-feedback">Country is required</div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="button" id="save-address" class="m-btn">Save Address</button>
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
                            <form id="placeOrderForm">
                                <button type="submit" class="cta w-100 mt-3">Place Order</button>
                            </form>

                            <script>
                                $(document).ready(function () {
                                    $("#placeOrderForm").submit(function (e) {
                                        e.preventDefault();
                                        const button = $(this).find('button');
                                        button.prop('disabled', true);
                                        button.html('<div class="spinner-border spinner-border-sm" role="status"></div> Processing...');
                                        const buttonTimeout = setTimeout(function() {
                                            console.log("Request timeout - resetting button");
                                            button.prop('disabled', false);
                                            button.html('<span class="material-symbols-outlined">shopping_cart_checkout</span> Place Order');
                                            alert("The request is taking too long. Please try again or check your internet connection.");
                                        }, 20000);
                                        
                                        const selectedOption = $('input[name="address_option"]:checked').val();
                                        
                                        if (!selectedOption) {
                                            alert('Please select a shipping address');
                                            button.prop('disabled', false);
                                            button.html('<span class="material-symbols-outlined">shopping_cart_checkout</span> Place Order');
                                            return;
                                        }

                                        if (selectedOption === 'new' && !$('#new-address-form').hasClass('hidden')) {
                                            if ($('.address-option').length <= 1) {
                                                alert('Please add a shipping address to continue');
                                            } else {
                                                alert('Please save your shipping address or select an existing one');
                                            }
                                            button.prop('disabled', false);
                                            button.html('<span class="material-symbols-outlined">shopping_cart_checkout</span> Place Order');
                                            return;
                                        }

                                        let addressId = null;
                                        if (selectedOption.startsWith('existing_')) {
                                            addressId = selectedOption.split('_')[1];
                                        }

                                        console.log("Selected address option:", selectedOption);
                                        console.log("Extracted address ID:", addressId);

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
                                                console.log("Place order response:", response);
                                                
                                                if (response.success) {
                                                    console.log("Creating Stripe checkout session for order ID:", response.order_id);
                                                    const checkoutData = { order_id: response.order_id };
                                                    console.log("Sending data to process_stripe_checkout.php:", JSON.stringify(checkoutData));
                                                    
                                                    $.ajax({
                                                        url: "../payments/process_stripe_checkout.php",
                                                        type: "POST",
                                                        contentType: "application/json",
                                                        headers: {
                                                            'X-Requested-With': 'XMLHttpRequest'
                                                        },
                                                        data: JSON.stringify(checkoutData),
                                                        dataType: 'json',
                                                        timeout: 30000,
                                                        success: function (checkoutResponse) {
                                                            clearTimeout(buttonTimeout);
                                                            console.log("Checkout response:", checkoutResponse);
                                                            
                                                            if (checkoutResponse.success && checkoutResponse.checkout_url) {
                                                                console.log("Redirecting to:", checkoutResponse.checkout_url);
                                                                window.location.href = checkoutResponse.checkout_url;
                                                            } else {
                                                                console.error("Invalid checkout response:", checkoutResponse);
                                                                alert("Checkout error: " + (checkoutResponse.error || "Unknown error"));
                                                                button.prop('disabled', false);
                                                                button.html('<span class="material-symbols-outlined">shopping_cart_checkout</span> Place Order');
                                                            }
                                                        },
                                                        error: function (xhr, status, error) {
                                                            clearTimeout(buttonTimeout);
                                                            let errorMessage = "Error creating checkout session. Please try again.";
                                                            
                                                            try {
                                                                const responseText = xhr.responseText.trim();
                                                                if (responseText.startsWith('{')) {
                                                                    const errorData = JSON.parse(responseText);
                                                                    if (errorData && errorData.error) {
                                                                        errorMessage = "Error: " + errorData.error;
                                                                    }
                                                                } else if (responseText.includes("permission denied")) {
                                                                    errorMessage = "Server error: The server doesn't have permission to write log files. Please contact the administrator.";
                                                                } else {
                                                                    errorMessage = "Server error: There was a problem processing your request. Please try again.";
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
                                                        },
                                                        fail: function(jqXHR, textStatus, errorThrown) {
                                                            clearTimeout(buttonTimeout);
                                                            console.error("AJAX request failed:", textStatus, errorThrown);
                                                            alert("Network error: Could not connect to the payment processor. Please try again.");
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
                                                clearTimeout(buttonTimeout);
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
                                    const selectedOption = $('input[name="address_option"]:checked').val();
                                    if (selectedOption === 'new') {
                                        $('#new-address-form').removeClass('hidden');
                                    } else {
                                        $('#new-address-form').addClass('hidden');
                                    }

                                    $('input[name="address_option"]').change(function() {
                                        if (this.value === 'new') {
                                            $('#new-address-form').removeClass('hidden');
                                        } else {
                                            $('#new-address-form').addClass('hidden');
                                        }

                                        $('.address-option').removeClass('selected');
                                        $(this).closest('.address-option').addClass('selected');
                                    });

                                    function validateAddressField(selector, minLength, pattern, errorMessages) {
                                        const value = $(selector).val().trim();
                                        const fieldName = $(selector).siblings('label').text().replace(' *', '');
                                        
                                        if ($(selector).prop('required') && !value) {
                                            $(selector).addClass('is-invalid');
                                            $('.invalid-feedback', $(selector).parent()).text(errorMessages.required || `${fieldName} is required`);
                                            return false;
                                        } else if (value && value.length < minLength) {
                                            $(selector).addClass('is-invalid');
                                            $('.invalid-feedback', $(selector).parent()).text(errorMessages.tooShort || `${fieldName} must be at least ${minLength} characters`);
                                            return false;
                                        } else if (value && !pattern.test(value)) {
                                            $(selector).addClass('is-invalid');
                                            $('.invalid-feedback', $(selector).parent()).text(errorMessages.invalidFormat || `${fieldName} contains invalid characters`);
                                            return false;
                                        }
                                        
                                        $(selector).removeClass('is-invalid');
                                        return true;
                                    }

                                    $('#address-line1').on('input change blur', function() {
                                        validateAddressField('#address-line1', 3, /^[A-Za-z0-9\s\-\.,#\/]+$/, {
                                            required: 'Address Line 1 is required',
                                            tooShort: 'Address Line 1 must be at least 3 characters',
                                            invalidFormat: 'Address Line 1 contains invalid characters (only letters, numbers, spaces, and -.,#/ are allowed)'
                                        });
                                    });

                                    $('#address-line2').on('input change blur', function() {
                                        if ($(this).val().trim()) {
                                            validateAddressField('#address-line2', 3, /^[A-Za-z0-9\s\-\.,#\/]+$/, {
                                                tooShort: 'Address Line 2 must be at least 3 characters',
                                                invalidFormat: 'Address Line 2 contains invalid characters (only letters, numbers, spaces, and -.,#/ are allowed)'
                                            });
                                        } else {
                                            $(this).removeClass('is-invalid');
                                        }
                                    });

                                    $('#address-city').on('input change blur', function() {
                                        validateAddressField('#address-city', 2, /^[A-Za-z\s\-']+$/, {
                                            required: 'City is required',
                                            tooShort: 'City must be at least 2 characters',
                                            invalidFormat: 'City contains invalid characters (only letters, spaces, hyphens, and apostrophes are allowed)'
                                        });
                                    });

                                    $('#address-state').on('input change blur', function() {
                                        validateAddressField('#address-state', 2, /^[A-Za-z\s\-']+$/, {
                                            required: 'State/Province is required',
                                            tooShort: 'State/Province must be at least 2 characters',
                                            invalidFormat: 'State/Province contains invalid characters (only letters, spaces, hyphens, and apostrophes are allowed)'
                                        });
                                    });
                                    
                                    $('#address-postal').on('input change blur', function() {
                                        validatePostalCode();
                                    });

                                    $('#address-country').on('change blur', function() {
                                        const value = $(this).val();
                                        if (!value) {
                                            $(this).addClass('is-invalid');
                                            $('.invalid-feedback', $(this).parent()).text('Country is required');
                                        } else {
                                            $(this).removeClass('is-invalid');
                                            validatePostalCode();
                                        }
                                    });

                                    function validatePostalCode() {
                                        const postalCode = $('#address-postal').val().trim();
                                        const country = $('#address-country').val();
                                        
                                        if (!postalCode) {
                                            $('#address-postal').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-postal').parent()).text('ZIP/Postal Code is required');
                                            return false;
                                        }
                                        
                                        let postalCodeValid = true;
                                        
                                        if (country === 'US') {
                                            // US ZIP: 5 digits or 5+4 format (12345 or 12345-6789)
                                            if (!postalCode.match(/^\d{5}(-\d{4})?$/)) {
                                                postalCodeValid = false;
                                                $('.invalid-feedback', $('#address-postal').parent()).text('US ZIP code should be 5 digits or 12345-6789 format');
                                            }
                                        } else if (country === 'CA') {
                                            // Canadian: Letter Number Letter Number Letter Number (A1B2C3 or A1B 2C3)
                                            if (!postalCode.match(/^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/)) {
                                                postalCodeValid = false;
                                                $('.invalid-feedback', $('#address-postal').parent()).text('Canadian postal code should be in A1B 2C3 format');
                                            }
                                        } else if (country === 'GB') {
                                            // UK: Various formats
                                            if (!postalCode.match(/^[A-Z]{1,2}[0-9][A-Z0-9]? ?[0-9][A-Z]{2}$/i)) {
                                                postalCodeValid = false;
                                                $('.invalid-feedback', $('#address-postal').parent()).text('UK postal code format is invalid');
                                            }
                                        }
                                        
                                        if (!postalCodeValid) {
                                            $('#address-postal').addClass('is-invalid');
                                        } else {
                                            $('#address-postal').removeClass('is-invalid');
                                        }
                                        
                                        return postalCodeValid;
                                    }
                                    
                                    $('#save-address').off('click').on('click', function() {
                                        const form = document.getElementById('address-form');
                                        console.log('Save button clicked (after delete), form element:', form);
                                        
                                        if (!form) {
                                            console.error('Form element not found!');
                                            alert('Error: Form element not found. Please try again.');
                                            return;
                                        }
                                        
                                        if ($(this).data('processing')) {
                                            console.log('Already processing a request, please wait...');
                                            return;
                                        }
                                        
                                        let isValid = true;
                                    
                                        const line1 = $('#address-line1').val().trim();
                                        const line2 = $('#address-line2').val().trim(); 
                                        const city = $('#address-city').val().trim();
                                        const state = $('#address-state').val().trim();
                                        const postalCode = $('#address-postal').val().trim();
                                        const country = $('#address-country').val();
                                        
                                        console.log('Form values (after delete):', { line1, line2, city, state, postalCode, country });
                                        $('#address-line1, #address-line2, #address-city, #address-state, #address-postal, #address-country').removeClass('is-invalid');
                                        
                                        // Validate each field
                                        if (!line1) {
                                            $('#address-line1').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-line1').parent()).text('Address Line 1 is required');
                                            isValid = false;
                                        } else if (line1.length < 3) {
                                            $('#address-line1').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-line1').parent()).text('Address Line 1 must be at least 3 characters');
                                            isValid = false;
                                        } else if (!/^[A-Za-z0-9\s\-\.,#\/]+$/.test(line1)) {
                                            $('#address-line1').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-line1').parent()).text('Address Line 1 contains invalid characters');
                                            isValid = false;
                                        }

                                        if (line2 && line2.length > 0) {
                                            if (line2.length < 3) {
                                                $('#address-line2').addClass('is-invalid');
                                                $('.invalid-feedback', $('#address-line2').parent()).text('Address Line 2 must be at least 3 characters');
                                                isValid = false;
                                            } else if (!/^[A-Za-z0-9\s\-\.,#\/]+$/.test(line2)) {
                                                $('#address-line2').addClass('is-invalid');
                                                $('.invalid-feedback', $('#address-line2').parent()).text('Address Line 2 contains invalid characters');
                                                isValid = false;
                                            }
                                        }

                                        if (!city) {
                                            $('#address-city').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-city').parent()).text('City is required');
                                            isValid = false;
                                        } else if (city.length < 2) {
                                            $('#address-city').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-city').parent()).text('City must be at least 2 characters');
                                            isValid = false;
                                        } else if (!/^[A-Za-z\s\-']+$/.test(city)) {
                                            $('#address-city').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-city').parent()).text('City contains invalid characters');
                                            isValid = false;
                                        }

                                        if (!state) {
                                            $('#address-state').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-state').parent()).text('State/Province is required');
                                            isValid = false;
                                        } else if (state.length < 2) {
                                            $('#address-state').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-state').parent()).text('State/Province must be at least 2 characters');
                                            isValid = false;
                                        } else if (!/^[A-Za-z\s\-']+$/.test(state)) {
                                            $('#address-state').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-state').parent()).text('State/Province contains invalid characters');
                                            isValid = false;
                                        }
                                        
                                        if (!postalCode) {
                                            $('#address-postal').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-postal').parent()).text('ZIP/Postal Code is required');
                                            isValid = false;
                                        } else {
                                            let postalCodeValid = true;
                                            if (country === 'US') {
                                                // US ZIP: 5 digits or 5+4 format (12345 or 12345-6789)
                                                if (!postalCode.match(/^\d{5}(-\d{4})?$/)) {
                                                    postalCodeValid = false;
                                                    $('.invalid-feedback', $('#address-postal').parent()).text('US ZIP code should be 5 digits or 12345-6789 format');
                                                }
                                            } else if (country === 'CA') {
                                                // Canadian: Letter Number Letter Number Letter Number (A1B2C3 or A1B 2C3)
                                                if (!postalCode.match(/^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/)) {
                                                    postalCodeValid = false;
                                                    $('.invalid-feedback', $('#address-postal').parent()).text('Canadian postal code should be in A1B 2C3 format');
                                                }
                                            } else if (country === 'GB') {
                                                // UK: Various formats
                                                if (!postalCode.match(/^[A-Z]{1,2}[0-9][A-Z0-9]? ?[0-9][A-Z]{2}$/i)) {
                                                    postalCodeValid = false;
                                                    $('.invalid-feedback', $('#address-postal').parent()).text('UK postal code format is invalid');
                                                }
                                            }
                                            
                                            if (!postalCodeValid) {
                                                $('#address-postal').addClass('is-invalid');
                                                isValid = false;
                                            }
                                        }

                                        if (!country) {
                                            $('#address-country').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-country').parent()).text('Country is required');
                                            isValid = false;
                                        }
                                        
                                        console.log('Form validation result (after delete):', isValid);
                                        
                                        if (!isValid) {
                                            return;
                                        }
                                        
                                        const button = $(this);
                                        button.prop('disabled', true);
                                        button.html('Saving...');
                                        
                                        $(this).data('processing', true);
                                        
                                        // Create formData from the form
                                        const formData = new FormData(form);
                                        const addressData = {};
                                        for (let [key, value] of formData.entries()) {
                                            addressData[key] = value;
                                        }
                                        
                                        console.log('Form data collected:', addressData);

                                        // Send data to the server
                                        $.ajax({
                                            url: '../basket/save_address.php',
                                            type: 'POST',
                                            contentType: 'application/json',
                                            data: JSON.stringify(addressData),
                                            success: function(response) {
                                                console.log('Save address response:', response);
                                                $(button).data('processing', false);
                                                
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
                                                    
                                                    // Display success message
                                                    alert('Address saved successfully!');
                                                    
                                                } else {
                                                    alert('Error saving address: ' + (response.error || 'Unknown error'));
                                                }
                                                
                                                button.prop('disabled', false);
                                                button.html('Save Address');
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('Error saving address:', status, error);
                                                console.error('Response:', xhr.responseText);
                                                $(button).data('processing', false);
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
<?php include __DIR__ . '/../assets/html/footer.php'; ?>
</body>

<div class="b-modal modal-backdrop" id="deleteConfirmationModal">
    <div class="b-modal modal-dialog">
        <div class="b-modal modal-content">
            <div class="b-modal modal-header">
                <h5 class="b-modal modal-title">Delete Address</h5>
            </div>
            <div class="b-modal modal-body">
                <p>Are you sure you want to delete this address?</p>
            </div>
            <div class="b-modal modal-footer">
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

        $('#cancelDeleteBtn').click(function() {
            $('#deleteConfirmationModal').fadeOut(200);
            addressToDelete = null;
            addressOptionElement = null;
        });
        
        $(document).on('click', '.delete-address-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            addressToDelete = $(this).data('address-id');
            addressOptionElement = $(this).closest('.address-option');
            
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
                                
                                // Reset the form
                                const form = document.getElementById('address-form');
                                form.reset();
                                
                                // Reinitialize the save button handler
                                $('#save-address').off('click').on('click', function() {
                                    const form = document.getElementById('address-form');
                                    console.log('Save button clicked (after delete), form element:', form);
                                    
                                    if (!form) {
                                        console.error('Form element not found!');
                                        alert('Error: Form element not found. Please try again.');
                                        return;
                                    }
                                    
                                    // Add a check for any ongoing AJAX requests
                                    if ($(this).data('processing')) {
                                        console.log('Already processing a request, please wait...');
                                        return;
                                    }
                                    
                                    let isValid = true;
                                    
                                    // Basic form validation
                                    const line1 = $('#address-line1').val().trim();
                                    const line2 = $('#address-line2').val().trim(); 
                                    const city = $('#address-city').val().trim();
                                    const state = $('#address-state').val().trim();
                                    const postalCode = $('#address-postal').val().trim();
                                    const country = $('#address-country').val();
                                    
                                    console.log('Form values (after delete):', { line1, line2, city, state, postalCode, country });
                                    
                                    // Clear previous error styles
                                    $('#address-line1, #address-line2, #address-city, #address-state, #address-postal, #address-country').removeClass('is-invalid');
                                    
                                    // Validate each field
                                    if (!line1) {
                                        $('#address-line1').addClass('is-invalid');
                                        $('.invalid-feedback', $('#address-line1').parent()).text('Address Line 1 is required');
                                        isValid = false;
                                    } else if (line1.length < 3) {
                                        $('#address-line1').addClass('is-invalid');
                                        $('.invalid-feedback', $('#address-line1').parent()).text('Address Line 1 must be at least 3 characters');
                                        isValid = false;
                                    } else if (!/^[A-Za-z0-9\s\-\.,#\/]+$/.test(line1)) {
                                        $('#address-line1').addClass('is-invalid');
                                        $('.invalid-feedback', $('#address-line1').parent()).text('Address Line 1 contains invalid characters');
                                        isValid = false;
                                    }

                                    // Line 2 is optional, but validate if provided
                                    if (line2 && line2.length > 0) {
                                        if (line2.length < 3) {
                                            $('#address-line2').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-line2').parent()).text('Address Line 2 must be at least 3 characters');
                                            isValid = false;
                                        } else if (!/^[A-Za-z0-9\s\-\.,#\/]+$/.test(line2)) {
                                            $('#address-line2').addClass('is-invalid');
                                            $('.invalid-feedback', $('#address-line2').parent()).text('Address Line 2 contains invalid characters');
                                            isValid = false;
                                        }
                                    }

                                    if (!city) {
                                        $('#address-city').addClass('is-invalid');
                                        $('.invalid-feedback', $('#address-city').parent()).text('City is required');
                                        isValid = false;
                                    } else if (city.length < 2) {
                                        $('#address-city').addClass('is-invalid');
                                        $('.invalid-feedback', $('#address-city').parent()).text('City must be at least 2 characters');
                                        isValid = false;
                                    } else if (!/^[A-Za-z\s\-']+$/.test(city)) {
                                        $('#address-city').addClass('is-invalid');
                                        $('.invalid-feedback', $('#address-city').parent()).text('City contains invalid characters');
                                        isValid = false;
                                    }

                                    if (!state) {
                                        $('#address-state').addClass('is-invalid');
                                        $('.invalid-feedback', $('#address-state').parent()).text('State/Province is required');
                                        isValid = false;
                                    } else if (state.length < 2) {
                                        $('#address-state').addClass('is-invalid');
                                        $('.invalid-feedback', $('#address-state').parent()).text('State/Province must be at least 2 characters');
                                        isValid = false;
                                    } else if (!/^[A-Za-z\s\-']+$/.test(state)) {
                                        $('#address-state').addClass('is-invalid');
                                        $('.invalid-feedback', $('#address-state').parent()).text('State/Province contains invalid characters');
                                        isValid = false;
                                    }
                                    
                                    // Postal code validation with country-specific formats
                                    if (!postalCode) {
                                        $('#address-postal').addClass('is-invalid');
                                        $('.invalid-feedback', $('#address-postal').parent()).text('ZIP/Postal Code is required');
                                        isValid = false;
                                    } else {
                                        let postalCodeValid = true;
                                        // Country-specific postal code validation
                                        if (country === 'US') {
                                            // US ZIP: 5 digits or 5+4 format (12345 or 12345-6789)
                                            if (!postalCode.match(/^\d{5}(-\d{4})?$/)) {
                                                postalCodeValid = false;
                                                $('.invalid-feedback', $('#address-postal').parent()).text('US ZIP code should be 5 digits or 12345-6789 format');
                                            }
                                        } else if (country === 'CA') {
                                            // Canadian: Letter Number Letter Number Letter Number (A1B2C3 or A1B 2C3)
                                            if (!postalCode.match(/^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/)) {
                                                postalCodeValid = false;
                                                $('.invalid-feedback', $('#address-postal').parent()).text('Canadian postal code should be in A1B 2C3 format');
                                            }
                                        } else if (country === 'GB') {
                                            // UK: Various formats
                                            if (!postalCode.match(/^[A-Z]{1,2}[0-9][A-Z0-9]? ?[0-9][A-Z]{2}$/i)) {
                                                postalCodeValid = false;
                                                $('.invalid-feedback', $('#address-postal').parent()).text('UK postal code format is invalid');
                                            }
                                        }
                                        
                                        if (!postalCodeValid) {
                                            $('#address-postal').addClass('is-invalid');
                                            isValid = false;
                                        }
                                    }

                                    if (!country) {
                                        $('#address-country').addClass('is-invalid');
                                        $('.invalid-feedback', $('#address-country').parent()).text('Country is required');
                                        isValid = false;
                                    }
                                    
                                    console.log('Form validation result (after delete):', isValid);
                                    
                                    // If the form is invalid, stop here
                                    if (!isValid) {
                                        return;
                                    }
                                    
                                    const button = $(this);
                                    button.prop('disabled', true);
                                    button.html('Saving...');
                                    
                                    // Mark as processing
                                    $(this).data('processing', true);
                                    
                                    // Create formData from the form
                                    const formData = new FormData(form);
                                    const addressData = {};
                                    for (let [key, value] of formData.entries()) {
                                        addressData[key] = value;
                                    }
                                    
                                    console.log('Form data collected:', addressData);

                                    // Send data to the server
                                    $.ajax({
                                        url: '../basket/save_address.php',
                                        type: 'POST',
                                        contentType: 'application/json',
                                        data: JSON.stringify(addressData),
                                        success: function(response) {
                                            console.log('Save address response:', response);
                                            $(button).data('processing', false);
                                            
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
                                                
                                                // Display success message
                                                alert('Address saved successfully!');
                                                
                                            } else {
                                                alert('Error saving address: ' + (response.error || 'Unknown error'));
                                            }
                                            
                                            button.prop('disabled', false);
                                            button.html('Save Address');
                                        },
                                        error: function(xhr, status, error) {
                                            console.error('Error saving address:', status, error);
                                            console.error('Response:', xhr.responseText);
                                            $(button).data('processing', false);
                                            alert('Server error while saving address. Please try again.');
                                            button.prop('disabled', false);
                                            button.html('Save Address');
                                        }
                                    });
                                });
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