<?php
require_once 'config.php';
session_start();
session_regenerate_id(true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Register 
    if (isset($_POST["register"])) {
        $name = trim($_POST["full_name"]);
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        $user_type = 'normal';

        $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            die("Error: Email already registered.");
        }
        $check->close();

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashed_password, $user_type);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $_SESSION["user_id"] = $user_id;
            $_SESSION["email"] = $email;
            $_SESSION["user_name"] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $_SESSION["user_type"] = $user_type;
            
            header("Location: ../pages/home.php");
            exit();
        } else {
            die("Error: Registration failed.");
        }
        $stmt->close();
    }
    
    // Login
    if (isset($_POST["login"])) {
        $email = trim($_POST["email"]);
        $password = $_POST["password"];

        $stmt = $conn->prepare("SELECT id, name, password, user_type FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id, $name, $hashed_password, $user_type);
            $stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                $_SESSION["user_id"] = $user_id;
                $_SESSION["email"] = $email;
                $_SESSION["user_name"] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                $_SESSION["user_type"] = $user_type;
                header("Location: ../pages/home.php");
                exit();
            } else {
                die("Error: Invalid email or password.");
            }
        } else {
            die("Error: No user found with that email.");
        }
        $stmt->close();
    }
    
    // Logout
    if (isset($_POST["logout"])) {
        session_destroy();
        header("Location: index.php");
        exit();
    }
}
