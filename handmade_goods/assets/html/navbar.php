<?php
session_start();
if (!isset($conn)) {
    include_once __DIR__ . '/../../config.php';
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
            <input type="text" name="search" placeholder="Search products..." class="search-input" aria-label="Search">
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
                        <a href="../logout.php">Logout</a>
                    <?php else: ?>
                        <a href="../pages/profile.php">View Profile</a>
                        <a href="../pages/my_shop.php">My Shop</a>
                        <a href="../pages/settings.php">Settings</a>
                        <a href="../logout.php">Logout</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="../pages/login.php">Login</a>
                    <a href="../pages/register.php">Sign Up</a>
                <?php endif; ?>
            </div>
        </span>
        <a class="cta hover-raise auth-hide" href="../pages/basket.php"><span
                class="material-symbols-outlined">shopping_basket</span>Basket</a>
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
</nav>