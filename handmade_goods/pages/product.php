<?php
session_start();
include '../config.php';

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// var_dump($_POST);

// echo "Debug: User ID = $user_id, Product ID = $item_id, Quantity = $quantity <br>";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit();
}
$product_id = intval($_GET['id']);

$from_profile = isset($_GET['from']) && $_GET['from'] === 'user_profile';


$stmt = $conn->prepare("SELECT id, name, description, price, img, user_id, category, stock FROM items WHERE id = ?");
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
$user_id = intval($product['user_id']);
$session_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$default_image = "../assets/images/placeholder.webp";
$image_path = !empty($product['img']) ? htmlspecialchars($product['img']) : $default_image;
$category_name = isset($product['category']) ? htmlspecialchars($product['category']) : null;

$stmt = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$seller = $result->fetch_assoc();
$stmt->close();

$first_name = isset($seller['name']) ? explode(' ', trim($seller['name']))[0] : 'Seller';
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
    <link rel="stylesheet" href="../assets/css/product_card.css">
</head>

<body>
    <?php include '../assets/html/navbar.php'; ?>

    <main class="mt-5 product-page">
        <div class="col-md-6 img">
            <img src="<?= $image ?>" alt="<?= $name ?>" class="img-fluid product-image">
        </div>

        <div class="col-md-6 desc">
            <h1><?= $name ?></h1>
            <div class="price-category-container d-flex align-items-center">
                <p class="text-muted" id="price-label">$<?= $price ?></p>
                <?php if (!empty($category_name)): ?>
                    <a href="products.php?category=<?= rawurlencode($category_name) ?>"
                        class="btn btn-outline-secondary mt-3" id="category-btn">
                        <?= $category_name ?>
                    </a>
                <?php endif; ?>
            </div>

            <p class="mt-4"><?= $description ?></p>

            <?php if (!$from_profile): ?>
                <div class="seller-info mt-4 d-flex align-items-center mb-3">
                    <a href="user_profile.php?id=<?= $user_id ?>&from_product=product.php?id=<?= $product_id ?>"
                        class="d-flex align-items-center text-decoration-none text-dark">
                        <img src="<?= htmlspecialchars($seller['profile_picture']) ?>" alt="Seller Profile"
                            class="rounded-circle seller-profile-pic" width="50" height="47">
                        <p class="ms-3 mb-0">Sold by: <strong><?= htmlspecialchars($seller['name']) ?></strong></p>
                    </a>
                </div>
            <?php endif; ?>


            <?php if ($session_user_id !== null && $session_user_id === $user_id): ?>
                <a href="edit_listing.php?id=<?= $product_id ?>" class="cta hover-raise atc">
                    <span class="material-symbols-outlined">edit</span> Edit Listing
                </a>
                <a href="my_shop.php" class="btn btn-outline-secondary mt-3">Back to My Shop</a>
            <?php else: ?>
                <?php if ($product['stock'] > 0): ?>
                    <p class="stock-info <?= $product['stock'] < 5 ? 'low-stock' : '' ?>">
                        <?= $product['stock'] < 5 ? 'Only ' . $product['stock'] . ' left in stock!' : 'In Stock' ?>
                    </p>
                    <form action="/cosc-360-project/handmade_goods/basket/add_to_basket.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                        <div class="quantity-add">
                            <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="form-control quantity-input">
                            <button type="submit" class="cta hover-raise atc">
                                <span class="material-symbols-outlined">add_shopping_cart</span> Add to Basket
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="out-of-stock">Out of Stock</p>
                    <button class="cta hover-raise atc" disabled>
                        <span class="material-symbols-outlined">add_shopping_cart</span> Out of Stock
                    </button>
                <?php endif; ?>
                <a href="<?= isset($from_profile) && $from_profile ? 'user_profile.php?id=' . $user_id : 'products.php' ?>"
                    class="btn btn-outline-secondary mt-3">
                    Back to
                    <?= isset($from_profile) && $from_profile ? htmlspecialchars($first_name) . "'s Shop" : 'Products' ?>
                </a>
            <?php endif; ?>
        </div>
    </main>

</body>

</html>