<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_picture"])) {
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/cosc-360-project/handmade_goods/assets/images/uploads/profile_pictures/";

    $allowedTypes = ["jpg", "jpeg", "png", "gif"];
    $maxFileSize = 2 * 1024 * 1024;

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0775, true);
    }

    $fileName = $_FILES["profile_picture"]["name"];
    $fileTmpPath = $_FILES["profile_picture"]["tmp_name"];
    $fileSize = $_FILES["profile_picture"]["size"];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileSize > $maxFileSize) {
        $_SESSION["error"] = "File size exceeds 2MB limit.";
        header("Location: ../pages/profile.php");
        exit();
    }

    $newFileName = "profile_" . $user_id . "." . $fileExt;
    $uploadFile = $upload_dir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $uploadFile)) {
        $profilePicPath = "/cosc-360-project/handmade_goods/assets/images/uploads/profile_pictures/" . $newFileName;

        $stmt = $conn->prepare("UPDATE USERS SET profile_picture = ? WHERE id = ?");
        $stmt->bind_param("si", $profilePicPath, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION["success"] = "Profile picture updated successfully!";
    } else {
        $_SESSION["error"] = "File upload failed. Please try again.";
    }
} else {
    $_SESSION["error"] = "No file uploaded.";
}

header("Location: ../pages/profile.php");
exit();
?>
