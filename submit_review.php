<?php
session_start();
require_once 'dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $movie_id = intval($_POST['movie_id']);
    $rating = intval($_POST['rating']);
    $review_text = trim($_POST['review_text']);

    // Basic validation
    if ($rating < 1 || $rating > 10 || empty($movie_id)) {
        header("Location: movie_details.php?id=" . $movie_id);
        exit();
    }

    // 1. Fetch movie duration
    $stmt = $conn->prepare("SELECT duration FROM movies WHERE id = ?");
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        header("Location: index.php");
        exit();
    }
    $movie = $res->fetch_assoc();
    $stmt->close();

    $duration_str = $movie['duration'];
    $duration_minutes = 120;
    if (preg_match('/(?:(\d+)h)?\s*(?:(\d+)m)?/i', $duration_str, $matches)) {
        $m = 0;
        if (!empty($matches[1])) $m += intval($matches[1]) * 60;
        if (!empty($matches[2])) $m += intval($matches[2]);
        if ($m > 0) $duration_minutes = $m;
    }

    // 2. Check if they already reviewed
    $rev_check = $conn->prepare("SELECT id FROM movie_reviews WHERE movie_id = ? AND user_id = ?");
    $rev_check->bind_param("ii", $movie_id, $user_id);
    $rev_check->execute();
    $already_reviewed = $rev_check->get_result()->num_rows > 0;
    $rev_check->close();

    if ($already_reviewed) {
        // Already reviewed
        header("Location: movie_details.php?id=" . $movie_id);
        exit();
    }

    // 3. Check eligibility (Booked and 30 mins passed)
    $can_review = false;
    $book_check = $conn->prepare("
        SELECT b.id 
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.id
        WHERE b.user_id = ? AND s.movie_id = ?
        AND ADDDATE(TIMESTAMP(s.show_date, s.show_time), INTERVAL (? + 30) MINUTE) <= NOW()
        LIMIT 1
    ");
    $book_check->bind_param("iii", $user_id, $movie_id, $duration_minutes);
    $book_check->execute();
    if ($book_check->get_result()->num_rows > 0) {
        $can_review = true;
    }
    $book_check->close();

    if ($can_review) {
        // Insert review
        $insert = $conn->prepare("INSERT INTO movie_reviews (movie_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
        $insert->bind_param("iiis", $movie_id, $user_id, $rating, $review_text);
        $insert->execute();
        $insert->close();
    }

    header("Location: movie_details.php?id=" . $movie_id);
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>
