<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, email, user_type FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

$email = isset($user['email']) ? $user['email'] : '';
$isAdmin = ($user['user_type'] === 'admin'); 
?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?= $_SESSION['success'];
        unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?= $_SESSION['error'];
        unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');
    </style>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/settings.css">
</head>

<body>
    <?php include '../assets/html/navbar.php'; ?>

    <div class="settings-container">
        <div class="settings-header">
            <a href="profile.php" class="back-arrow">&#8592;</a>
            <h2><i class="bi bi-gear-fill"></i> Settings</h2>
        </div>

        <div class="mb-4">
            <h5>Account Details</h5>
            <form action="update_profile.php" method="post">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>"
                        required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email"
                        value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary w-100" <?= $isAdmin ? 'disabled' : '' ?>>Save Changes</button>
            </form>
        </div>

        <div class="mb-4">
            <h5>Security</h5>
            <a href="../auth/forgot_password.php?email=<?= urlencode($email); ?>"
                class="btn btn-outline-secondary w-100">
                Change Password
            </a>

        </div>

        <div class="mb-4">
            <h5>Preferences</h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="darkModeToggle" <?= $dark_mode_checked ?>>
                <label class="form-check-label" for="darkModeToggle">Enable Dark Mode</label>
            </div>
        </div>

        <div>
            <button class="btn btn-delete">Delete My Account</button>
        </div>
    </div>

    <script>
        document.getElementById('darkModeToggle').addEventListener('change', function () {
            document.body.classList.toggle('bg-dark');
            document.body.classList.toggle('text-light');
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const darkModeToggle = document.getElementById("darkModeToggle");

            let darkModeLocal = localStorage.getItem("darkMode") === "enabled";
            if (darkModeLocal) {
                document.body.classList.add("bg-dark", "text-light");
                darkModeToggle.checked = true;
            }

            darkModeToggle.addEventListener("change", function () {
                let darkMode = this.checked ? 1 : 0;

                document.body.classList.toggle("bg-dark", darkMode);
                document.body.classList.toggle("text-light", darkMode);

                localStorage.setItem("darkMode", darkMode ? "enabled" : "disabled");

                fetch("../dark_mode.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "dark_mode=" + darkMode
                });
            });
        });
    </script>
</body>

</html>