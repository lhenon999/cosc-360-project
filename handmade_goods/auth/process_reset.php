<?php
session_start();
require '../config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = trim($_POST['token']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Update the user's password in db
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    if (!$update_stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $update_stmt->bind_param("ss", $hashed_password, $email);
    
    if ($update_stmt->execute()) {
        $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $delete_stmt->bind_param("s", $email);
        $delete_stmt->execute();

        // Redirect to login
        header("Location: login.php?success=password_reset");
        exit();
    } else {
        header("Location: verify_reset_token.php?error=update_failed");
        exit();
    }
} else {
    header("Location: forgot_password.php?error=invalid_request");
    exit();
}
?>
