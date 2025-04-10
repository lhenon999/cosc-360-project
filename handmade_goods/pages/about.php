<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    session_start();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Handmade Goods - About</title>

        <style>@import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');</style>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="../assets/css/globals.css">
        <link rel="stylesheet" href="../assets/css/navbar.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/about.css">
    </head>
    <body>
        <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

        <div class="container mt-5">
            <h1 class="mb-2">About Us</h1>
            <p class="mb-5">Learn about our values and what drives us.</p>
            
            <div class="row align-items-center justify-content-center">
                <div class="col-md-6">
                    <p class="mb-4">
                        Welcome to Handmade Goods, where artistry meets authenticity. Our platform serves as a vibrant marketplace connecting talented local artisans with those who appreciate the beauty and quality of handcrafted items. Each piece in our collection tells a unique story, crafted with dedication and skill by makers who pour their heart into their work.
                    </p>
                    <p class="mb-4">
                        We believe in supporting local craftsmanship and preserving traditional techniques while embracing modern creativity. Our mission is to provide a space where artisans can thrive and customers can discover one-of-a-kind pieces that bring beauty and character to their lives.
                    </p>
                </div>
                <div class="col-md-6">
                    <img src="/~rsodhi03/cosc-360-project/handmade_goods/assets/images/about-craft.jpg" alt="Handcrafted wooden utensils" class="rounded shadow">
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-md-4">
                    <div class="feature-box hover-raise text-center p-4">
                        <span class="material-symbols-outlined feature-icon">handshake</span>
                        <h3 class="mt-3">Supporting Artisans</h3>
                        <p>We provide a platform for local craftspeople to showcase and sell their unique creations.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box hover-raise text-center p-4">
                        <span class="material-symbols-outlined feature-icon">verified</span>
                        <h3 class="mt-3">Quality Assured</h3>
                        <p>Each item is carefully reviewed to ensure it meets our high standards of craftsmanship.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box hover-raise text-center p-4">
                        <span class="material-symbols-outlined feature-icon">eco</span>
                        <h3 class="mt-3">Sustainable Practices</h3>
                        <p>We encourage eco-friendly materials and sustainable production methods.</p>
                    </div>
                </div>
            </div>
        </div>

        <?php include __DIR__ . '/../assets/html/footer.php'; ?>
    </body>
</html> 