<div class="listing-item-container">
    <?php
    $from = '';
    $and = '';
    $source = '';

    if (isset($from_profile_users) && $from_profile_users) {
        $from = '&from=profile_users';
    } elseif (isset($from_profile_listings) && $from_profile_listings) {
        $from = '&from=profile_listing_users';
    }

    if (isset($from_profile) && $from_profile === "my_shop") {
        $and = '&and=my_shop';
    } elseif (isset($isFromProfile) && $isFromProfile) {
        $and = '&and=user_profile';
    } 
    
    // Default placeholder image if the image path is empty or invalid
    if (empty($image)) {
        $image = '../assets/images/placeholder.webp';
    }
    
    // Set source parameter for home page links
    if (isset($source) && $source === 'home') {
        $source = '&source=home';
    }
    ?>
    <a class="listing-item" href="../pages/product.php?id=<?= $id ?><?= $source ?><?= $and ?>">

        <div class="product-image-container">
            <div class="product-image-wrapper">
                <img src="<?= $image ?>" alt="<?= htmlspecialchars($name) ?>" class="product-image" loading="lazy" 
                     onerror="this.src='../assets/images/placeholder.webp';">
            </div>
            <?php if (isset($stock)): ?>
                <div class="stock-badge <?= $stock_class ?>">
                    <?php if ($stock <= 0): ?>
                        Out of Stock
                    <?php elseif ($stock <= 5): ?>
                        Only <?= $stock ?> left!
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="product-info">
            <h4 class="product-title"><?= htmlspecialchars($name) ?></h4>
            <p class="product-price">$<?= number_format($price, 2) ?></p>
        </div>
    </a>
</div>