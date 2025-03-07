<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
            <input type="text" name="search" placeholder="Search products and people..." class="search-input"
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
                        <a href="../logout.php">Logout</a>
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
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const searchInput = document.querySelector(".search-input");
            const suggestionsBox = document.createElement("div");
            suggestionsBox.id = "search-suggestions";
            suggestionsBox.style.position = "absolute";
            suggestionsBox.style.background = "#fff";
            suggestionsBox.style.border = "1px solid #ddd";
            suggestionsBox.style.padding = "10px";
            suggestionsBox.style.display = "none";
            suggestionsBox.style.zIndex = "1000";
            searchInput.parentNode.appendChild(suggestionsBox);

            function updateSuggestionsPosition() {
                const rect = searchInput.getBoundingClientRect();
                suggestionsBox.style.top = `${rect.bottom + window.scrollY}px`;
                suggestionsBox.style.left = `${rect.left + window.scrollX}px`;
                suggestionsBox.style.width = `${rect.width}px`;
            }

            searchInput.addEventListener("input", function () {
                let query = this.value.trim();
                if (query.length > 1) {
                    fetch(`../pages/products.php?search=${encodeURIComponent(query)}&ajax=true`)
                        .then(response => response.json())
                        .then(data => {
                            suggestionsBox.innerHTML = "";
                            updateSuggestionsPosition();

                            if (data.products.length > 0) {
                                let productSection = document.createElement("div");
                                data.products.forEach(p => {
                                    let item = document.createElement("div");
                                    item.innerHTML = `<a href="../pages/product.php?id=${p.id}" class="suggestion-item"> <span class="material-symbols-outlined icon">shopping_bag</span> ${p.name}</a>`;
                                    productSection.appendChild(item);
                                });
                                suggestionsBox.appendChild(productSection);
                            }

                            if (data.users.length > 0) {
                                let userSection = document.createElement("div");
                                data.users.forEach(u => {
                                    let profileImage = u.profile_picture ? `<img src="${u.profile_picture}" class="profile-pic" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">` : `<span class="material-symbols-outlined icon">person</span>`;
                                    let item = document.createElement("div");
                                    item.innerHTML = `<a href="../pages/user_profile.php?id=${u.id}" class="suggestion-item">${profileImage} ${u.name}</a>`;
                                    userSection.appendChild(item);
                                });
                                suggestionsBox.appendChild(userSection);
                            }

                            if (data.products.length > 0 || data.users.length > 0) {
                                suggestionsBox.style.display = "block";
                            } else {
                                suggestionsBox.style.display = "none";
                            }
                        })
                        .catch(error => console.error("Error fetching search results:", error));
                } else {
                    suggestionsBox.style.display = "none";
                }
            });

            document.addEventListener("click", function (e) {
                if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    suggestionsBox.style.display = "none";
                }
            });

            window.addEventListener("resize", updateSuggestionsPosition);
        });

    </script>
</nav>