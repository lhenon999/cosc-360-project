<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

// Debugging - log the request data
error_log("save_address.php called with user_id: " . ($_SESSION['user_id'] ?? 'not set'));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get JSON input
$raw_input = file_get_contents('php://input');
error_log("Raw input: " . $raw_input);

$input = json_decode($raw_input, true);
if (!$input) {
    error_log("Invalid JSON input: " . json_last_error_msg());
    echo json_encode(['success' => false, 'error' => 'Invalid input format: ' . json_last_error_msg()]);
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

error_log("Extracted address data: " . json_encode([
    'line1' => $line1,
    'line2' => $line2,
    'city' => $city,
    'state' => $state,
    'postal_code' => $postal_code,
    'country' => $country
]));

// Simple validation for required fields
if (empty($line1) || empty($city) || empty($state) || empty($postal_code) || empty($country)) {
    $missing = [];
    if (empty($line1)) $missing[] = 'line1';
    if (empty($city)) $missing[] = 'city';
    if (empty($state)) $missing[] = 'state';
    if (empty($postal_code)) $missing[] = 'postal_code';
    if (empty($country)) $missing[] = 'country';
    
    error_log("Missing required fields: " . implode(', ', $missing));
    echo json_encode(['success' => false, 'error' => 'Missing required address fields: ' . implode(', ', $missing)]);
    exit;
}

// Validate text length and allowed characters
$validation_errors = [];

// Address Line 1
if (strlen($line1) < 3) {
    $validation_errors[] = 'Address Line 1 must be at least 3 characters';
}
if (!preg_match('/^[A-Za-z0-9\s\-\.,#\/]+$/', $line1)) {
    $validation_errors[] = 'Address Line 1 contains invalid characters';
}

// Address Line 2 (optional)
if (!empty($line2)) {
    if (strlen($line2) < 3) {
        $validation_errors[] = 'Address Line 2 must be at least 3 characters';
    }
    if (!preg_match('/^[A-Za-z0-9\s\-\.,#\/]+$/', $line2)) {
        $validation_errors[] = 'Address Line 2 contains invalid characters';
    }
}

// City
if (strlen($city) < 2) {
    $validation_errors[] = 'City must be at least 2 characters';
}
if (!preg_match('/^[A-Za-z\s\-\']+$/', $city)) {
    $validation_errors[] = 'City contains invalid characters';
}

// State/Province
if (strlen($state) < 2) {
    $validation_errors[] = 'State/Province must be at least 2 characters';
}
if (!preg_match('/^[A-Za-z\s\-\']+$/', $state)) {
    $validation_errors[] = 'State/Province contains invalid characters';
}

// If there are validation errors, return them
if (!empty($validation_errors)) {
    error_log("Validation errors: " . implode(', ', $validation_errors));
    echo json_encode(['success' => false, 'error' => implode(', ', $validation_errors)]);
    exit;
}

// Validate postal code format based on country
$postal_code_valid = true;
$postal_code_error = '';

switch ($country) {
    case 'US':
        // US ZIP: 5 digits or 5+4 format (12345 or 12345-6789)
        if (!preg_match('/^\d{5}(-\d{4})?$/', $postal_code)) {
            $postal_code_valid = false;
            $postal_code_error = 'US ZIP code should be 5 digits or 12345-6789 format';
        }
        break;
    case 'CA':
        // Canadian: Letter Number Letter Number Letter Number (A1B2C3 or A1B 2C3)
        if (!preg_match('/^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/', $postal_code)) {
            $postal_code_valid = false;
            $postal_code_error = 'Canadian postal code should be in A1B 2C3 format';
        }
        break;
    case 'GB':
        // UK: Various formats
        if (!preg_match('/^[A-Z]{1,2}[0-9][A-Z0-9]? ?[0-9][A-Z]{2}$/i', $postal_code)) {
            $postal_code_valid = false;
            $postal_code_error = 'UK postal code format is invalid';
        }
        break;
}

if (!$postal_code_valid) {
    error_log("Invalid postal code format: $postal_code for country: $country - $postal_code_error");
    echo json_encode(['success' => false, 'error' => $postal_code_error]);
    exit;
}

try {
    // Build street address
    $street_address = $line1;
    if (!empty($line2)) {
        $street_address .= ', ' . $line2;
    }

    error_log("Formatted street address: " . $street_address);

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
        $error = "Failed to insert address: " . $stmt->error;
        error_log($error);
        throw new Exception($error);
    }
    
    $address_id = $stmt->insert_id;
    $stmt->close();
    
    error_log("Address inserted successfully with ID: " . $address_id);
    
    // Return success with the new address ID and formatted address
    $response = [
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
    ];
    
    error_log("Sending response: " . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Exception caught: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}