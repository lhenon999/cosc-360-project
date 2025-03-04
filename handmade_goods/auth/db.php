<?php
require_once '../config.php';
session_start();
session_regenerate_id(true);

error_reporting(E_ALL);
ini_set('display_errors', 1);
// var_dump($_FILES);
// exit();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Register 
    if (isset($_POST["register"])) {
        $name = trim($_POST["full_name"]);
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        $user_type = 'normal';
        $profile_picture = "/cosc-360-project/handmade_goods/assets/images/default-profile.jpg"; // Default profile picture

        // Ensure uploads directory exists
        $upload_dir = "assets/images/uploads/profile_pictures/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
            // Sanitize file name
            $file_name = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES["profile_picture"]["name"]));
            $target_file = $upload_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = array("jpg", "jpeg", "png", "gif");
        
            if (in_array($imageFileType, $allowed_types)) {
                error_log("Attempting to move file from: " . $_FILES["profile_picture"]["tmp_name"]);
                error_log("Target file path: " . $target_file);
        
                if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    error_log("Move failed: " . print_r(error_get_last(), true));
                    header("Location: /cosc-360-project/handmade_goods/auth/register.php?error=file_upload_failed");
                    exit();
                } else {
                    $profile_picture = "/cosc-360-project/handmade_goods/" . $target_file;
                }
            } else {
                error_log("Invalid file type: " . $imageFileType);
                header("Location: /cosc-360-project/handmade_goods/auth/register.php?error=invalid_file");
                exit();
            }
        }
        
        $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            header("Location: ../pages/register.php?error=email_taken");
            exit();
        }
        $check->close();

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, profile_picture) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $user_type, $profile_picture);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $_SESSION["user_id"] = $user_id;
            $_SESSION["email"] = $email;
            $_SESSION["user_name"] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $_SESSION["user_type"] = $user_type;
            $_SESSION["profile_picture"] = $profile_picture;
            
            header("Location: /cosc-360-project/handmade_goods/pages/home.php");
            exit();
        } else {
            header("Location: /cosc-360-project/handmade_goods/auth/register.php?error=registration_failed");
            exit();
        }
        $stmt->close();
    }
}

    // Login
    if (isset($_POST["login"])) {
        $email = trim($_POST["email"]);
        $password = $_POST["password"];

        $stmt = $conn->prepare("SELECT id, name, password, user_type, profile_picture FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id, $name, $hashed_password, $user_type, $profile_picture);
            $stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                $_SESSION["user_id"] = $user_id;
                $_SESSION["email"] = $email;
                $_SESSION["user_name"] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                $_SESSION["user_type"] = $user_type;
                $_SESSION["profile_picture"] = $profile_picture;
                header("Location: http://localhost/cosc-360-project/handmade_goods/pages/home.php");
                exit();
            } else {
                header("Location: /cosc-360-project/handmade_goods/auth/login.php?error=invalid");
                exit();
            }
        } else {
            header("Location: http://localhost/cosc-360-project/handmade_goods/pages/login.php?error=nouser");
            exit();

        }
        $stmt->close();
    }
