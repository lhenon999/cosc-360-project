<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar</title>
    <link rel="stylesheet" href="assets/css/navbar_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-logo" href="#">Handmade Goods</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav me-auto ms-4">
                    <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#product-cards-container">Shop</a></li>
                    <li class="nav-item"><a class="nav-link" href="#footer">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                </ul>
                <form class="d-flex navbar-search me-3">
                    <input class="form-control me-2" type="search" placeholder="Search">
                    <button class="btn btn-primary" type="submit"> <img src="assets/images/icons/search.png" alt="Search" width="25" height="25"></button>
                </form>
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="db/logout.php">Logout</a></li>
                        <li class="nav-item"><a class="nav-link" href="db/account.php"> <img src="assets/images/icons/accIcon.png" alt="Account" width="25" height="25"></a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="html/login.html">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="html/register.html">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</body>
</html>
