<?php
session_start();
require __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, email, user_type FROM USERS WHERE id = ?");
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
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/settings.css">
</head>

<body>
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

    <div class="settings-container">
        <div class="settings-header">
            <h2><i class="bi bi-gear-fill"></i> Settings</h2>
        </div>

        <div class="mb-4">
            <h5>Account Details</h5>
            <form action="update_profile.php" method="post">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>"
                        required <?= $isAdmin ? 'disabled' : '' ?>>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email"
                        value="<?= htmlspecialchars($user['email']) ?>" required <?= $isAdmin ? 'disabled' : '' ?>>
                </div>
                <button type="submit" class="btn btn-primary w-100" <?= $isAdmin ? 'disabled' : '' ?>>Save
                    Changes</button>
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
            <div class="theme-toggle">
                <input type="radio" id="light-theme" name="theme" />
                <label for="light-theme">
                    <span>
                        <i class="bi bi-brightness-high"></i> Light
                    </span>
                </label>
                <input type="radio" id="dark-theme" name="theme" />
                <label for="dark-theme">
                    <span>
                        <i class="bi bi-moon"></i> Dark
                    </span>
                </label>
                <div class="theme-slider"></div>
            </div>
        </div>
        <button id="advanced-settings-btn" class="btn btn-outline-secondary w-100 w-100">Advanced Settings</button>

        <div id="advanced-settings" class="advanced-settings-content">
            <div class="mb-4">
                <h5>Advanced Settings</h5>
                <label class="form-label">Account Deletion</label>
                </select>
                <button id="delete-btn" class="btn btn-outline-secondary" <?= $isAdmin ? 'disabled' : '' ?>>Delete My Account</button>
            </div>
        </div>
        <div>
                <a href="profile.php" class="btn btn-outline-secondary w-100" </a>Back</a>
        </div>
    </div>
        <script src="../assets/js/bg-dark.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const advancedSettingsButton = document.getElementById("advanced-settings-btn");
                const advancedSettingsContent = document.getElementById("advanced-settings");

                advancedSettingsButton.addEventListener("click", function () {
                    if (advancedSettingsContent.classList.contains("expanded")) {
                        advancedSettingsContent.classList.remove("expanded");
                        advancedSettingsButton.textContent = "Advanced Settings";
                    } else {
                        advancedSettingsContent.classList.add("expanded");
                        advancedSettingsButton.textContent = "Hide";
                    }
                });
            });
    
        </script>

<?php include __DIR__ . '/../assets/html/footer.php'; ?>
</body>

</html>