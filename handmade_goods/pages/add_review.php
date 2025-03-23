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
            header("Location: product.php?id=" . $product_id);
            exit();
        } else {
            echo "Error adding review.";
        }
        $stmt->close();
    }
?>