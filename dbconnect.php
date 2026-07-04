<?php
// dbconnect.php

$db_host = 'localhost';
$db_name = 'cinebook_db'; // Ensure this database is created in your MySQL server
$db_user = 'root';        // Default XAMPP/WAMP user
$db_pass = '';            // Default XAMPP/WAMP password (leave blank)

// Set default timezone to Indian Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');

// Connect to MySQL using procedural MySQLi
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error() . ". Please ensure your MySQL server is running and the database 'cinebook_db' exists.");
}

// Automatically update "Coming Soon" movies to "Now Showing" if they have reached their release date
$res = mysqli_query($conn, "SELECT id, release_date FROM movies WHERE status = 'Coming Soon'");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        if (!empty($row['release_date']) && strtotime($row['release_date']) <= time()) {
            mysqli_query($conn, "UPDATE movies SET status = 'Now Showing' WHERE id = " . $row['id']);
        }
    }
}

// Set charset to UTF-8 for proper character encoding
mysqli_set_charset($conn, "utf8");
?>