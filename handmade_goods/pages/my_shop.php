<?php
session_start();
$is_logged_in = isset($_SESSION["user_id"]);

if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

include __DIR__ . '/../config.php';

$user_email = $_SESSION["user_id"];
$products = [];

$stmt = $conn->prepare("SELECT id, name, price, img, stock FROM ITEMS WHERE user_id = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Fix image paths
    if (!empty($row['img'])) {
        // Keep paths that already have '../' prefix or start with '/'
        if (substr($row['img'], 0, 3) !== '../' && substr($row['img'], 0, 1) !== '/') {
            // Handle case where only the filename is stored
            if (!strpos($row['img'], '/')) {
                $row['img'] = '../assets/images/uploads/product_images/' . $row['img'];
            }
        }
    } else {
        // No image path, use placeholder
        $row['img'] = '../assets/images/placeholder.webp';
    }
    
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

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/products.css?v=1">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/product_card.css?v=4">
</head>

<body>
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

    <h1 class="text-center">My Shop</h1>
    <p class="text-center">Browse and edit your listings</p>
    <br>
    <div class="d-flex justify-content-center mb-5">
        <a class="cta hover-raise" href="create_listing.php">
            <span class="material-symbols-outlined">add</span> Create a new listing
        </a>
    </div>

    <div class="container">
        <div class="scrollable-container">
            <div class="listing-grid">
                <?php if (!empty($products)): ?>
                    <?php $isFromProfile = true; ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $id = htmlspecialchars($product["id"]);
                        $name = htmlspecialchars($product["name"]);
                        $price = number_format($product["price"], 2);
                        $image = htmlspecialchars($product["img"]);
                        $stock = intval($product["stock"]);
                        $stock_class = $stock > 5 ? 'in-stock' : ($stock > 0 ? 'low-stock' : 'out-of-stock');
                        $from_profile = "my_shop";
                        include "../assets/html/product_card.php";
                        ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center">You have no current listings</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . "/../assets/html/footer.php"; ?>
</body>
</html>