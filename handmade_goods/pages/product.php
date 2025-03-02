<?php
session_start();
include '../config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit();
}
$product_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT id, name, description, price, img FROM items WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: products.php");
    exit();
}

$name = htmlspecialchars($product['name']);
$description = nl2br(htmlspecialchars($product['description']));
$price = number_format($product['price'], 2);
$image = htmlspecialchars($product['img']);
$stmt = $conn->prepare("SELECT id, name, description, price, img, user_id FROM items WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $name ?> - Handmade Goods</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');
    </style>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/product.css">
</head>

<body>
    <?php include '../assets/html/navbar.php'; ?>

    <main class="mt-5 product-page">
        <div class="col-md-6 img">
            <img src="<?= $image ?>" alt="<?= $name ?>" class="img-fluid product-image">
        </div>

        <div class="col-md-6 desc">
            <h1><?= $name ?></h1>
            <p class="text-muted">$<?= $price ?></p>
            <p class="mt-4"><?= $description ?></p>

            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $product['user_id']): ?>
                <a href="edit_listing.php?id=<?= $product['id'] ?>" class="cta hover-raise atc">
                    <span class="material-symbols-outlined">edit</span> Edit Listing
                </a>
            <?php else: ?>
                <form action="../basket/add_to_basket.php" method="POST">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <div class="quantity-add">
                        <input type="number" name="quantity" value="1" min="1" class="form-control">
                        <button type="submit" class="cta hover-raise atc"><span
                                class="material-symbols-outlined">add_shopping_cart</span>Add to Basket</button>
                    </div>
                </form>

            <?php endif; ?>


            <a href="products.php" class="btn btn-outline-secondary mt-3">Back to Products</a>
        </div>
    </main>

    <?php include "../assets/html/footer.php"; ?>
</body>

</html>