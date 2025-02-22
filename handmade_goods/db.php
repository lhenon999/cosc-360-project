<?php
require_once 'config.php';
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST["register"])) {
        $name = trim($_POST["name"]);
        $email = trim($_POST["email"]);
        $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
    
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
    
        if ($check_stmt->num_rows > 0) {
            echo "Error: This email is already registered!";
            exit();
        }
        $check_stmt->close();
    
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);
        
        if ($stmt->execute()) {
            $_SESSION["user_id"] = $stmt->insert_id;
            $_SESSION["user_name"] = $name;
            header("Location: /handmade_goods/pages/account.php");
            exit();
        } else {
            echo "Registration failed!";
        }
    
        $stmt->close();
    }

    //Login 
    if (isset($_POST["login"])) {
        $email = trim($_POST["email"]);
        $password = $_POST["password"];

        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $name, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION["user_id"] = $id;
                $_SESSION["user_name"] = $name;
                
                header("Location: /handmade_goods/pages/home.php");
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
