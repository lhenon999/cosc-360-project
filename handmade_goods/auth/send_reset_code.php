<?php
session_start();
require '../config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: forgot_password.php?error=invalidemail");
        exit();
    }

    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        header("Location: forgot_password.php?error=not_found");
        exit();
    }

    $stmt->close();

    $full_token = bin2hex(random_bytes(32));
    $hashed_token = password_hash($full_token, PASSWORD_DEFAULT);
    $short_code = substr(hash('sha256', $full_token), 0, 8);

    $expires = date("Y-m-d H:i:s", strtotime("+30 minutes"));
    $stmt = $conn->prepare("INSERT INTO password_resets (email, token, short_code, expires) 
                        VALUES (?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE token=?, short_code=?, expires=?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sssssss", $email, $hashed_token, $short_code, $expires, $hashed_token, $short_code, $expires);
    $stmt->execute();
    $stmt->close();

    $api_key = "api-DFEA151D81194B3EB9B6CF30891D53A5";
    $email_data = [
        "api_key" => $api_key,
        "sender" => "handmadegoods@mail2world.com",
        "to" => [$email],
        "subject" => "Password Reset Verification Code",
        "html_body" => "<h1>Your verification code: $short_code</h1><p>It expires in 30 minutes.</p>",
        "text_body" => "Your verification code: $short_code (Expires in 30 minutes)",
    ];

    $ch = curl_init("https://api.smtp2go.com/v3/email/send");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'accept: application/json']);

    $response = curl_exec($ch);
    curl_close($ch);

    header("Location: verify_reset_token.php?email=" . urlencode($email));
    exit();
}
?>