<?php
// dbconnect.php

$db_host = 'localhost';
$db_name = 'cinebook_db'; // Ensure this database is created in your MySQL server
$db_user = 'root';        // Default XAMPP/WAMP user
$db_pass = '';            // Default XAMPP/WAMP password (leave blank)

// Connect to MySQL using procedural MySQLi
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error() . ". Please ensure your MySQL server is running and the database 'cinebook_db' exists.");
}

// Set charset to UTF-8 for proper character encoding
mysqli_set_charset($conn, "utf8");
?>