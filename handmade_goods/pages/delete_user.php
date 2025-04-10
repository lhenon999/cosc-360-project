<?php
    session_start();
    include __DIR__ . '/../config.php';

    // Check if a user ID was provided
    if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
        $_SESSION['error'] = "Invalid user ID provided.";
        header("Location: profile.php");
        exit();
    }

    $user_id = intval($_POST['user_id']);

    try {
        // Start a transaction to ensure all operations complete or none do
        $conn->begin_transaction();

        // First get the user's email to avoid collation issues
        $email_stmt = $conn->prepare("SELECT email FROM USERS WHERE id = ?");
        $email_stmt->bind_param("i", $user_id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        $user_email = "";
        if ($email_row = $email_result->fetch_assoc()) {
            $user_email = $email_row['email'];
        }
        $email_stmt->close();

        // First, let's delete all items belonging to the user
        $stmt = $conn->prepare("DELETE FROM ITEMS WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Delete user's reviews
        $stmt = $conn->prepare("DELETE FROM REVIEWS WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Delete user's addresses
        $stmt = $conn->prepare("DELETE FROM ADDRESSES WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Delete the user's orders
        $stmt = $conn->prepare("SELECT id FROM ORDERS WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderIds = [];
        while ($row = $result->fetch_assoc()) {
            $orderIds[] = $row['id'];
        }
        $stmt->close();

        // Delete order items for each order
        if (!empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $stmt = $conn->prepare("DELETE FROM ORDER_ITEMS WHERE order_id = ?");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Delete the orders
        $stmt = $conn->prepare("DELETE FROM ORDERS WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Finally, delete the user
        $stmt = $conn->prepare("DELETE FROM USERS WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        $_SESSION['success'] = "User account and all associated data have been deleted successfully.";
        header("Location: profile.php#users");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if any error occurred
        $conn->rollback();
        $_SESSION['error'] = "An error occurred while deleting the user account: " . $e->getMessage();
        header("Location: profile.php");
        exit();
    }