<?php
    session_start();
    include __DIR__ . '/../config.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $product_id = isset($_POST["product_id"]) ? intval($_POST["product_id"]) : 0;
        $user_id = $_SESSION["user_id"];
        $rating = isset($_POST["rating"]) ? intval($_POST["rating"]) : 0;
        $comment = isset($_POST["comment"]) ? trim($_POST["comment"]) : "";

        if ($product_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
            die("Invalid input.");
        }

        $stmt = $conn->prepare("INSERT INTO REVIEWS (item_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);

        if ($stmt->execute()) {
            $activity_details = "New Review: Rating $rating, Comment: " . substr($comment, 0, 50);
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $activity_stmt = $conn->prepare(
                "INSERT INTO ACCOUNT_ACTIVITY (user_id, event_type, ip_address, user_agent, details)
                 VALUES (?, 'review', ?, ?, ?)"
            );
            $activity_stmt->bind_param("isss", $user_id, $ip_address, $user_agent, $activity_details);
            $activity_stmt->execute();
            $activity_stmt->close();
    
            header("Location: product.php?id=" . $product_id);
            exit();
        } else {
            echo "Error adding review.";
        }
        $stmt->close();
    }
