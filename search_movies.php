<?php
// search_movies.php
require_once 'dbconnect.php';

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';

if (empty($q)) {
    echo json_encode([]);
    exit;
}

$search_term = '%' . $q . '%';
$results = [];

// 1. Fetch "Now Showing" movies that match the title AND are playing in the selected city
$ns_sql = "
    SELECT DISTINCT m.id, m.title, m.poster_image, m.genre, m.status 
    FROM movies m 
    INNER JOIN showtimes s ON m.id = s.movie_id 
    WHERE m.status = 'Now Showing' 
      AND s.city = ? 
      AND m.title LIKE ? 
      AND s.show_date >= CURRENT_DATE
    ORDER BY m.title ASC
    LIMIT 5
";
$ns_stmt = $conn->prepare($ns_sql);
if ($ns_stmt) {
    $ns_stmt->bind_param("ss", $city, $search_term);
    $ns_stmt->execute();
    $ns_res = $ns_stmt->get_result();
    while ($row = $ns_res->fetch_assoc()) {
        $results[] = $row;
    }
    $ns_stmt->close();
}

// 2. Fetch "Coming Soon" movies globally that match the title
$cs_sql = "
    SELECT id, title, poster_image, genre, status 
    FROM movies 
    WHERE status = 'Coming Soon' 
      AND title LIKE ? 
    ORDER BY title ASC
    LIMIT 5
";
$cs_stmt = $conn->prepare($cs_sql);
if ($cs_stmt) {
    $cs_stmt->bind_param("s", $search_term);
    $cs_stmt->execute();
    $cs_res = $cs_stmt->get_result();
    while ($row = $cs_res->fetch_assoc()) {
        $results[] = $row;
    }
    $cs_stmt->close();
}

echo json_encode($results);
$conn->close();
?>
