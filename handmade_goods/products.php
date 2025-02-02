<?php
session_start();
$is_logged_in = isset($_SESSION["user_id"]);
include 'test-products.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <link rel="stylesheet" href="assets/css/product_styles.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>

<body>
    <?php include 'navbar.php'; ?>
    <h1 class="text-center">Explore our products !</h1>
    <p class="text-center">Browse our collection and discover what suits you.</p>

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
                <?php foreach ($products as $product): ?>
                    <?php
                    $name = htmlspecialchars($product["name"]);
                    $price = number_format($product["price"], 2);
                    $image = htmlspecialchars($product["image"]);
                    include "product_card.php";
                    ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>

</html>