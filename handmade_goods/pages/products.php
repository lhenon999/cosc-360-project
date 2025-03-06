<?php
session_start();
$is_logged_in = isset($_SESSION["user_id"]);
include '../config.php';

// Get filter parameters with proper sanitization
$category_filter = isset($_GET['category']) && $_GET['category'] !== '' ? trim($_GET['category']) : null;
$price_from = isset($_GET['price-from']) && $_GET['price-from'] !== '' ? floatval($_GET['price-from']) : null;
$price_to = isset($_GET['price-to']) && $_GET['price-to'] !== '' ? floatval($_GET['price-to']) : null;
$search = isset($_GET['search']) && $_GET['search'] !== '' ? trim($_GET['search']) : null;
$rating_filter = isset($_GET['rating']) && $_GET['rating'] !== '' ? intval($_GET['rating']) : null;

// Debug URL parameters
error_log("URL Parameters: " . json_encode($_GET));
error_log("Category Filter Value (raw): " . $category_filter);
error_log("Category Filter Value (trimmed): " . trim($category_filter));

// Add debug for GET parameters
echo "<!-- Debug: GET parameters = " . json_encode($_GET) . " -->\n";

// Build the base query
$query = "SELECT DISTINCT i.*, IFNULL(AVG(r.rating), 0) as avg_rating 
          FROM items i 
          LEFT JOIN reviews r ON i.id = r.item_id";

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

// Debug output
error_log("Category Filter: " . ($category_filter ?? 'null'));
error_log("Final SQL Query: " . $query);
error_log("Parameters: " . print_r($params, true));

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
$cat_stmt = $conn->prepare("SELECT DISTINCT category FROM items WHERE category IS NOT NULL ORDER BY category");
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row['category'];
    echo "<!-- Debug: Available category: " . htmlspecialchars($row['category']) . " -->\n";
}
$cat_stmt->close();

// Get available ratings
$ratings = [];
$rating_stmt = $conn->prepare("SELECT DISTINCT rating FROM reviews ORDER BY rating");
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

        <style>@import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');</style>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="../assets/css/globals.css">
        <link rel="stylesheet" href="../assets/css/products.css">
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
                    <form action="products.php" method="GET" id="filter-form" class="filter-form">
                        <?php if($search): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                        
                        <div class="filter-section">
                            <h4>Categories</h4>
                            <div class="category-options">
                                <label class="category-label">
                                    <input type="radio" name="category" value="" 
                                        <?= (!$category_filter) ? 'checked' : '' ?>> 
                                    All Categories
                                </label><br>
                                <?php foreach($categories as $cat): ?>
                                    <label class="category-label">
                                        <input type="radio" name="category" value="<?= htmlspecialchars($cat) ?>" 
                                            <?= ($category_filter === $cat) ? 'checked' : '' ?>>
                                        <?= htmlspecialchars($cat) ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="filter-section">
                            <h4>Price Range</h4>
                            <label>From: 
                                <input type="number" name="price-from" min="0" step="0.01" 
                                    value="<?= $price_from !== null ? htmlspecialchars($price_from) : '' ?>"> 
                            </label><br>
                            <label>To: 
                                <input type="number" name="price-to" min="0" step="0.01" 
                                    value="<?= $price_to !== null ? htmlspecialchars($price_to) : '' ?>"> 
                            </label><br>
                        </div>

                        <div class="filter-section">
                            <h4>Ratings</h4>
                            <label><input type="radio" name="rating" value="1" <?= ($rating_filter === 1) ? 'checked' : '' ?>> 1+ Star</label><br>
                            <label><input type="radio" name="rating" value="2" <?= ($rating_filter === 2) ? 'checked' : '' ?>> 2+ Stars</label><br>
                            <label><input type="radio" name="rating" value="3" <?= ($rating_filter === 3) ? 'checked' : '' ?>> 3+ Stars</label><br>
                            <label><input type="radio" name="rating" value="4" <?= ($rating_filter === 4) ? 'checked' : '' ?>> 4+ Stars</label><br>
                            <label><input type="radio" name="rating" value="5" <?= ($rating_filter === 5) ? 'checked' : '' ?>> 5 Stars</label><br>
                        </div>

                        <a href="products.php" class="btn btn-secondary w-100">Clear Filters</a>
                    </form>
                </div>
            </div>
            <div class="scrollable-container">
                <!-- <div class="container">  -->
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
                            <div class="no-results text-center">
                                <p>No products found matching your criteria</p>
                                <a href="products.php" class="cta hover-raise">Clear Filters</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="spacer"></div>
                <!-- </div> -->
            </div>
        </div>
        <?php include '../assets/html/footer.php'; ?>

        <script>
            // Add client-side validation and handling
            document.getElementById('filter-form').addEventListener('submit', function(e) {
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
                input.addEventListener('change', function() {
                    if (this.checked) {
                        document.getElementById('filter-form').submit();
                    }
                });
            });

            // Add automatic submission for price range with debounce
            let timeout;
            document.querySelectorAll('input[name="price-from"], input[name="price-to"]').forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        if (this.value.trim() !== '') {
                            document.getElementById('filter-form').submit();
                        }
                    }, 1000); // Wait 1 second after user stops typing
                });
            });
        </script>
    </body>
</html>