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

    if ($stmt->num_rows > 0) {
        // Generate a token
        $token = bin2hex(random_bytes(50));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Insert token into db
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token=?, expires=?");
        if (!$stmt) {
            die("Prepare failed for insert: " . $conn->error);
        }

        $stmt->bind_param("sssss", $email, $token, $expires, $token, $expires);
        $stmt->execute();

        // Email the reset link

        header("Location: verify_reset_token.php");
        exit();
    } else {
        header("Location: forgot_password.php?error=not_found");
        exit();
    }
} else {
    header("Location: forgot_password.php");
    exit();
}
