<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/form_styles.css">
    <title>Register</title>
</head>
<body>

    <div class="login-container">
        <h2>Create an Account</h2>
        <form method="POST" action="../db.php">
            <input type="text" name="full_name" id="full_name" placeholder="Full Name" required>
            <input type="email" name="email" id="email" placeholder="Email" required>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>

            <button type="submit" name="register">Sign Up</button>

            <a href="login.php">Already have an account? Log in</a>
        </form>
    </div>

</body>
</html>
