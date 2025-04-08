<?php
session_start();
include __DIR__ . '/../config.php';

$is_logged_in = isset($_SESSION["user_id"]);

// Get filter parameters with proper sanitization
$category_filter = isset($_GET['category']) && $_GET['category'] !== '' ? trim($_GET['category']) : null;
$price_from = isset($_GET['price-from']) && $_GET['price-from'] !== '' ? floatval($_GET['price-from']) : null;
$price_to = isset($_GET['price-to']) && $_GET['price-to'] !== '' ? floatval($_GET['price-to']) : null;
$search = isset($_GET['search']) && $_GET['search'] !== '' ? trim($_GET['search']) : null;
$rating_filter = isset($_GET['rating']) && $_GET['rating'] !== '' ? intval($_GET['rating']) : null;

//auto suggestions
if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    header('Content-Type: application/json');
    $search_param = "%" . $search . "%";
    $results = ["products" => [], "users" => [], "categories" => []];

    // for products
    $query = "SELECT id, name, img, user_id FROM ITEMS WHERE name LIKE ? LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $results["products"] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // for users
    $query = "SELECT id, name, profile_picture FROM USERS WHERE (name LIKE ? OR email LIKE ?) AND user_type != 'admin' LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $results["users"] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // for categories
    $query = "SELECT DISTINCT category FROM ITEMS WHERE category LIKE ? LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $results["categories"] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode($results);
    exit();
}

// Build the base query
$query = "SELECT DISTINCT i.id, i.name, i.img, i.user_id, i.price, i.stock, IFNULL(AVG(r.rating), 0) as avg_rating 
        FROM ITEMS i 
        LEFT JOIN REVIEWS r ON i.id = r.item_id";

// Start WHERE clause
$where_conditions = [];
$params = [];
$types = "";

// Add category filter with exact matching
if ($category_filter !== null && trim($category_filter) !== '') {
    $where_conditions[] = "i.category = ?";
    $params[] = trim($category_filter);
    $types .= "s";
    error_log("Filtering by category: " . $category_filter);
}

// Add price filters
if ($price_from !== null) {
    $where_conditions[] = "i.price >= ?";
    $params[] = $price_from;
    $types .= "d";
}

if ($price_to !== null) {
    $where_conditions[] = "i.price <= ?";
    $params[] = $price_to;
    $types .= "d";
}

// Add search filter
if ($search !== null) {
    $where_conditions[] = "(i.name LIKE ? OR i.description LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// Combine WHERE conditions if any exist
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

// Add GROUP BY
$query .= " GROUP BY i.id";

// Add rating filter as HAVING clause
if ($rating_filter !== null) {
    $query .= " HAVING avg_rating >= ? AND avg_rating < ?";
    $params[] = $rating_filter;
    $params[] = $rating_filter + 1;
    $types .= "dd";
}

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all categories for the filter sidebar
$categories = [];
$cat_stmt = $conn->prepare("SELECT DISTINCT category FROM ITEMS WHERE category IS NOT NULL ORDER BY category");
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row['category'];
}
$cat_stmt->close();

// Get available ratings
$ratings = [];
$rating_stmt = $conn->prepare("SELECT DISTINCT rating FROM REVIEWS ORDER BY rating");
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result();
while ($row = $rating_result->fetch_assoc()) {
    $ratings[] = $row['rating'];
}
$rating_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handmade Goods - Browse</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');
    </style>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/products.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/product_card.css">
</head>

<body>
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

    <h1 class="text-center">Explore Our Products!</h1>
    <p class="text-center">Browse our collection and discover what suits you</p>

    <main class="mt-5">
        <div class="mobile-only" id="toggle-filters">
            <h5>Filters <span class="material-symbols-outlined">keyboard_arrow_down</span></h5>
        </div>
        <div class="sidebar">
            <form action="products.php" method="GET" id="filter-form" class="filter-form">
                <?php if ($search): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>

                <div class="filter-section">
                    <h4>Categories</h4>
                    <div class="category-options">
                        <label class="category-label">
                            <input type="radio" name="category" value="" <?= (!$category_filter) ? 'checked' : '' ?>>
                            All
                        </label>
                        <?php foreach ($categories as $cat): ?>
                            <label class="category-label">
                                <input type="radio" name="category" value="<?= htmlspecialchars($cat) ?>"
                                    <?= ($category_filter === $cat) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <h4>Price Range</h4>
                    <label class="price-label">
                        <span>Min $</span>
                        <input type="number" name="price-from" id="price-from" placeholder="Min Price" min="0"
                            value="<?= htmlspecialchars($price_from ?? '') ?>">
                    </label>

                    <label class="price-label">
                        <span>Max $</span>
                        <input type="number" name="price-to" id="price-to" placeholder="Max Price" min="0"
                            value="<?= htmlspecialchars($price_to ?? '') ?>">
                    </label>

                </div>

                <div class="filter-section">
                    <h4>Average Rating</h4>
                    <label><input type="radio" name="rating" value="5" <?= ($rating_filter === 5) ? 'checked' : '' ?>>
                        ★ ★ ★ ★ ★</label>
                    <label><input type="radio" name="rating" value="4" <?= ($rating_filter === 4) ? 'checked' : '' ?>>
                        ★ ★ ★ ★</label>
                    <label><input type="radio" name="rating" value="3" <?= ($rating_filter === 3) ? 'checked' : '' ?>>
                        ★ ★ ★</label>
                    <label><input type="radio" name="rating" value="2" <?= ($rating_filter === 2) ? 'checked' : '' ?>>
                        ★ ★</label>
                    <label><input type="radio" name="rating" value="1" <?= ($rating_filter === 1) ? 'checked' : '' ?>>
                        ★</label>
                </div>

                <button type="button" class="m-btn clear-filters" onclick="window.location.href='products.php'">Clear Filters</button>
            </form>
        </div>

        <div class="listing-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    $id = htmlspecialchars($product["id"]);
                    $name = htmlspecialchars($product["name"]);
                    $price = number_format($product["price"], 2);
                    $image = htmlspecialchars($product["img"]);
                    $stock = intval($product["stock"]);
                    $stock_class = $stock > 5 ? 'in-stock' : ($stock > 0 ? 'low-stock' : 'out-of-stock');
                    include "../assets/html/product_card.php";
                    ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div
                    class="no-results text-center w-100 d-flex flex-column justify-content-center align-items-center h-100">
                    <p>No products found matching your criteria. Try adjusting search or filter parameters!</p>
                    <a href="products.php" class="cta hover-raise mt-5 clear-filters">Clear Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/../assets/html/footer.php'; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
        const toggleBtn = document.getElementById("toggle-filters");
        const sidebar = document.querySelector(".sidebar");

        toggleBtn.addEventListener("click", function () {
            if (window.innerWidth <= 680) {
                sidebar.classList.toggle("show");

                const icon = this.querySelector(".material-symbols-outlined");
                icon.textContent = sidebar.classList.contains("show") ? "keyboard_arrow_up" : "keyboard_arrow_down";
            }
        });
    });

        // validation for price filters
        const priceFrom = document.getElementById('price-from');
        const priceTo = document.getElementById('price-to');

        priceFrom.addEventListener('input', function () {
            const minValue = this.value.trim() !== '' ? Number(this.value) : 0;
            priceTo.min = minValue;

            if (priceTo.value && Number(priceTo.value) < minValue) {
                priceTo.value = minValue;
            }
        });
        priceTo.addEventListener('input', function () {
            const currentMin = priceTo.min ? Number(priceTo.min) : 0;
            if (this.value && Number(this.value) < currentMin) {
                this.value = currentMin;
            }
        });

        // Add client-side validation and handling
        document.getElementById('filter-form').addEventListener('submit', function (e) {
            // Clear empty values before submission
            const inputs = this.querySelectorAll('input[type="number"], input[type="text"]');
            inputs.forEach(input => {
                if (input.value.trim() === '') {
                    input.disabled = true;
                }
            });
        });

        // Add automatic submission for all filter types
        document.querySelectorAll('input[name="category"], input[name="rating"]').forEach(input => {
            input.addEventListener('change', function () {
                if (this.checked) {
                    document.getElementById('filter-form').submit();
                }
            });
        });

        // Add automatic submission for price range with debounce
        let timeout;
        document.querySelectorAll('input[name="price-from"], input[name="price-to"]').forEach(input => {
            input.addEventListener('input', function () {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    document.getElementById('filter-form').submit();
                }, 1000);
            });
        });
    </script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const hasCategory = urlParams.get('category') && urlParams.get('category').trim() !== '';
        const hasPriceFrom = urlParams.get('price-from') && urlParams.get('price-from').trim() !== '';
        const hasPriceTo = urlParams.get('price-to') && urlParams.get('price-to').trim() !== '';
        const hasSearch = urlParams.get('search') && urlParams.get('search').trim() !== '';
        const hasRating = urlParams.get('rating') && urlParams.get('rating').trim() !== '';

        const clearBtn = document.querySelector('.clear-filters');

        if (!(hasCategory || hasPriceFrom || hasPriceTo || hasSearch || hasRating)) {
            clearBtn.disabled = true;
        } else {
            clearBtn.disabled = false;
        }


    </script>
</body>