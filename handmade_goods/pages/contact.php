<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start(); // Prevent header issues
session_start();
require_once '../config.php';


// Ensure $conn is set
if (!isset($conn)) {
    die("Database connection failed.");
}

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php"); // Redirect to login page
    exit();
}

// Fetch user data from database
$user_id = $_SESSION["user_id"];
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="../css/navbar_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="account-info-container">
        <h2>Contact Us</h2>
        <p>If you have any questions, feel free to reach out to us at <strong>support@handmadegoods.com</strong>.</p>
    </div>

</body>
</html>

<?php ob_end_flush(); ?>
