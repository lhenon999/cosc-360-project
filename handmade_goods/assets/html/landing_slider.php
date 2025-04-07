<?php 
try {
    // Modified queries to exclude products from frozen accounts
    $slides = [
        ["Trending Now", "SELECT i.id, i.name, i.description, i.img FROM ORDER_ITEMS AS oi JOIN ORDERS AS o ON o.id = oi.order_id JOIN ITEMS AS i ON i.id = oi.item_id JOIN USERS AS u ON i.user_id = u.id WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY) AND u.is_frozen = 0 GROUP BY i.id ORDER BY sum(oi.quantity) DESC LIMIT 1;"],
        ["Hot in Apparel", "SELECT i.id, i.name, i.description, i.img FROM ORDER_ITEMS AS oi JOIN ITEMS AS i ON i.id = oi.item_id JOIN USERS AS u ON i.user_id = u.id WHERE i.category = 'Clothing' AND u.is_frozen = 0 GROUP BY i.id ORDER BY SUM(oi.quantity) DESC LIMIT 1;"],
        ["New Arrivals", "SELECT i.id, i.name, i.description, i.img from ITEMS i JOIN USERS u ON i.user_id = u.id WHERE u.is_frozen = 0 ORDER BY i.created_at desc LIMIT 1;"],
        ["Best Selling Kitchenware", "SELECT i.id, i.name, i.description, i.img FROM ORDER_ITEMS AS oi JOIN ITEMS AS i ON i.id = oi.item_id JOIN USERS AS u ON i.user_id = u.id WHERE i.category = 'Kitchenware' AND u.is_frozen = 0 GROUP BY i.id ORDER BY SUM(oi.quantity) DESC LIMIT 1;"],
        ["Selling Out Soon", "SELECT i.id, i.name, i.description, i.img FROM ITEMS i JOIN USERS u ON i.user_id = u.id WHERE u.is_frozen = 0 ORDER BY i.stock LIMIT 1;"]
    ];

    $sliderData = [];
    foreach ($slides as $slideInfo) {
        $title = $slideInfo[0];
        $query = $slideInfo[1];
    
        try {
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
        
            while ($row = $result->fetch_assoc()) {
                $sliderData[] = [
                    'title' => $title,
                    'id'    => $row['id'],
                    'name'  => $row['name'],
                    'desc'  => $row['description'],
                    'img'   => $row['img']
                ];
            }
            $stmt->close();
        } catch (Exception $e) {
            echo "<!-- Error in slide query '" . htmlspecialchars($title) . "': " . htmlspecialchars($e->getMessage()) . " -->";
        }
    }

    // If no slides were found, add a default one
    if (empty($sliderData)) {
        $sliderData[] = [
            'title' => 'Welcome to Handmade Goods',
            'id'    => 1,
            'name'  => 'Explore Our Collection',
            'desc'  => 'Discover unique handcrafted items made with care and passion.',
            'img'   => '../assets/images/default.webp'
        ];
    }
} catch (Exception $e) {
    echo "<!-- Error in slider: " . htmlspecialchars($e->getMessage()) . " -->";
    // Provide fallback data
    $sliderData = [[
        'title' => 'Welcome to Handmade Goods',
        'id'    => 1,
        'name'  => 'Explore Our Collection',
        'desc'  => 'Discover unique handcrafted items made with care and passion.',
        'img'   => '../assets/images/default.webp'
    ]];
}
?>

<div class="slider">
    <?php foreach ($sliderData as $index => $slide): ?>
        <div class="slide">
            <div class="left">
                <h5><?php echo htmlspecialchars($slide['title']); ?></h5>
                <h1><?php echo htmlspecialchars($slide['name']); ?></h1>
                <p><?php echo htmlspecialchars($slide['desc']); ?></p>
                <a class="view-button" href="../pages/product.php?id=<?= $slide['id'] ?><?= isset($isFromProfile) && $isFromProfile ? '&from=user_profile' : '' ?>&source=home">View Now</a>
            </div>

            <div class="right">
                <img src="<?php echo htmlspecialchars($slide['img']); ?>"
                    alt="<?php echo htmlspecialchars($slide['name']); ?>">
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    const slides = document.querySelectorAll('.slide');
    let currentSlide = 0;

    function showSlide(index) {
        slides.forEach(slide => {
            slide.style.display = 'none';
        });
        slides[index].style.display = 'flex';
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }

    showSlide(currentSlide);
    setInterval(nextSlide, 4500);
</script>