<?php session_start();
include '../config.php';

$products = [];
$stmt = $conn->prepare("SELECT id, name, price, img FROM items ORDER BY created_at DESC LIMIT 6");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();
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
        <link rel="stylesheet" href="../assets/css/product_card.css">
    </head>

    <body>
        <?php include '../assets/html/navbar.php'; ?>

        <div class="container text-center">
            <h1 >Welcome to Handmade Goods</h1>
            <p class="text-muted mb-5">Discover unique local products crafted with care</p>

            <div class="img-container mt-5">
                <div class="home-img">
                    <img src="../assets/images/image1.jpg">
                </div>
                <div class="home-img">
                    <img src="../assets/images/image2.jpg">
                </div>
                <div class="home-img">
                    <img src="../assets/images/image3.jpg">
                </div>
                <div class="home-img">
                    <img src="../assets/images/image4.jpg">
                </div>
                <div class="home-img">
                    <img src="../assets/images/stock_image.webp">
                </div>
            </div>

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
            $categories = [
                "Woodwork", "Jewelry", "Textiles", "Pottery", "Sculptures", "Candles"
            ];
            ?>

            <div class="category-container d-flex justify-content-center flex-wrap">
                <?php foreach ($categories as $category): ?>
                    <div class="category-button"><?= htmlspecialchars($category) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="container mt-5 text-center">
            <h3>What's New</h3>
            <p>Discover the latest handmade creations and featured products</p>
            <div class="product-cards-container" id="product-cards-container">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $id = htmlspecialchars($product["id"]);
                        $name = htmlspecialchars($product["name"]);
                        $price = number_format($product["price"], 2);
                        $image = htmlspecialchars($product["img"]);
                        include "../assets/html/product_card.php";
                        ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center">No products available at the moment</p>
                    <a class="cta hover-raise">Create a Listing</a>
                <?php endif; ?>
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