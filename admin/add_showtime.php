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
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'type' => 'missingfields']);
        exit();
    }

    // 3. Prevent Overlaps & Enforce 55-Min Rule
    function getMinutesFromDuration($durationStr) {
        $hours = 0; $minutes = 0;
        if (preg_match('/(\d+)h/', $durationStr, $matches)) { $hours = (int)$matches[1]; }
        if (preg_match('/(\d+)m/', $durationStr, $matches)) { $minutes = (int)$matches[1]; }
        return ($hours * 60) + $minutes;
    }

    $dur_stmt = $conn->prepare("SELECT duration FROM movies WHERE id = ? LIMIT 1");
    $dur_stmt->bind_param("i", $movie_id);
    $dur_stmt->execute();
    $dur_res = $dur_stmt->get_result();
    $new_movie = $dur_res->fetch_assoc();
    $dur_stmt->close();

    $new_dur_mins = getMinutesFromDuration($new_movie['duration'] ?? '0h 0m');
    $new_start = strtotime("$show_date $show_time");
    $new_end = $new_start + (($new_dur_mins + 55) * 60);

    $conflict_sql = "
        SELECT s.show_date, s.show_time, m.duration 
        FROM showtimes s
        JOIN movies m ON s.movie_id = m.id
        WHERE s.theater_id = ? AND s.screen_id = ? 
        AND s.show_date BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 1 DAY)
    ";
    $conflict_stmt = $conn->prepare($conflict_sql);
    $conflict_stmt->bind_param("ssss", $theater_id, $screen_id, $show_date, $show_date);
    $conflict_stmt->execute();
    $conflict_res = $conflict_stmt->get_result();

    $has_conflict = false;
    $max_existing_end = 0;
    while ($row = $conflict_res->fetch_assoc()) {
        $ex_start = strtotime($row['show_date'] . ' ' . $row['show_time']);
        $ex_dur_mins = getMinutesFromDuration($row['duration'] ?? '0h 0m');
        $ex_end = $ex_start + (($ex_dur_mins + 55) * 60);

        if ($new_start < $ex_end && $new_end > $ex_start) {
            $has_conflict = true;
            if ($ex_end > $max_existing_end) {
                $max_existing_end = $ex_end;
            }
        }
    }
    $conflict_stmt->close();

    if ($has_conflict) {
        $recommended_time = date('H:i', $max_existing_end);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'type' => 'conflict',
            'recommended_time' => $recommended_time
        ]);
        exit();
    }

    // 4. Prepare the SQL INSERT query (Added city column here)
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
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'type' => 'sqlfailed']);
        }

        $stmt->close();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'type' => 'sqlpreparefailed']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'type' => 'invalidmethod']);
}

$conn->close();
exit();
?>