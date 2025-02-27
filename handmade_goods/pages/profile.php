<?php
session_start();
require_once '../config.php'; // Ensure database connection

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
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Handmade Goods</div>
            <div class="search-bar">
                <input type="search" placeholder="Search...">
            </div>
            <ul class="nav-links">
                <li><a href="../pages/home.php">Home</a></li>
                <li><a href="shop.php">Shop</a></li>
                <li><a href="about.php">About</a></li>
                <li class="dropdown">
                    <a href="profile.php">My profile <span class="material-symbols-outlined">expand_more</span></a>
                    <div class="dropdown-content">
                        <a href="profile.php">View Profile</a>
                        <a href="settings.php">Settings</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </li>
                <li><a href="cart.php" class="basket">Basket (2)</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>My Profile</h1>
        
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-image">
                    <img src="../assets/images/default-profile.jpg" alt="Profile Picture">
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($name); ?></h2>
                    <p><?php echo htmlspecialchars($email); ?></p>
                    <div class="profile-buttons">
                        <button class="settings-btn">Settings</button>
                        <button class="orders-btn">List Orders</button>
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
    </main>
</body>
</html> 