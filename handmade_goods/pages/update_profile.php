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
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $removePicture = isset($_POST['remove_picture']);
    $errors = [];
    $profile_picture = null;

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

    if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = "/cosc-360-project/handmade_goods/assets/images/profile_pics/";
        $image_name = basename($_FILES['profile_picture']['name']);
        $image_path = $target_dir . time() . "_" . $image_name;
        $image_type = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));

        if (!in_array($image_type, ['jpg', 'jpeg', 'png', 'webp'])) {
            $errors[] = "Only JPG, JPEG, PNG, and WEBP are allowed.";
        }

        if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Max file size is 2MB.";
        }

        if (empty($errors)) {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $image_path)) {
                $profile_picture = $image_path;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current = $result->fetch_assoc();
        $stmt->close();

        $final_picture = $removePicture
            ? '/cosc-360-project/handmade_goods/assets/images/default_profile.png'
            : ($profile_picture ?? $current['profile_picture']);

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_picture = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $final_picture, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Profile updated successfully.";
            header("Location: profile.php");
            exit();
        } else {
            $_SESSION['error'] = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }

    $conn->close();
    header("Location: settings.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: settings.php");
    exit();
}
?>