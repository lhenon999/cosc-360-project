<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Output basic PHP info
echo "<h1>PHP Test</h1>";
echo "<p>PHP version: " . phpversion() . "</p>";
echo "<p>Server time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Server software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Test database connection
echo "<h2>Testing database connection</h2>";
try {
    $conn = new mysqli("localhost", "root", "", "rsodhi03");
    if ($conn->connect_error) {
        echo "<p style='color:red'>Database connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color:green'>Database connection successful!</p>";
        
        // Try to query a table
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            echo "<p>Tables in the database:</p><ul>";
            while($row = $result->fetch_row()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>

<p>If you can see this page with PHP information, your PHP server is working!</p> 