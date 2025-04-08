<?php
session_start();
include __DIR__ . '/../config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);
$from_products = isset($_GET['from_product']) ? $_GET['from_product'] : null;
$from_profile = isset($_GET['and']) && $_GET['and'] === 'user_profile';
$from_listings = isset($_GET['from']) && $_GET['from'] === 'profile_listings';
$from_listing_users = isset($_GET['from']) && $_GET['from'] === 'profile_listing_users';
$from_users = isset($_GET['from']) && $_GET['from'] === 'profile_users';
$from_home = isset($_GET['source']) && $_GET['source'] === 'home';
$from_my_shop = isset($_GET['and']) && $_GET['and'] === 'my_shop';

$stmt = $conn->prepare("SELECT id, name, description, price, img, user_id, category, stock FROM ITEMS WHERE id = ?");
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
$image = !empty($product['img']) ? htmlspecialchars($product['img']) : "../assets/images/placeholder.webp";
$user_id = intval($product['user_id']);
$session_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$category_name = isset($product['category']) ? htmlspecialchars($product['category']) : null;
$created_at = date("F j, Y", strtotime($product['created_at']));


$stmt = $conn->prepare("SELECT name, profile_picture FROM USERS WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$seller = $result->fetch_assoc();
$stmt->close();

$first_name = isset($seller['name']) ? explode(' ', trim($seller['name']))[0] : 'Seller';
$sellerProfileUrl = "user_profile.php?id=" . $user_id . "&from_product=product.php?id=" . $product_id;

$stmt = $conn->prepare("SELECT r.rating, r.comment, u.id AS user_id, u.name, u.profile_picture 
                        FROM REVIEWS r 
                        JOIN USERS u ON r.user_id = u.id 
                        WHERE r.item_id = ? 
                        ORDER BY r.created_at DESC");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$userHasReviewed = false;
$hasPurchased = false;

if ($session_user_id !== null) {
    // Check if user purchased this product
    $stmt = $conn->prepare("SELECT COUNT(*) FROM SALES WHERE buyer_id = ? AND item_id = ?");
    $stmt->bind_param("ii", $session_user_id, $product_id);
    $stmt->execute();
    $stmt->bind_result($purchaseCount);
    $stmt->fetch();
    $stmt->close();
    $hasPurchased = ($purchaseCount > 0);

    // Check if user has reviewed this product
    $stmt = $conn->prepare("SELECT COUNT(*) FROM REVIEWS WHERE user_id = ? AND item_id = ?");
    $stmt->bind_param("ii", $session_user_id, $product_id);
    $stmt->execute();
    $stmt->bind_result($reviewCount);
    $stmt->fetch();
    $stmt->close();
    $userHasReviewed = ($reviewCount > 0);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Handmade Goods - <?= $name ?></title>

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
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

    <main>
        <section class="main mt-5">
            <div class="left">
                <img src="<?= $image ?>" alt="<?= $name ?>">
            </div>
            <div class="right">
                <div aria-label="breadcrumb" class="breadcrumb-nav">
                    <ol class="breadcrumb">
                        <?php if ($from_products): ?>
                            <li class="breadcrumb-item">
                                <a href="products.php">Products</a>
                            </li>
                        <?php elseif ($from_my_shop): ?>
                            <li class="breadcrumb-item">
                                <a href="my_shop.php">My Shop</a>
                            </li>
                        <?php elseif ($from_profile): ?>
                            <li class="breadcrumb-item">
                                <a href="user_profile.php?id=<?= $user_id ?>">Profile</a>
                            </li>
                        <?php elseif ($from_listings || $from_listing_users || $from_users): ?>
                            <li class="breadcrumb-item">
                                <a href="profile.php#listings">Dashboard</a>
                            </li>
                        <?php elseif ($from_home): ?>
                            <li class="breadcrumb-item">
                                <a href="home.php?id=<?= $user_id ?>">Home</a>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item">
                                <a href="products.php">Products</a>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($category_name)): ?>
                            <li class="breadcrumb-item">
                                <a href="products.php?category=<?= rawurlencode($category_name) ?>">
                                    <?= $category_name ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="breadcrumb-item active" aria-current="page"><?= $name ?></li>
                    </ol>
                        </div>


                <h1><?= $name ?></h1>
                <p class="created-at">Listed on: <?= $created_at ?></p>
                <h5 id="price-label">$<?= $price ?></h5>

                <p class="mt-4"><?= $description ?></p>

                <?php if (!$from_profile): ?>
                    <a href="user_profile.php?id=<?= $user_id ?>&from_product=product.php?id=<?= $product_id ?>"
                        class="seller-info hover-raise">
                        <img src="<?= htmlspecialchars($seller['profile_picture']) ?>" alt="Seller Profile"
                            class="rounded-circle" width="50" height="50">
                        <p class="ms-3 mb-0">
                            Sold by <strong><?= htmlspecialchars($seller['name']) ?></strong>
                        </p>
                    </a>
                <?php endif; ?>

                <?php if ($session_user_id !== null && $session_user_id === $user_id): ?>
                    <a href="edit_listing.php?id=<?= $product_id ?>" class="m-btn g atc">
                        <span class="material-symbols-outlined">edit</span> Edit Listing
                    </a>
                <?php else: ?>
                    <?php if ($product['stock'] > 0): ?>
                        <p class="stock-info <?= $product['stock'] < 5 ? 'low-stock' : '' ?>">
                            <?= $product['stock'] < 5
                                ? 'Only ' . $product['stock'] . ' left in stock!'
                                : 'In Stock' ?>
                        </p>
                        <form action="/cosc-360-project/handmade_goods/basket/add_to_basket.php" method="POST"
                            class="user-options">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                                <a href="profile.php?item=<?= urlencode($name) ?>" class="m-btn g atc">
                                    <span class="material-symbols-outlined">manage_accounts</span> Manage Listing
                                </a>
                            <?php else: ?>
                                <div class="quantity-add w-100">
                                    <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>"
                                        class="form-control quantity-input">
                                    <button type="submit" class="m-btn hover-raise g atc <?php echo !isset($_SESSION["user_id"]) ? 'not-logged-in' : ''; ?>">
                                        <span class="material-symbols-outlined">add_shopping_cart</span> Add to Basket
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <p class="out-of-stock">Out of Stock</p>
                        <button class="m-btn atc" disabled>
                            <span class="material-symbols-outlined">add_shopping_cart</span> Out of Stock
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
        <section class="reviews">
            <h1 class="mb-4">Customer Reviews</h1>
            <?php if (empty($reviews)): ?>
                <p class="mb-5">No reviews yet. Be the first to review this product!</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review mt-2 d-flex flex-column">
                        <a href="user_profile.php?id=<?= $review['user_id'] ?>" class="review-user">
                            <img src="<?= htmlspecialchars($review['profile_picture']) ?>" alt="Profile"
                                class="review-user-img">
                            <strong><?= htmlspecialchars($review['name']) ?></strong>
                        </a>
                        <div class="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= $review['rating'] ? 'filled' : '' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <p class="review-comment"><?= htmlspecialchars($review['comment']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($session_user_id !== null): ?>
                <?php if ($hasPurchased): ?>
                    <?php if (!$userHasReviewed): ?>
                        <h3 class="mt-5">Add a Review</h3>
                        <form action="add_review.php" method="POST" class="add-review-form">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <textarea placeholder="Tell other buyers about your experience with the product..." name="comment"
                                id="comment" rows="3" required></textarea>
                            <div class="d-flex flex-row align-items-center justify-content-start">
                                <span class="rating-label">Rating: </span>
                                <div class="rating-group">
                                    <input type="radio" id="star5" name="rating" value="5">
                                    <label for="star5">★</label>
                                    <input type="radio" id="star4" name="rating" value="4">
                                    <label for="star4">★</label>
                                    <input type="radio" id="star3" name="rating" value="3">
                                    <label for="star3">★</label>
                                    <input type="radio" id="star2" name="rating" value="2">
                                    <label for="star2">★</label>
                                    <input type="radio" id="star1" name="rating" value="1">
                                    <label for="star1">★</label>
                                </div>
                            </div>
                            <button type="submit" class="m-btn w-40">
                                <span class="material-symbols-outlined">check</span>Submit Review
                            </button>
                        </form>
                    <?php else: ?>
                        <p>You can only review the product once.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>You can only leave a review if you've purchased this product.</p>
                <?php endif; ?>
            <?php else: ?>
                <p>You must be logged in to leave a review.</p>
            <?php endif; ?>
        </section>
    </main>
    <script src="../assets/js/product_reviews.js"></script>
    <?php include __DIR__ . '/../assets/html/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const comment = document.getElementById('comment');
            const ratingInputs = document.querySelectorAll('input[name="rating"]');
            const submitButton = document.querySelector('.add-review-form button[type="submit"]');

            submitButton.disabled = true;

            function checkFormValidity() {
                const commentFilled = comment.value.trim() !== '';
                let ratingSelected = false;
                ratingInputs.forEach(input => {
                    if (input.checked) {
                        ratingSelected = true;
                    }
                });
                submitButton.disabled = !(commentFilled && ratingSelected);
            }

            comment.addEventListener('input', checkFormValidity);

            ratingInputs.forEach(input => {
                input.addEventListener('change', checkFormValidity);
            });
        });
    </script>

</body>

</html>