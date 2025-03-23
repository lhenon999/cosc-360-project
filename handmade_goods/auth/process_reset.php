<?php
session_start();
require __DIR__ . '/../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = trim($_POST['token']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($token) || empty($email) || empty($new_password) || empty($confirm_password)) {
        header("Location: login.php?error=password_reset_failed");
        exit();
    }

    $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $user_stmt->bind_param("s", $email);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows === 0) {
        header("Location: forgot_password.php?error=not_found");
        exit();
    }

    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    if (!$update_stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $update_stmt->bind_param("ss", $hashed_password, $email);

    if ($update_stmt->execute()) {
        $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $delete_stmt->bind_param("s", $email);
        $delete_stmt->execute();

        header("Location: login.php?success=password_reset");
        exit();
    } else {
        header("Location: login.php?error=password_reset_failed");
        exit();
    }
} else {
    header("Location: forgot_password.php?error=invalid_request");
    exit();
}
?>
