ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

<div class="listing-item-container">
    <?php
    $from = '';
    $and = '';

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
    
    if (isset($source) && $source === 'home') {
        $source = '&source=home';
    }
    ?>
    <a class="listing-item" href="../pages/product.php?id=<?= $id ?><?= $source ?><?= $and ?>">

        <div class="product-image-container">
            <img src="<?= $image ?>" alt="<?= htmlspecialchars($name) ?>" class="product-image">
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