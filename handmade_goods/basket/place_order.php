<?php
session_start();
include __DIR__ . '/../config.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$logDir = dirname(dirname(__FILE__)) . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$logFile = $logDir . '/order_process.log';

function logOrderProcess($message, $data = null) {
    global $logFile;
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= " - " . (is_array($data) || is_object($data) ? json_encode($data) : $data);
    }
    
    $logDir = dirname(dirname(__FILE__)) . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    try {
        file_put_contents($logFile, $log . "\n", FILE_APPEND);
    } catch (Exception $e) {
    }
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT is_frozen FROM USERS WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($is_frozen);
$stmt->fetch();
$stmt->close();

$input = null;
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
}

$address_id = null;
if ($isAjax) {
    $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : null;
    if (!$address_id && isset($input['address_id'])) {
        $address_id = intval($input['address_id']);
    }
}

if (isset($_POST['address_option']) && strpos($_POST['address_option'], 'existing_') === 0) {
    $address_id = intval(str_replace('existing_', '', $_POST['address_option']));
    logOrderProcess("Got address_id from address_option", ['address_id' => $address_id]);
}

logOrderProcess("Starting order process", [
    'user_id' => $user_id, 
    'is_ajax' => $isAjax,
    'address_id' => $address_id,
    'address_option' => $_POST['address_option'] ?? 'not set'
]);

$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM CART_ITEMS ci 
    JOIN CART c ON ci.cart_id = c.id 
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_count = $result->fetch_assoc()['count'];
$stmt->close();

if ($cart_count === 0) {
    logOrderProcess("Cart is empty");
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Your basket is empty']);
        exit;
    } else {
        $_SESSION['error'] = "Your basket is empty.";
        header('Location: ../pages/basket.php');
        exit;
    }
}

if ($address_id) {
    $stmt = $conn->prepare("SELECT id FROM ADDRESSES WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        logOrderProcess("Database error preparing address validation", $conn->error);
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Database error']);
            exit;
        } else {
            $_SESSION['error'] = "Database error.";
            header('Location: ../pages/basket.php');
            exit;
        }
    }
    
    $stmt->bind_param("ii", $address_id, $user_id);
    if (!$stmt->execute()) {
        logOrderProcess("Database error executing address validation", $stmt->error);
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Database error validating address']);
            exit;
        } else {
            $_SESSION['error'] = "Database error validating address.";
            header('Location: ../pages/basket.php');
            exit;
        }
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        logOrderProcess("Invalid address ID", ['address_id' => $address_id]);
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid shipping address']);
            exit;
        } else {
            $_SESSION['error'] = "Invalid shipping address.";
            header('Location: ../pages/basket.php');
            exit;
        }
    }
    
    $stmt->close();
    logOrderProcess("Address validated", ['address_id' => $address_id]);
} else {
    logOrderProcess("No address_id provided");
}

try {
    logOrderProcess("Beginning transaction");
    $conn->begin_transaction();

    if ($address_id) {
        $stmt = $conn->prepare("INSERT INTO ORDERS (user_id, address_id, status, created_at, total_price, payment_method) VALUES (?, ?, 'Pending', NOW(), 0, 'card')");
        $stmt->bind_param("ii", $_SESSION['user_id'], $address_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO ORDERS (user_id, status, created_at, total_price, payment_method) VALUES (?, 'Pending', NOW(), 0, 'card')");
        $stmt->bind_param("i", $_SESSION['user_id']);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create order: " . $stmt->error);
    }
    $orderId = $conn->insert_id;
    $stmt->close();
    
    logOrderProcess("Created order", ['order_id' => $orderId, 'address_id' => $address_id]);

    $totalPrice = 0;
    
    $stmt = $conn->prepare("
        SELECT ci.item_id, i.name, i.price, ci.quantity, i.stock 
        FROM CART_ITEMS ci
        JOIN CART c ON ci.cart_id = c.id
        JOIN ITEMS i ON ci.item_id = i.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($item = $result->fetch_assoc()) {
        $itemPrice = $item['price'];
        $quantity = $item['quantity'];
        $subtotal = $itemPrice * $quantity;
        $totalPrice += $subtotal;
        
        $stmt2 = $conn->prepare("INSERT INTO ORDER_ITEMS (order_id, item_id, item_name, quantity, price_at_purchase) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("iisid", $orderId, $item['item_id'], $item['name'], $quantity, $itemPrice);
        if (!$stmt2->execute()) {
            throw new Exception("Failed to create order item: " . $stmt2->error);
        }
        $stmt2->close();
    }
    $stmt->close();
    
    logOrderProcess("Total order price calculated", ['total_price' => $totalPrice]);
    
    $stmt = $conn->prepare("UPDATE ORDERS SET total_price = ? WHERE id = ?");
    $stmt->bind_param("di", $totalPrice, $orderId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update order with total price: " . $stmt->error);
    }
    $stmt->close();
    
    $stmt = $conn->prepare("
        SELECT ci.item_id, ci.quantity
        FROM CART_ITEMS ci
        JOIN CART c ON ci.cart_id = c.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $savedCart = [];
    while ($item = $result->fetch_assoc()) {
        $savedCart[$item['item_id']] = [
            'quantity' => $item['quantity']
        ];
    }
    $stmt->close();
    
    $_SESSION['pending_order_cart'] = $savedCart;
    $_SESSION['pending_order_id'] = $orderId;
    
    $stmt = $conn->prepare("DELETE ci FROM CART_ITEMS ci JOIN CART c ON ci.cart_id = c.id WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    logOrderProcess("Transaction committed successfully");
    
    // Clear basket
    $_SESSION['basket'] = [];
    
    logOrderProcess("Order process complete", ['order_id' => $orderId, 'is_ajax' => $isAjax]);
    
    // Return different response based on request type
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'order_id' => $orderId]);
        exit;
    } else {
        header("Location: ../pages/order_confirmation.php?order_id=$orderId");
        exit;
    }
    
} catch (Exception $e) {
    $conn->rollback();
    
    logOrderProcess("ERROR: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } else {
        $_SESSION['error'] = "Error placing order: " . $e->getMessage();
        header('Location: ../pages/basket.php');
    }
}
?>
