<?php
$admin_password = password_hash('Admin@123', PASSWORD_BCRYPT);
$user_password = password_hash('John@123', PASSWORD_BCRYPT);

echo "Admin Hash: " . $admin_password . "\n";
echo "User Hash: " . $user_password . "\n";
?>