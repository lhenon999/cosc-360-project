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
        $profile_picture = "/cosc-360-project/handmade_goods/assets/images/default-profile.jpg";

        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/cosc-360-project/handmade_goods/assets/images/uploads/profile_pictures/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
            $imageFileType = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $allowed_types = array("jpg", "jpeg", "png", "gif");
        
            if (in_array($imageFileType, $allowed_types)) {
                $file_name = "profile_" . uniqid() . "." . $imageFileType;
                $target_file = $upload_dir . $file_name;

                if (!file_exists($_FILES["profile_picture"]["tmp_name"])) {
                    header("Location: /cosc-360-project/handmade_goods/auth/register.php?error=temp_file_missing");
                    exit();
                }

                if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    header("Location: /cosc-360-project/handmade_goods/auth/register.php?error=file_upload_failed");
                    exit();
                } else {
                    $profile_picture = str_replace($_SERVER['DOCUMENT_ROOT'], '', $target_file);
                }
            } else {
                header("Location: /cosc-360-project/handmade_goods/auth/register.php?error=invalid_file");
                exit();
            }
        }
        
        $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Email already exists, redirect with clear error message
            header("Location: ../auth/register.php?error=email_taken&email=" . urlencode($email));
            exit();
        }
        $check->close();

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Add try-catch to handle potential duplicate entry errors that might slip through
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, profile_picture) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $user_type, $profile_picture);

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                $_SESSION["user_id"] = $user_id;
                $_SESSION["email"] = $email;
                $_SESSION["user_name"] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                $_SESSION["user_type"] = $user_type;
                $_SESSION["profile_picture"] = $profile_picture;

                $api_key = "api-DFEA151D81194B3EB9B6CF30891D53A5";
                $email_data = [
                    "api_key" => $api_key,
                    "sender" => "handmadegoods@mail2world.com",
                    "to" => [$email],
                    "subject" => "Welcome to Handmade Goods",
                    "html_body" => "<h1>Hello $name,</h1><p>Thank you for signing up! Welcome to Handmade Goods.</p>",
                ];

                $ch = curl_init("https://api.smtp2go.com/v3/email/send");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'accept: application/json']);

                $response = curl_exec($ch);
                curl_close($ch);
                
                header("Location: /cosc-360-project/handmade_goods/pages/home.php");
                exit();
            } else {
                header("Location: /cosc-360-project/handmade_goods/auth/register.php?error=registration_failed");
                exit();
            }
        } catch (Exception $e) {
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
        $remember = isset($_POST["remember"]); 

        if ($remember) {
            $token = bin2hex(random_bytes(32));
    
            $cookie_stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE email = ?");
            $cookie_stmt->bind_param("ss", $token, $email);
            
            if (!$cookie_stmt->execute()) {
                echo "Error updating remember me token.";
            }
            $cookie_stmt->close();
    
            setcookie("remember_token", $token, time() + (30 * 24 * 60 * 60), "/", "", false, true);
            setcookie("user_email", $email, time() + (30 * 24 * 60 * 60), "/", "", false, true);
        }
    
        
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
            header("Location: http://localhost/cosc-360-project/handmade_goods/auth/login.php?error=nouser");
            exit();

        }
        $stmt->close();
    }
