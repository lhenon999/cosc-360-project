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

    $stmt = $conn->prepare("UPDATE USERS SET is_frozen = 1 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE ITEMS SET status = 'inactive' WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = "User account and listings have been frozen.";
    header("Location: ../user_profile.php?id=$user_id");
    exit();
?>
