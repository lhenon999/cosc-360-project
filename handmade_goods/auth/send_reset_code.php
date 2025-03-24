<?php
session_start();
require __DIR__ . '/../config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Los_Angeles');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: forgot_password.php?error=invalidemail");
        exit();
    }

    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    $stmt = $conn->prepare("SELECT id FROM USERS WHERE email = ?");
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

    // rate limits 1 per hour
    $stmt = $conn->prepare("
    SELECT created_at FROM PASSWORD_RESETS WHERE email = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    error_log("Rows fetched: " . $result->num_rows);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $created_at = $row['created_at'] ?? null;
    } else {
        $created_at = null;
    }

    $stmt->close();

    if ($created_at !== null) {
        $last_request_time = strtotime($created_at);
        $one_hour_ago = strtotime("-1 hour");

        if ($last_request_time >= $one_hour_ago) {
            header("Location: forgot_password.php?error=too_many_requests");
            exit();
        }
    }

    $full_token = bin2hex(random_bytes(32));
    $hashed_token = password_hash($full_token, PASSWORD_DEFAULT);
    $short_code = substr(hash('sha256', $full_token), 0, 8);

    $expires = date("Y-m-d H:i:s", strtotime("+30 minutes"));
    $stmt = $conn->prepare("INSERT INTO PASSWORD_RESETS (email, token, short_code, expires, created_at) 
                                    VALUES (?, ?, ?, ?, NOW()) 
                                    ON DUPLICATE KEY UPDATE 
                                    token = ?, 
                                    short_code = ?, 
                                    expires = ?, 
                                    created_at = NOW()");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sssssss", $email, $hashed_token, $short_code, $expires, $hashed_token, $short_code, $expires);
    $stmt->execute();
    $stmt->close();

    error_log("PHP timezone: " . date_default_timezone_get());
    error_log("Current PHP time: " . date("Y-m-d H:i:s"));


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