<?php
session_start();

// Include database connection
require_once 'dbconnect.php';

// Optional: Security check to ensure only admins can add showtimes
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: admin_login.php");
//     exit();
// }

// Check if the form was actually submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Sanitize and retrieve input data (Added City here)
    $movie_id      = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
    $city          = trim($_POST['city'] ?? '');
    $theater_id    = trim($_POST['theater_id'] ?? '');
    $screen_id     = trim($_POST['screen_id'] ?? '');
    $format        = trim($_POST['format'] ?? '');
    $language      = trim($_POST['language'] ?? '');
    $show_date     = trim($_POST['show_date'] ?? '');
    $show_time     = trim($_POST['show_time'] ?? '');
    $total_seats   = isset($_POST['total_seats']) ? (int)$_POST['total_seats'] : 0;
    $price_regular = isset($_POST['price_regular']) ? (float)$_POST['price_regular'] : 0.00;
    $price_premium = isset($_POST['price_premium']) ? (float)$_POST['price_premium'] : 0.00;
    $price_vip     = isset($_POST['price_vip']) ? (float)$_POST['price_vip'] : 0.00;

    // 2. Basic Validation for required fields (Added City validation here)
    if ($movie_id === 0 || empty($city) || empty($theater_id) || empty($show_date) || empty($show_time)) {
        header("Location: scheduling.php?error=missingfields");
        exit();
    }

    // 3. Prepare the SQL INSERT query (Added city column here)
    $sql = "INSERT INTO showtimes (movie_id, city, theater_id, screen_id, format, language, show_date, show_time, total_seats, price_regular, price_premium, price_vip) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Bind parameters
        // Types: i = integer, s = string, d = double/float
        // Order: i (movie), s (city), s (theater), s (screen), s (format), s (lang), s (date), s (time), i (seats), d (reg), d (prem), d (vip)
        // Bind string is now 12 characters: "isssssssiddd"
        
        $stmt->bind_param("isssssssiddd", 
            $movie_id, 
            $city,
            $theater_id, 
            $screen_id, 
            $format, 
            $language, 
            $show_date, 
            $show_time, 
            $total_seats, 
            $price_regular, 
            $price_premium, 
            $price_vip
        );

        // 4. Execute the query
        if ($stmt->execute()) {
            // Success! Redirect back to the scheduling page with a success flag
            // Optionally, pass the month and year back so the calendar stays on the month they were viewing
            $m = date('n', strtotime($show_date));
            $y = date('Y', strtotime($show_date));
            header("Location: scheduling.php?success=showtimeadded&m=$m&y=$y");
        } else {
            // Execution failed
            header("Location: scheduling.php?error=sqlfailed");
        }

        $stmt->close();
    } else {
        // Preparation failed (usually means a mismatch between columns and the query)
        header("Location: scheduling.php?error=sqlpreparefailed");
    }
} else {
    // Direct access not allowed
    header("Location: scheduling.php");
}

$conn->close();
exit();
?>