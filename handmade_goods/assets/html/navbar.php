<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($conn)) {
    include_once __DIR__ . '/../../config.php';
}

$totalItems = 0;
if (isset($_SESSION["user_id"])) {
    // Retrieve the user's cart and total quantity from the database
    $stmt = $conn->prepare("SELECT id FROM CART WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($cart) {
        $cart_id = $cart['id'];
        $stmt = $conn->prepare("SELECT SUM(quantity) AS total FROM CART_ITEMS WHERE cart_id = ?");
        $stmt->bind_param("i", $cart_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $totalItems = $result["total"] ? $result["total"] : 0;
        $stmt->close();
    }
}

?>
<nav>
    <div class="navleft">
        <a href="../pages/home.php">
            <h3 class="cta hover-raise"><span class="material-symbols-outlined logo">spa</span>Handmade Goods</h3>
        </a>
        <button class="material-symbols-outlined" id="toggle-nav">menu</button>
    </div>
    <div class="navright">
        <form action="../pages/products.php" method="GET" class="search-form">
            <input type="text" name="search" placeholder="Products, categories, or sellers" class="search-input"
                aria-label="Search">
            <input type="hidden" name="search_type" value="products">
            <button type="submit" class="search-button">
                <span class="material-symbols-outlined">search</span>
            </button>
        </form>
        <div class="navlinks">
            <ul>
                <li class="hover-raise"><a class="navlink" href="../pages/home.php">Home</a></li>
                <li class="hover-raise"><a class="navlink" href="../pages/products.php">Shop</a></li>
                <li class="hover-raise"><a class="navlink" href="../pages/about.php">About</a></li>
            </ul>
        </div>
        <span class="hover-raise dropdown">
            <span class="material-symbols-outlined">account_circle</span>
            <span class="material-symbols-outlined" id="downArrow">expand_more</span>
            <div class="dropdown-content">
                <?php if (isset($_SESSION["user_id"])): ?>
                    <?php if ($_SESSION["user_type"] === 'admin'): ?>
                        <a href="../pages/profile.php">Dashboard</a>
                        <a href="../pages/settings.php">Admin Settings</a>
                        <a href="../auth/logout.php">Logout</a>
                    <?php else: ?>
                        <a href="../pages/profile.php">View Profile</a>
                        <a href="../pages/my_shop.php">My Shop</a>
                        <a href="../pages/settings.php">Settings</a>
                        <a href="../auth/logout.php">Logout</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="../auth/login.php">Login</a>
                    <a href="../auth/register.php">Sign Up</a>
                <?php endif; ?>
            </div>
        </span>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <?php if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "admin"): ?>
            <span class="dropdown overview">
                <a id="basket-btn" class="m-btn <?php echo !isset($_SESSION["user_id"]) ? 'not-logged-in' : ''; ?>"
                    href="../pages/basket.php">
                    <i class="fas fa-shopping-cart cart-icon"></i>
                    <?php if (isset($_SESSION["user_id"]) && $totalItems > 0): ?>
                        <span class="badge"><?= $totalItems ?></span>
                    <?php endif; ?>
                </a>
                <div class="overview-content">
                    <?php if (isset($_SESSION["user_id"])): ?>
                        <?php include "basket_overview.php"; ?>
                    <?php endif; ?>
                </div>
            </span>
        <?php endif; ?>
        <?php if (isset($_SESSION["user_type"]) && $_SESSION["user_type"] === "admin"): ?>
            <style>
                .dropdown .dropdown-content {
                    right: 1px;
                }
            </style>
        <?php endif; ?>


    </div>
    <script>
        $("#toggle-nav").click(function () {
            $(".navright").toggleClass("active");
            $(this).text($(this).text() == "menu" ? "close" : "menu");
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let darkModeLocal = localStorage.getItem("darkMode") === "enabled";
            if (darkModeLocal) {
                document.body.classList.add("bg-dark", "text-light");
            }
        });
    </script>
    <script>window.loggedInUserId = <?php echo json_encode($_SESSION['user_id']); ?>;</script>
    <script src="../assets/js/search_suggestions.js"></script>
</nav>