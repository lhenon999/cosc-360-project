<?php
session_start();
$directory = ".";
$files = scandir($directory);

echo "<ul>";
foreach ($files as $file) {
    if ($file != "." && $file != "..") {
        echo "<li><a href='$file'>$file</a></li>";
    }
}
echo "</ul>";f