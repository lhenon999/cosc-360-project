<?php
session_start();
session_destroy();
echo "<script>window.location.href = './pages/home.php';</script>";
exit();
