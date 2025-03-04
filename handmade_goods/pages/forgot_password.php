<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');
    </style>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/form.css">
</head>

<body>
    <?php include '../assets/html/navbar.php'; ?>
    <main class="container text-center">
        <h1>Confirm your email address</h1>
        <div class="login-container">
            <?php
            if (isset($_GET["error"])) {
                echo '<p class="error">';
                if ($_GET["error"] == "nouser") {
                    echo "No user found with that email.";
                }
                echo '</p>';
            }
            ?>
            <form method="POST" action="../password_reset.php" id="emailValidationForm" novalidate>
                <input type="email" name="email" id="email" placeholder="Email" required>
                <span class="error" id="emailError"></span>

                <button type="submit" name="login">Confirm</button>
                <br>
                <a href="login.php">Back to login</a>
            </form>
        </div>
    </main>

    <script>
        $(document).ready(function () {
            $("#emailValidationForm").submit(function (event) {
                $(".error").text("");
                let isValid = true;

                let email = $("#email").val().trim();
                let emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                if (!emailRegex.test(email)) {
                    $("#emailError").text("Enter a valid email address");
                    isValid = false;
                }
                if (!isValid) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>

</html>