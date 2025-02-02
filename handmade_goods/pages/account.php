<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once '../config.php';

$user_id = $_SESSION["user_id"];
if (!isset($_SESSION["user_email"])) {
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($name, $email);
    $stmt->fetch();
    $stmt->close();

    $_SESSION["user_name"] = $name;
    $_SESSION["user_email"] = $email;
} else {
    $name = $_SESSION["user_name"];
    $email = $_SESSION["user_email"];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link rel="stylesheet" href="../assets/css/navbar_styles.css">
    <link rel="stylesheet" href="../assets/css/account_styles.css">
</head>
<body>
    <?php include '../assets/html/navbar.php'; ?>
    <div class="account-info-container">
        <h2>My Account</h2>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($name); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
    </div>
</body>
</html>
