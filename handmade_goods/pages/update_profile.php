<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "User not logged in.";
    header("Location: settings.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if (empty($name) || empty($email)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: settings.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: settings.php");
        exit();
    }

    if (!preg_match("/^[a-zA-Z]+\s+[a-zA-Z]+$/", $name)) {
        $_SESSION['error'] = "Enter a valid first and last name.";
        header("Location: settings.php");
        exit();
    }    

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    if (!$stmt) {
        $_SESSION['error'] = "SQL prepare failed: " . $conn->error;
        header("Location: settings.php");
        exit();
    }

    $stmt->bind_param("ssi", $name, $email, $user_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully.";
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating profile: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    $_SESSION['error'] = "Invalid request.";
}

