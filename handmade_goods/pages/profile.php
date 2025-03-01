<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user_type = $_SESSION["user_type"]; 
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

    <div class="container">
        <h1 class="text-center mt-5"><?php echo ($user_type === 'admin') ? 'Admin Dashboard' : 'My Profile'; ?></h1>
        
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-image">
                    <img src="../assets/images/default-profile.jpg" alt="Profile Picture">
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($name); ?></h2>
                    <p><?php echo htmlspecialchars($email); ?></p>
                    <div class="profile-buttons">
                        <a class="cta hover-raise" href=""><span class="material-symbols-outlined">settings</span>Settings</a>

                        <?php if ($user_type !== 'admin') : ?>
                            <a class="cta hover-raise" href="../myShop.php"><span class="material-symbols-outlined">storefront</span>My Shop</a>
                        <?php endif; ?>

                        <a class="cta hover-raise" href="../logout.php"><span class="material-symbols-outlined">logout</span>Logout</a>
                    </div>
                </div>
            </div>

            <div class="profile-tabs mt-5">
                <nav class="tabs-nav">
                    <?php if ($user_type === 'admin'): ?>
                        <a href="#users" class="active">Users</a>
                        <a href="#listings">Listings</a>
                    <?php else: ?>
                        <a href="#orders" class="active">My Orders</a>
                        <a href="#reviews">My Reviews</a>
                        <a href="#activity">Other Activity</a>
                    <?php endif; ?>
                </nav>
                <div class="tab-content">
                    You currently have no activity.
                </div>
            </div>
        </div>
    </div>

    <?php include "../assets/html/footer.php"; ?>
</body>
</html>
