<?php
session_start();
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input format']);
    exit;
}

// Extract address ID
$user_id = $_SESSION['user_id'];
$address_id = isset($input['address_id']) ? intval($input['address_id']) : 0;

// Validate address ID
if (!$address_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid address ID']);
    exit;
}

try {
    // First check if the address belongs to the user
    $stmt = $conn->prepare("SELECT id FROM ADDRESSES WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $address_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Address not found or doesn't belong to you");
    }
    $stmt->close();
    
    // Begin transaction
    $conn->begin_transaction();
    
    // Check if this address is linked to any orders
    $stmt = $conn->prepare("SELECT id FROM ORDERS WHERE address_id = ?");
    $stmt->bind_param("i", $address_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_used_in_orders = ($result->num_rows > 0);
    $stmt->close();
    
    // If address is used in orders, update orders to remove the link first
    if ($is_used_in_orders) {
        $update_stmt = $conn->prepare("UPDATE ORDERS SET address_id = NULL WHERE address_id = ?");
        $update_stmt->bind_param("i", $address_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update orders: " . $update_stmt->error);
        }
        
        $update_stmt->close();
    }
    
    // Now try deleting the address
    $stmt = $conn->prepare("DELETE FROM ADDRESSES WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $address_id, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete address: " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Address could not be deleted");
    }
    
    $stmt->close();
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Address removed successfully'
    ]);

} catch (Exception $e) {
    // Roll back transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}