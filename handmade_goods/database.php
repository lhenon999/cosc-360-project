<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    echo "Form submitted!<br>";
    echo "Received Name: " . $_POST["name"] . "<br>";
    echo "Received Email: " . $_POST["email"] . "<br>";
    echo "Received Password: " . $_POST["password"] . "<br>";
}

// register
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);
    $stmt->execute();
    $stmt->close();
    echo "Registration successful!";
}

//login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        session_start();
        $_SESSION["user_id"] = $id;
        echo "Login successful!";
        header("Location: /handmade_good/dashboard.php");
    } else {
        echo "Invalid email or password.";
    }
    $stmt->close();
}
?>
