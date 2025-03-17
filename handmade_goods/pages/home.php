<?php 
session_start();
include '../config.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Handmade Goods - Home</title>

        <style>@import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');</style>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="../assets/css/globals.css">
        <link rel="stylesheet" href="../assets/css/navbar.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/home.css">
        <link rel="stylesheet" href="../assets/css/landing_slider.css">
        <link rel="stylesheet" href="../assets/css/product_card.css">
    </head>

    <body>
        <?php include '../assets/html/navbar.php'; ?>

        <div class="container text-center">
            <h1 >Welcome to Handmade Goods</h1>
            <p class="text-muted mb-5">Discover unique local products crafted with care</p>

            <?php include '../assets/html/landing_slider.php'; ?>

            <script>
                $(document).ready(function () {
                    let counter = 0;
                    let slides = $(".home-img");

                    function showSlides() {
                        slides.hide();
                        counter++;
                        if (counter > slides.length) { counter = 1; }
                        slides.eq(counter - 1).fadeIn();
                        setTimeout(showSlides, 5000);
                    }
                    
                    showSlides();
                });
            </script>

            <h3 class="text-center mt-5">Browse by Category</h3>
            <?php
            $cat_stmt = $conn->prepare("SELECT DISTINCT category FROM items WHERE category IS NOT NULL ORDER BY category");
            $cat_stmt->execute();
            $cat_result = $cat_stmt->get_result();
            $categories = [];
            while ($row = $cat_result->fetch_assoc()) {
                $categories[] = $row['category'];
            }
            $cat_stmt->close();
            ?>

            <div class="category-container d-flex justify-content-center flex-wrap">
                <?php foreach ($categories as $category): ?>
                    <div class="category-button" onclick="window.location.href='products.php?category=<?= rawurlencode($category) ?>'"><?= htmlspecialchars($category) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="container mt-5 text-center">
            <h3>What's New</h3>
            <p>Discover the latest handmade creations and featured products</p>
            <div class="product-cards-container" id="product-cards-container">
                <?php
                $stmt = $conn->prepare("SELECT id, name, price, img, stock FROM items ORDER BY created_at DESC LIMIT 6");
                $stmt->execute();
                $result = $stmt->get_result();
                while($product = $result->fetch_assoc()):
                    $id = htmlspecialchars($product["id"]);
                    $name = htmlspecialchars($product["name"]);
                    $price = number_format($product["price"], 2);
                    $image = htmlspecialchars($product["img"]);
                    $stock = intval($product["stock"]);
                    $stock_class = $stock > 5 ? 'in-stock' : ($stock > 0 ? 'low-stock' : 'out-of-stock');
                    include "../assets/html/product_card.php";
                endwhile;
                $stmt->close();
                ?>
            </div>
            <div class="view-more-container text-center mt-4">
                <a href="products.php" class="hover-raise cta">View More</a>
            </div>
        </div>

        <div class="container mt-5 mb-3">
            <h3 class="text-center">Get In Touch</h3>
            <div class="contact-form-container">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="cta hover-raise">Submit</button>
                    </div>
                </form>
            </div>
        </div>

        </div>
        <?php include "../assets/html/footer.php"; ?>
    </body>

</html>