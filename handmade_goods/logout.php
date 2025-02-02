<?php
session_start();
session_destroy();
echo "<script>window.location.href = '/handmade_goods/pages/home.php';</script>";
exit();
