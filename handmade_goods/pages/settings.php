<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email, user_type, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$email = $user['email'] ?? '';
$name = $user['name'] ?? '';
$profile_picture = $user['profile_picture'] ?? '';
$isAdmin = ($user['user_type'] === 'admin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handmade Goods - Settings</title>

    <style>@import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap');</style>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/settings.css">
</head>
<body>
<?php include '../assets/html/navbar.php'; ?>

<div class="container settings-container mt-5 mb-5 p-4 rounded shadow bg-light">
    <h2>Settings</h2>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form action="update_profile.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required <?= $isAdmin ? 'disabled' : '' ?>>
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required <?= $isAdmin ? 'disabled' : '' ?>>
        </div>

        <div class="mb-3">
            <label class="form-label">Profile Picture</label>
            <input type="file" name="profile_picture" class="form-control" accept="image/*">
            <?php if (!empty($profile_picture) && $profile_picture !== '/cosc-360-project/handmade_goods/assets/images/default_profile.png'): ?>
                <img src="<?= htmlspecialchars($profile_picture) ?>" width="100" class="mt-2 rounded">
                <div class="form-check mt-2">
                    <input type="checkbox" name="remove_picture" class="form-check-input" id="removePicture">
                    <label for="removePicture" class="form-check-label">Remove current picture</label>
                </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary w-100" <?= $isAdmin ? 'disabled' : '' ?>>Save Changes</button>
    </form>

    <hr class="my-4">

    <div class="mb-4">
        <h5>Security</h5>
        <a href="../auth/forgot_password.php?email=<?= urlencode($email); ?>" class="btn btn-outline-secondary w-100">
            Change Password
        </a>
    </div>

    <div class="mb-4">
        <h5>Preferences</h5>
        <div class="theme-toggle">
            <input type="radio" id="light-theme" name="theme" />
            <label for="light-theme"><span><i class="bi bi-brightness-high"></i> Light</span></label>
            <input type="radio" id="dark-theme" name="theme" />
            <label for="dark-theme"><span><i class="bi bi-moon"></i> Dark</span></label>
            <div class="theme-slider"></div>
        </div>
    </div>

    <button id="advanced-settings-btn" class="btn btn-outline-secondary w-100">Advanced Settings</button>
    <div id="advanced-settings" class="advanced-settings-content mt-3" style="display: none;">
        <div class="mb-4">
            <h5>Advanced</h5>
            <label class="form-label">Account Deletion</label>
            <button id="delete-btn" class="btn btn-outline-danger w-100" <?= $isAdmin ? 'disabled' : '' ?>>Delete My Account</button>
        </div>
    </div>

    <div class="mt-4">
        <a href="profile.php" class="btn btn-outline-secondary w-100">Back</a>
    </div>
</div>

<script>
    document.getElementById("advanced-settings-btn").addEventListener("click", function () {
        const section = document.getElementById("advanced-settings");
        const isVisible = section.style.display === "block";
        section.style.display = isVisible ? "none" : "block";
        this.textContent = isVisible ? "Advanced Settings" : "Hide Advanced Settings";
    });
</script>

<?php include '../assets/html/footer.php'; ?>
</body>
</html>