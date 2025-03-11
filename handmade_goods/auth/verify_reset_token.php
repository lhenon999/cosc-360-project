<?php
if (!isset($_GET['email']) || empty($_GET['email'])) {
    die("Error: Account Error. Please request a new reset link.");
}
$email = urldecode($_GET['email']);
?>

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
        <h1>Enter your reset code</h1>
        <p class="text-muted mb-5">Your code has been sent to your email address</p>
        <div class="login-container">
            <form method="POST" action="reset_password.php" id="tokenValidationForm" novalidate>
                <input type="token" name="token" id="token" placeholder="Reset Code" required>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <span class="error" id="tokenError"></span>
                <button type="submit" name="confirm">Confirm</button>
                <a href="login.php">Back to login</a>
            </form>
        </div>
    </main>

    <script>
        $(document).ready(function () {
            $(".error").text("");

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has("error")) {
                let errorType = urlParams.get("error");
                if (errorType === "invalid_token") {
                    $("#tokenError").text("Invalid token. Please check your email.");
                } else if (errorType === "expired_token") {
                    $("#tokenError").text("This token has expired. Request a new one.");
                }
            }

            $("#tokenValidationForm").submit(function (event) {
                let token = $("#token").val().trim();
                if (token === "") {
                    $("#tokenError").text("Token field cannot be empty.");
                    event.preventDefault();
                }
            });
        });


    </script>
</body>

</html>