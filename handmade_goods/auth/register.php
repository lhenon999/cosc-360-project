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
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/form.css">

    <title>Handmade Goods - Register</title>
</head>

<body>
    <?php include '../assets/html/navbar.php'; ?>
    <main class="container text-center">
        <h1 class="mb-5">Create an Account</h1>
        <div class="login-container">
            <?php
            if (isset($_GET["error"])) {
                echo '<p class="error-message">';
                switch ($_GET["error"]) {
                    case "email_taken":
                        echo "This email is already registered.";
                        break;
                    case "registration_failed":
                        echo "Registration failed. Please try again.";
                        break;
                    case "invalid_file":
                        echo "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF.";
                        break;
                    case "file_upload_failed":
                        echo "File upload failed. Check file permissions.";
                        break;
                }
                echo '</p>';
            }
            ?>
            <form method="POST" action="db.php" id="registerForm" enctype="multipart/form-data" novalidate>
                <input type="text" name="full_name" id="full_name" placeholder="Full Name" required>
                <span class="error" id="nameError"></span>

                <input type="email" name="email" id="email" placeholder="Email" required>
                <span class="error" id="emailError"></span>

                <input type="password" name="password" id="password" placeholder="Password" required>
                <span class="error" id="passwordError"></span>

                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password"
                    required>
                <span class="error" id="confirmPasswordError"></span>

                <label for="profile_picture">Profile Picture (Optional)</label>
                <input type="file" name="profile_picture" id="profile_picture" accept="image/*">
                <span class="error" id="profilePictureError"></span>

                <button type="submit" name="register">Sign Up</button>

                <a href="login.php">Already have an account? Log in</a>
            </form>
        </div>
    </main>

    <script>
        $(document).ready(function () {
            const urlParams = new URLSearchParams(window.location.search);
        
            if (urlParams.get("error") === "email_taken") {
                $("#emailError").text("This email is already registered. Please use another one.");
            }
            
            $("#registerForm").submit(function (event) {
                $(".error").text("");
                let isValid = true;

                let fullName = $("#full_name").val().trim();
                if (!/^\w+\s+\w+/.test(fullName)) {
                    $("#nameError").text("Enter a first and last name");
                    isValid = false;
                }

                let email = $("#email").val().trim();
                let emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                if (!emailRegex.test(email)) {
                    $("#emailError").text("Enter a valid email address");
                    isValid = false;
                }

                let password = $("#password").val();
                let passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                if (!passwordRegex.test(password)) {
                    $("#passwordError").text("Password must have at least 8 characters, 1 uppercase, 1 lowercase, 1 number, and 1 special character");
                    isValid = false;
                }

                let confirmPassword = $("#confirm_password").val();
                if (password !== confirmPassword) {
                    $("#confirmPasswordError").text("Passwords do not match");
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