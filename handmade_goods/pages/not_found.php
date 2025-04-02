<?php 
session_start();
include __DIR__ . '/../config.php';
http_response_code(404);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Handmade Goods - Page Not Found</title>

        <style>@import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');</style>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="../assets/css/globals.css">
        <link rel="stylesheet" href="../assets/css/navbar.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/home.css">
    </head>

    <body>
        <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

        <div class="container text-center d-flex flex-column justify-content-center align-items-center mt-5">
            <h1 class="mt-5">Uh Oh!</h1>
            <p class="text-muted mb-5">The page you requested could not be found.</p>
            <a href="./home.php" class="hover-raise cta mt-5">Go Home</a>
        </div>
    </body>

</html>