<link rel="stylesheet" href="../../assets/css/product_card.css">

<a class="listing-item-container" href="../pages/product.php?id=<?= $id ?><?= isset($isFromProfile) && $isFromProfile ? '&from=user_profile' : '' ?>">
    <div class="listing-item">
        <img src="<?= $image ?>" alt="<?= htmlspecialchars($name) ?>" class="product-image">
    </div>
    <div class="product-info">
        <h4 class="product-title"><?= htmlspecialchars($name) ?></h4>
        <p class="product-price">$<?= number_format($price, 2) ?></p>
    </div>
</a>


