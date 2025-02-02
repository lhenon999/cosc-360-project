<?php
session_start();
session_destroy();
header("Location: /handmade_good/html/login.html");
exit();
?>
