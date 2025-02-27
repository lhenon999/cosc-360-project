<?php
session_start();
$is_logged_in = isset($_SESSION["user_id"]);
include '../config.php';

$products = [];
$stmt = $conn->prepare("SELECT id, name, price, img FROM items");
$stmt->execute();
$result = $stmt->get_result();

$products = [];
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
        <title>Handmade Goods - Browse</title>

        <style>@import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');</style>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="../assets/css/globals.css">
        <link rel="stylesheet" href="../assets/css/products.css?v=1">
        <link rel="stylesheet" href="../assets/css/navbar.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/product_card.css">
    </head>

    <body>
        <?php include '../assets/html/navbar.php'; ?>
        <h1 class="text-center">Explore our products!</h1>
        <p class="text-center">Browse our collection and discover what suits you</p>

        <div class="container">
            <div class="sidebar">
                <div class="filter-container">
                    <div class="filter-section">
                        <h4>Categories</h4>
                        <label><input type="checkbox" name="category" value="category1"> Category 1</label><br>
                        <label><input type="checkbox" name="category" value="category2"> Category 2</label><br>
                        <label><input type="checkbox" name="category" value="category3"> Category 3</label><br>
                        <label><input type="checkbox" name="category" value="category4"> Category 4</label><br>
                    </div>
                    <div class="filter-section">
                        <h4>Price Range</h4>
                        <label>From: <input type="number" name="price-from" min="0" step="10"> </label><br>
                        <label>To: <input type="number" name="price-to" min="0" step="10"> </label><br>
                    </div>

                    <div class="filter-section">
                        <h4>Ratings</h4>
                        <label><input type="radio" name="rating" value="1"> 1 Star</label><br>
                        <label><input type="radio" name="rating" value="2"> 2 Stars</label><br>
                        <label><input type="radio" name="rating" value="3"> 3 Stars</label><br>
                        <label><input type="radio" name="rating" value="4"> 4 Stars</label><br>
                        <label><input type="radio" name="rating" value="5"> 5 Stars</label><br>
                    </div>

                    <button type="submit">Apply Filters</button>
                </div>
            </div>
            <div class="scrollable-container">
                <div class="listing-grid">
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
            </div>
        </div>
        <?php include '../assets/html/footer.php'; ?>
    </body>
</html>