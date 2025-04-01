<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Create paths using directory constants
$base_dir = dirname(dirname(__FILE__));
$upload_dir = $base_dir . "/assets/images/uploads/profile_pictures/";
$web_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $base_dir);

// Create upload directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_picture"])) {
    $allowedTypes = ["jpg", "jpeg", "png", "gif"];
    $maxFileSize = 2 * 1024 * 1024;

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
        $profilePicPath = $web_path . "/assets/images/uploads/profile_pictures/" . $newFileName;

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
