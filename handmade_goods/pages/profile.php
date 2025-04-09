<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user_type = $_SESSION["user_type"];

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}

$user_id = intval($_SESSION["user_id"]);
$stmt = $conn->prepare("SELECT name, email, profile_picture FROM USERS WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $profile_picture);
$stmt->fetch();
$stmt->close();

$all_users = [];
if ($user_type === 'admin') {
    $stmt = $conn->prepare("
        SELECT 
            u.id, 
            u.name, 
            u.email, 
            u.user_type, 
            u.created_at,
            (SELECT COUNT(*) FROM ORDERS WHERE user_id = u.id) as total_orders,
            (SELECT COUNT(*) FROM ITEMS WHERE user_id = u.id) as total_listings
        FROM USERS u
        WHERE u.id != ?
        ORDER BY u.created_at DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_users[] = $row;
    }
    $stmt->close();
}

$isAdvanced = isset($_GET['page']) && $_GET['page'] === 'advanced';
$toggleLink = $isAdvanced ? 'profile.php' : 'profile.php?page=advanced';


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Handmade Goods</title>

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
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="../assets/css/profile_bg-dark.css">
    <link rel="stylesheet" href="../assets/css/profile_admin.css">
    <link rel="stylesheet" href="../assets/css/profile_user.css">
</head>

<body>
    <?php include __DIR__ . '/../assets/html/navbar.php'; ?>

    <div>
        <h1 class="text-center mt-5"><?php echo ($user_type === 'admin') ? 'Admin Dashboard' : 'My Profile'; ?></h1>

        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-image">
                    <div class="profile-image">
                        <form id="profilePicForm" action="upload_profile_picture.php" method="POST"
                            enctype="multipart/form-data">
                            <input type="file" name="profile_picture" id="profileInput" accept="image/*"
                                style="display: none;">
                            <?php if ($user_type !== 'admin'): ?>
                                <label for="profileInput">
                                    <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture"
                                        id="profilePic" data-bs-toggle="tooltip" data-bs-placement="top"
                                        title="Click to change your profile picture">

                                </label>
                            <?php endif ?>
                        </form>
                    </div>

                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($name); ?></h2>
                    <p><?php echo htmlspecialchars($email); ?></p>
                    <div class="profile-buttons">
                        <?php if ($user_type == 'admin'): ?>
                            <a class="r-btn header-btn <?php echo $isAdvanced ? 'active' : ''; ?>"
                                href="../pages/<?php echo $toggleLink; ?>">
                                <span class="material-symbols-outlined">assessment</span>Advanced Report
                            </a>
                        <?php endif; ?>
                        <a class="r-btn header-btn" href="../pages/settings.php">
                            <span class="material-symbols-outlined">settings</span>Settings
                        </a>

                        <?php if ($user_type !== 'admin'): ?>
                            <a class="r-btn header-btn" href="../pages/my_shop.php">
                                <span class="material-symbols-outlined">storefront</span>My Shop
                            </a>
                        <?php endif; ?>
                        <a class="r-btn header-btn" href="../auth/logout.php">
                            <span class="material-symbols-outlined">logout</span>Logout
                        </a>
                    </div>

                </div>
            </div>
            <?php if ($user_type === 'admin'): ?>
                <?php
                if ($isAdvanced) {
                    include __DIR__ . '/advanced_reports.php';
                } else {
                    include __DIR__ . '/profile_admin_dashboard.php';
                }
                ?>
            <?php else: ?>
                <?php include __DIR__ . '/profile_user_dashboard.php'; ?>
            <?php endif; ?>
        </div>
    </div>
    <script>
        document.getElementById("profileInput").addEventListener("change", function () {
            document.getElementById("profilePicForm").submit();
        });
    </script>
    <script src="../assets/js/profile_handle_modal.js"></script>
    <script src="../assets/js/profile_tab_switching.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>const totalEarnings = <?= json_encode($totalEarnings) ?>;</script>
    <script src="../assets/js/profile_earnings_chart.js"></script>
    <script>
        let urlParams = new URLSearchParams(window.location.search);
        let itemName = urlParams.get('item');
        let userName = urlParams.get('user');
        let userType = "<?= $user_type ?>";
    </script>
    <script src="../assets/js/admin_manage_listing.js"></script>

    <?php include __DIR__ . '/../assets/html/footer.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>