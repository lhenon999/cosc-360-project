<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../config.php';

if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    $_SESSION['error'] = "Invalid user ID provided.";
    header("Location: profile.php");
    exit();
}

$user_id = intval($_POST['user_id']);

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("DELETE FROM USERS WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);

    if (!$stmt->execute()) {
        $conn->rollback();
        throw new Exception("Failed to delete user account: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        $conn->rollback();
        throw new Exception("No user found with the provided ID.");
    }

    $conn->commit();



    if (isset($_POST['self_deletion']) && $_POST['self_deletion'] === '1') {
        session_unset();
        session_destroy();

        header("Location: login.php?account_deleted=1");
        header("Location: home.php");
    } else {
        $_SESSION['success'] = "User account and all associated data have been deleted successfully.";
        header("Location: profile.php");
    }
    exit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "An error occurred while deleting the user account: " . $e->getMessage();
    header("Location: profile.php");
    exit();
}