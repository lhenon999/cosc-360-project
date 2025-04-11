<?php
    session_start();
    include __DIR__ . '/../config.php';

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        header("Location: ../index.php");
        exit();
    }

    if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
        header("Location: ../profile.php");
        exit();
    }

    $user_id = intval($_POST['user_id']);

    // Set is_frozen to 0 (unfrozen)
    $stmt = $conn->prepare("UPDATE USERS SET is_frozen = 0 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Reactivate all the user's listings
    $stmt = $conn->prepare("UPDATE ITEMS SET status = 'active' WHERE user_id = ? AND status = 'inactive'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = "User account has been unfrozen and listings have been reactivated.";
    header("Location: ../pages/profile.php#users");
    exit();
?>