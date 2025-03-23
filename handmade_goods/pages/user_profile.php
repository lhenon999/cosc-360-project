<?php
session_start();
include '../config.php';

$product_id = intval($_GET['id']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php");
}

$from_product = isset($_GET['from_product']) ? $_GET['from_product'] : null;
$from_admin = isset($_GET['from']) && $_GET['from'] === 'admin';
$from_profile_listings = isset($_GET['from']) && $_GET['from'] === 'profile_listings';
$from_profile_listings_user = isset($_GET['from']) && $_GET['from'] === 'profile_listing_users';
$from_profile_users = isset($_GET['from']) && $_GET['from'] === 'profile_users';

$user_id = intval($_GET['id']);

$query = "SELECT name, email, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

$first_name = explode(' ', trim($user['name']))[0];

$query = "SELECT id, name, img, price, stock FROM items WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$products_result = $stmt->get_result();
$products = $products_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handmade Goods - <?= htmlspecialchars($user['name']) ?>'s Profile</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');
    </style>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/products.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/product_card.css">
    <link rel="stylesheet" href="../assets/css/user_profile.css">
</head>

<body>

    <?php include '../assets/html/navbar.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" class="profile-pic">
            <div class="profile-info">
                <h1><?= htmlspecialchars($user['name']) ?></h1>
                <div class="profile-details">
                    <h3 class="contact-label">Contact</h3>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                </div>
                <?php if ($from_admin): ?>
                    <a href="profile.php" class="btn btn-outline-secondary w-100">Back</a>
                <?php elseif ($from_product): ?>
                    <a href="<?= htmlspecialchars($from_product . ($from_profile_listings ? (strpos($from_product, '?') !== false ? '&' : '?') . 'from=profile_listings' : '')) ?>"
                        class="btn btn-outline-secondary w-100" onclick="goBack(event)">Back</a>
                <?php elseif ($from_profile_listings_user): ?>
                    <a href="profile.php#listings" class="btn btn-outline-secondary w-100">Back</a>
                <?php elseif ($from_profile_users): ?>
                    <a href="profile.php#users" class="btn btn-outline-secondary w-100">Back</a>
                <?php endif; ?>

            </div>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                <a href="profile.php?from=admin&user=<?= urlencode($user['name']) ?>" class="manage-btn">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i> Moderate
                </a>
            <?php endif; ?>

        </div>

        <div class="reviews-containers">
            <div class="rating-summary">
                <h3>Review Summary</h3>
                <div class="rating-overall">
                    <span class="rating-score">4.1</span>
                    <span class="stars">★★★★☆</span>
                    <span class="rating-count">167 reviews</span>
                </div>

                <div class="rating-bars">
                    <div class="rating-row">
                        <span>5</span>
                        <div class="bar">
                            <div class="filled" style="width: 80%;"></div>
                        </div>
                    </div>
                    <div class="rating-row">
                        <span>4</span>
                        <div class="bar">
                            <div class="filled" style="width: 40%;"></div>
                        </div>
                    </div>
                    <div class="rating-row">
                        <span>3</span>
                        <div class="bar">
                            <div class="filled" style="width: 20%;"></div>
                        </div>
                    </div>
                    <div class="rating-row">
                        <span>2</span>
                        <div class="bar">
                            <div class="filled" style="width: 10%;"></div>
                        </div>
                    </div>
                    <div class="rating-row">
                        <span>1</span>
                        <div class="bar">
                            <div class="filled" style="width: 30%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="reviews-summary">
                <h3>Recent reviews</h3>
            </div>
        </div>


        <?php if (!empty($products)): ?>
            <div class="container">
                <div class="listings-title">
                    <h3 class="text-center"><?= htmlspecialchars($first_name) ?>'s Listings</h3>
                </div>
                <div class="scrollable-container">
                    <div class="listing-grid">
                        <?php $isFromProfile = true; ?>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $id = htmlspecialchars($product["id"]);
                            $name = htmlspecialchars($product["name"]);
                            $price = number_format($product["price"], 2);
                            $image = htmlspecialchars($product["img"]);
                            $stock = intval($product["stock"]);
                            $stock_class = $stock > 5 ? 'in-stock' : ($stock > 0 ? 'low-stock' : 'out-of-stock');
                            $from_profile = "user_profile";
                            include "../assets/html/product_card.php";
                            ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p class="text-center">User has no current listings</p>
        <?php endif; ?>
    </div>

    <?php include '../assets/html/footer.php'; ?>
</body>

</html>