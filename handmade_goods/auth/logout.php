<?php
session_start();
$_SESSION = []; 
session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');

header("Location: /cosc-360-project/handmade_goods/pages/home.php");
exit();
