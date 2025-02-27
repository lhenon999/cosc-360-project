<?php
session_start();
require_once '../config.php'; // Ensure database connection

// Check if user is logged in
/*if (!isset($_SESSION["user_id"])) {
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
$stmt->close();*/


// For now, using placeholder data instead of database fetch
$name = "John Doe";
$email = "john.doe@example.com";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Handmade Goods</title>

    <style>@import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');</style>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
</head>
<body>
    <?php include '../assets/html/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">My Profile</h1>
        
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-image">
                    <img src="../assets/images/default-profile.jpg" alt="Profile Picture">
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($name); ?></h2>
                    <p><?php echo htmlspecialchars($email); ?></p>
                    <div class="profile-buttons">
                        <button class="cta hover-raise">Settings</button>
                        <button class="cta hover-raise">List Orders</button>
                    </div>
                </div>
            </div>

            <div class="profile-tabs">
                <nav class="tabs-nav">
                    <a href="#orders" class="active">My Orders</a>
                    <a href="#reviews">My Reviews</a>
                    <a href="#activity">Other Activity</a>
                </nav>
                <div class="tab-content">
                    <!-- Tab content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <?php include "../assets/html/footer.php"; ?>
</body>
</html> 