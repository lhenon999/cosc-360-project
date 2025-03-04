<?php
session_start();
require '../config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = trim($_POST['token']);

    if (empty($token)) {
        header("Location: verify_reset_token.php?error=invalid_token");
        exit();
    }

    $stmt = $conn->prepare("SELECT email, expires FROM password_resets WHERE token = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        header("Location: verify_reset_token.php?error=invalid_token");
        exit();
    }

    if (strtotime($row['expires']) < time()) {
        header("Location: verify_reset_token.php?error=expired_token");
        exit();
    }

    $_SESSION['reset_email'] = $row['email'];
    $_SESSION['reset_token'] = $token;
} else {
    if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_token'])) {
        header("Location: forgot_password.php?error=session_expired");
        exit();
    }
}
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
        <h1>Reset Your Password</h1>
        <div class="login-container">
            <form method="POST" action="process_reset.php" id="updatePasswordForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['reset_token']); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['reset_email']); ?>">

                <input type="password" name="new_password" id="new_password" placeholder="New Password" required>
                <span class="error" id="passwordError"></span>

                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                <span class="error" id="confirmPasswordError"></span>

                <button type="submit">Reset Password</button>
            </form>
        </div>
    </main>
    <script>
        $(document).ready(function () {
            $("#updatePasswordForm").submit(function (event) {
                $(".error").text("");
                let isValid = true;

                let password = $("#new_password").val();
                let passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                if (!passwordRegex.test(password)) {
                    $("#passwordError").text("Password must have at least 8 characters, 1 uppercase, 1 lowercase, 1 number, and 1 special character.");
                    isValid = false;
                }

                let confirmPassword = $("#confirm_password").val();
                if (password !== confirmPassword) {
                    $("#confirmPasswordError").text("Passwords do not match.");
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
