<?php
    session_start();
    include '../config.php';

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header("Location: products.php");
        exit();
    }
    $product_id = intval($_GET['id']);
    $from_profile = isset($_GET['from']) && ($_GET['from'] === 'user_profile' || $_GET['from'] === 'my_shop');

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

    $stmt = $conn->prepare("SELECT r.rating, r.comment, u.id AS user_id, u.name, u.profile_picture FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.item_id = ? ORDER BY r.created_at DESC");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $reviews_result = $stmt->get_result();
    $reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <?php include '../assets/html/navbar.php'; ?>

        <main>
            <section class="main mt-5">
                <div class="left">
                    <img src="<?= $image ?>" alt="<?= $name ?>">
                </div>

                <div class="right">
                    <?php if (!empty($category_name)): ?>
                        <a href="products.php?category=<?= rawurlencode($category_name) ?>"
                            class="hover-raise" id="category-btn">
                            <?= $category_name ?>
                        </a>
                    <?php endif; ?>
                    <h1><?= $name ?></h1>
                    <h5 id="price-label">$<?= $price ?></h5>

                    <p class="mt-4"><?= $description ?></p>

                    <?php if (!$from_profile): ?>
                        <a href="user_profile.php?id=<?= $user_id ?>&from_product=product.php?id=<?= $product_id ?>" class="seller-info hover-raise">
                            <img src="<?= htmlspecialchars($seller['profile_picture']) ?>" alt="Seller Profile"
                                class="rounded-circle" width="50" height="50">
                            <p class="ms-3 mb-0">Sold by <strong><?= htmlspecialchars($seller['name']) ?></strong></p>
                        </a>
                    <?php endif; ?>


                    <?php if ($session_user_id !== null && $session_user_id === $user_id): ?>
                        <a href="edit_listing.php?id=<?= $product_id ?>" class="cta hover-raise w-100 mt-5">
                            <span class="material-symbols-outlined">edit</span> Edit Listing
                        </a>
                        <a href="my_shop.php" class="cta-2 mt-3 w-100 hover-raise">Back to My Shop</a>
                    <?php else: ?>
                        <?php if ($product['stock'] > 0): ?>
                            <p class="stock-info <?= $product['stock'] < 5 ? 'low-stock' : '' ?>">
                                <?= $product['stock'] < 5 ? 'Only ' . $product['stock'] . ' left in stock!' : 'In Stock' ?>
                            </p>
                            <form action="/cosc-360-project/handmade_goods/basket/add_to_basket.php" method="POST" class="user-options">
                                <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                <div class="quantity-add w-100">
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
                            class="cta-2 mt-5 w-100 hover-raise">
                            Back to
                            <?= isset($from_profile) && $from_profile ? htmlspecialchars($first_name) . "'s Shop" : 'Products' ?>
                        </a>
                    <?php endif; ?>
                </div>
            </section>

            <section class="reviews">
                <h1 class="mb-4">Customer Reviews</h1>

                <?php if (empty($reviews)): ?>
                    <p class="mb-5">No reviews yet. <?php if (!$from_profile): ?>Be the first to review this product!<?php endif; ?></p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <?php $userProfileLink = ($review['user_id'] == $_SESSION['user_id']) ? "profile.php" : "user_profile.php?id=" . $review['user_id']; ?>
                        <div class="review mt-2 d-flex flex-column">
                            <a href="<?= htmlspecialchars($userProfileLink) ?>" class="review-user">
                                <img src="<?= htmlspecialchars($review['profile_picture']) ?>" alt="Profile" class="review-user-img">
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
                
                <?php if (!$from_profile): ?>
                    <?php if ($session_user_id !== null): ?>
                        <h3 class="mt-5">Add a Review</h3>
                        <form action="add_review.php" method="POST" class="add-review-form" novalidate>
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <textarea placeholder="Tell other buyers about your experience with the product..." name="comment" id="comment" rows="3" required></textarea>
                            <small id="commentError"></small>
                            <div class="d-flex flex-row align-items-center justify-content-start">
                                <span class="rating-label">Rating: </span>
                                <div class="rating-group">
                                    <input type="radio" id="star5" name="rating" value="5"><label for="star5" required>★</label>
                                    <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
                                    <input type="radio" id="star3" name="rating" value="3"><label for="star3">★</label>
                                    <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
                                    <input type="radio" id="star1" name="rating" value="1"><label for="star1">★</label>
                                </div>
                                <small id="ratingError"></small>
                            </div>
                            <button type="submit" class="cta hover-raise w-100"><span class="material-symbols-outlined">check</span>Submit Review</button>
                        </form>
                    <?php else: ?>
                        <p>You must be logged in to leave a review.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <?php include '../assets/html/footer.php'; ?>
        </main>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const form = document.querySelector(".add-review-form");
                const commentField = document.getElementById("comment");
                const ratingFields = document.querySelectorAll('input[name="rating"]');
                const commentError = document.getElementById("commentError");
                const ratingError = document.getElementById("ratingError");

                commentError.classList.add("error-message", "text-danger");
                ratingError.classList.add("error-message", "text-danger");

                form.addEventListener("submit", function (event) {
                    let isValid = true;
                    commentError.textContent = "";
                    ratingError.textContent = "";

                    if (commentField.value.trim().length < 10) {
                        commentError.textContent = "Comment must be at least 10 characters long.";
                        isValid = false;
                    }

                    const selectedRating = document.querySelector('input[name="rating"]:checked');
                    if (!selectedRating) {
                        ratingError.textContent = "Please select a rating.";
                        isValid = false;
                    }

                    if (!isValid) {
                        event.preventDefault();
                    }
                });

                commentField.addEventListener("input", function () {
                    if (commentField.value.trim().length >= 10) {
                        commentError.textContent = "";
                    }
                });

                ratingFields.forEach((radio) => {
                    radio.addEventListener("change", function () {
                        ratingError.textContent = "";
                    });
                });
            });
        </script>
    </body>
</html>