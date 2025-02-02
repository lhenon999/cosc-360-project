<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/form_styles.css">
    <title>Login</title>
</head>
<body>
    <div class="login-container">
        <h2>Welcome Back</h2>
        <form method="POST" action="../db.php">
            <input type="email" name="email" id="email" placeholder="Email" required>
            <input type="password" name="password" id="password" placeholder="Password" required>

            <button type="submit" name="login">Log In</button>

            <a href="register.php">Don't have an account? Sign up</a>
        </form>
    </div>

</body>
</html>
