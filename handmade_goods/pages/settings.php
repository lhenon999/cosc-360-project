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
    <link rel="stylesheet" href="../assets/css/globals.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/settings.css">
</head>

<body>
    <?php include '../assets/html/navbar.php'; ?>

    <div class="settings-container">
        <h2>Settings</h2>

        <div class="settings-section">
            <h3>Account Settings</h3>
            <form action="update_profile.php" method="post">
                <label>Name:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

                <button type="submit">Save Changes</button>
            </form>
        </div>

        <div class="settings-section">
            <h3>Security</h3>
            <a href="change_password.php" class="btn">Change Password</a>
        </div>

        <div class="settings-section">
            <h3>Preferences</h3>
            <label class="toggle-switch">
                <input type="checkbox" id="darkModeToggle">
                <span class="slider"></span>
            </label>
            <p>Enable Dark Mode</p>
        </div>

        <div class="settings-section">
            <a href="delete_account.php" class="btn-danger">Delete My Account</a>
        </div>
    </div>
    <script src="../assets/js/dark_mode.js"></script>
</body>

</html>