<link rel="stylesheet" href="../../assets/css/product_card.css">

<div class="listing-item-container">
    <a class="listing-item" href="../pages/product.php?id=<?= $id ?><?= isset($isFromProfile) && $isFromProfile ? '&from=user_profile' : '' ?>">
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


