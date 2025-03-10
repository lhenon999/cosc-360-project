<?php
require_once 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Register 
    if (isset($_POST["register"])) {
        $name = trim($_POST["full_name"]);
        $email = trim($_POST["email"]);
        $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
        $user_type = 'normal';

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $user_type);

        if ($stmt->execute()) {
            $_SESSION["email"] = $email;
            $_SESSION["user_name"] = $name;
            $_SESSION["user_type"] = $user_type;
            header("Location: /cosc-360-project/handmade_goods/pages/home.php");
            exit();
        } else {
            echo "Registration failed!";
        }

        $stmt->close();
    }

    session_start();
    include '../config.php';
    
    // Login 
    if (isset($_POST["login"])) {
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
    
        $stmt = $conn->prepare("SELECT id, name, password, user_type FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
    
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $name, $hashed_password, $user_type);
            $stmt->fetch();
    
            if (password_verify($password, $hashed_password)) {
                $_SESSION["user_id"] = intval($id);
                $_SESSION["email"] = $email;
                $_SESSION["user_name"] = $name;
                $_SESSION["user_type"] = $user_type;
    
                // echo "Debug: User ID stored in session = " . $_SESSION["user_id"] . "<br>";
    
                header("Location: /cosc-360-project/handmade_goods/pages/home.php");
                exit();
            } else {
                echo "Invalid password.";
            }
        } else {
            echo "No account found with this email.";
        }
    
        $stmt->close();
    }
}
?>