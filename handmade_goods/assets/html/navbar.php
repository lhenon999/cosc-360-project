<nav>
    <div class="navleft">
        <h3 class="cta hover-raise"><span class="material-symbols-outlined logo">spa</span>Handmade Goods</h3>
        <button class="material-symbols-outlined" id="toggle-nav"">menu</button>
    </div>
    <div class="navright">
        <span class="material-symbols-outlined hover-raise">search</span>
        <div class="navlinks">
            <ul>
                <li class="hover-raise"><a class="navlink" href="../pages/home.php">Home</a></li>
                <li class="hover-raise"><a class="navlink" href="../pages/products.php">Shop</a></li>
                <li class="hover-raise"><a class="navlink" href="#">About</a></li>
            </ul>
        </div>
        <span class="hover-raise dropdown"><span class="material-symbols-outlined">account_circle</span><span class="material-symbols-outlined" id="downArrow">expand_more</span>
            <div class="dropdown-content">
                <a href="../pages/profile.php">View Profile</a>
                <a href="../pages/settings.php">Settings</a>
                <a href="../pages/logout.php">Logout</a>
            </div>
        </span>
        <button class="cta hover-raise"><span class="material-symbols-outlined" id="basket">shopping_basket</span>Basket</button>
    </div>
    <script>
        $("#toggle-nav").click(function() {
            $(".navright").toggleClass("active");
            $(this).text($(this).text() == "menu" ? "close" : "menu");
        });
    </script>
</nav>

