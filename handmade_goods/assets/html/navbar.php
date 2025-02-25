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
                <li class="hover-raise"><a class="navlink" href="#">My Profile</a></li>
            </ul>
        </div>
        <button class="cta hover-raise">Basket</button>
    </div>
    <script>
        $("#toggle-nav").click(function() {
            $(".navright").toggleClass("active");
            $(this).text($(this).text() == "menu" ? "close" : "menu");
        });
    </script>
</nav>