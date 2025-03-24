<?php
session_start();
require_once '../config.php';
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

// Extract address data
$user_id = $_SESSION['user_id'];
$line1 = $input['line1'] ?? '';
$line2 = $input['line2'] ?? '';
$city = $input['city'] ?? '';
$state = $input['state'] ?? '';
$postal_code = $input['postal_code'] ?? '';
$country = $input['country'] ?? '';

// Validate required fields
if (empty($line1) || empty($city) || empty($state) || empty($postal_code) || empty($country)) {
    echo json_encode(['success' => false, 'error' => 'Missing required address fields']);
    exit;
}

try {
    // Build street address
    $street_address = $line1;
    if (!empty($line2)) {
        $street_address .= ', ' . $line2;
    }

    // Insert address into database
    $stmt = $conn->prepare("
        INSERT INTO addresses (user_id, street_address, city, state, postal_code, country)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("isssss", 
        $user_id,
        $street_address,
        $city,
        $state,
        $postal_code,
        $country
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert address: " . $stmt->error);
    }
    
    $address_id = $stmt->insert_id;
    $stmt->close();
    
    // Return success with the new address ID and formatted address
    echo json_encode([
        'success' => true, 
        'address_id' => $address_id,
        'address' => [
            'id' => $address_id,
            'street_address' => $street_address,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postal_code,
            'country' => $country,
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}