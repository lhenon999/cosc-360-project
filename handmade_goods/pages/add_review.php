<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $product_id = isset($_POST["product_id"]) ? intval($_POST["product_id"]) : 0;
    $user_id = $_SESSION["user_id"];
    $rating = isset($_POST["rating"]) ? intval($_POST["rating"]) : 0;
    $comment = isset($_POST["comment"]) ? trim($_POST["comment"]) : "";

    if ($product_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid input."]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO REVIEWS (item_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);

    if ($stmt->execute()) {
        $review = [
            "user_id" => $user_id,
            "rating" => $rating,
            "comment" => htmlspecialchars($comment)
        ];
        header('Content-Type: application/json; charset=utf-8');

        $responseData = [
            'success' => true,
            'review' => [
                'user_id' => $user_id,
                'rating' => $rating,
                'comment' => $comment
            ]
        ];

        echo json_encode($responseData);

        exit;
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Error adding review."]);
        exit();
    }
    $stmt->close();
}
?>