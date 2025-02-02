<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar</title>
    <link rel="stylesheet" href="../css/navbar_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<script>
    function confirmLogout() {
        return confirm("Are you sure you want to log out?");
    }
</script>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-logo" href="#">Handmade Goods</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav me-auto ms-4">
                    <li class="nav-item"><a class="nav-link" href="../pages/home.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../pages/products.php">Shop</a></li>
                    <li class="nav-item"><a class="nav-link" href="#footer">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                </ul>
                <form class="d-flex navbar-search me-3">
                    <input class="form-control me-2" type="search" placeholder="Search">
                    <button class="btn btn-primary" type="submit"> <img src="../assets/images/icons/search.png"
                            alt="Search" width="25" height="25"></button>
                </form>
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="../logout.php"  onclick="return confirmLogout();">Logout</a></li>
                        <li class="nav-item">
                            <a class="nav-link account-link" href="../pages/account.php">
                                <img src="../assets/images/icons/accIcon.png" alt="Account" class="account-icon">
                                <span class="nav-user">Hello,
                                    <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="../pages/login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="../pages/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</body>

</html>