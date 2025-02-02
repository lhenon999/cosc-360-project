<?php session_start();
include 'test-products.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="assets/css/navbar_styles.css">
    <link rel="stylesheet" href="assets/css/footer_styles.css">
    <link rel="stylesheet" href="assets/css/home_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center text-uppercase fw-bold">Welcome to Handmade Goods</h1>
        <p class="text-center text-muted mb-5">Discover unique handmade products crafted with care.</p>

        <div class="img-container">
            <div class="home-img">
                <img src="assets/images/image1.jpg">
            </div>
            <div class="home-img">
                <img src="assets/images/image2.jpg">
            </div>
            <div class="home-img">
                <img src="assets/images/image3.jpg">
            </div>
            <div class="home-img">
                <img src="assets/images/image4.jpg">
            </div>
            <div class="home-img">
                <img src="assets/images/stock_image.webp">
            </div>
        </div>

        <script>
            let counter = 0;

            function showSlides() {
                let slides = document.querySelectorAll(" .home-img"); slides.forEach(slide => {
                    slide.style.display = "none";
                });

                counter++;
                if (counter > slides.length) { counter = 1 }
                slides[counter - 1].style.display = "block";

                setTimeout(showSlides, 5000);
            }

            showSlides();
        </script>

        <h3 class="text-center mt-5">Browse by Category</h3>
        <div class="category-container d-flex justify-content-center flex-wrap">
            <div class="category-button">Woodwork</div>
            <div class="category-button">Jewelry</div>
            <div class="category-button">Textiles</div>
            <div class="category-button">Pottery</div>
            <div class="category-button">Art</div>
            <div class="category-button">Glasswork</div>
            <div class="category-button">Leather Goods</div>
            <div class="category-button">Metal Crafts</div>
            <div class="category-button">Sculptures</div>
            <div class="category-button">Home Decor</div>
            <div class="category-button">Stationery</div>
            <div class="category-button">Handmade Candles</div>
            <div class="category-button">Rugs</div>
        </div>
    </div>

    <div class="container mt-5">
        <h3 class="text-center">What's New</h3>
        <p class="text-center">Discover the latest handmade creations and featured products.</p>
        <div class="product-cards-container" id="product-cards-container">
            <?php
            $counter = 0;
            foreach ($products as $product):
                if ($counter >= 4)
                    break;
                $name = htmlspecialchars($product["name"]);
                $price = number_format($product["price"], 2);
                $image = htmlspecialchars($product["image"]);
                ?>
                <?php include "product_card.php"; ?>
                <?php $counter++; ?>
            <?php endforeach; ?>
        </div>
        <div class="view-more-container text-center mt-4">
            <a href="products.php" class="btn btn-outline-dark rounded-pill px-4 py-2">View More</a>
        </div>
    </div>

    <div id="footer"></div>
        <?php include "footer.php"; ?>
    </div>

</body>

</html>