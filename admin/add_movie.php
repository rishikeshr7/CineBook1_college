<?php
session_start();

// Include database connection
require_once 'dbconnect.php';
// include "session.php"; // Uncomment if using session checks

// Check if the user is an admin (Optional but recommended security check)
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: admin_login.php");
//     exit();
// }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitize and retrieve text input data
    $title         = trim($_POST['title'] ?? '');
    $duration      = trim($_POST['duration'] ?? '');
    $genre         = trim($_POST['genre'] ?? '');
    $language      = trim($_POST['language'] ?? '');
    $certification = trim($_POST['certification'] ?? '');
    $synopsis      = trim($_POST['synopsis'] ?? '');
    $director      = trim($_POST['director'] ?? '');
    $release_date  = trim($_POST['release_date'] ?? '');
    $rating        = floatval($_POST['rating'] ?? 0);
    $status        = trim($_POST['status'] ?? '');
    $formats       = trim($_POST['formats'] ?? '');
    $trailer_url   = trim($_POST['trailer_url'] ?? ''); 
    
    // NEW: Check if the Re-Release toggle was checked (returns 1 if checked, 0 if not)
    $is_rerelease  = isset($_POST['is_rerelease']) ? 1 : 0;

    // 2. Basic Validation for required fields
    if (empty($title) || empty($duration) || empty($genre)) {
        header("Location: admin_dashboard.php?error=missingfields");
        exit();
    }

    // 3. Handle Main Poster Image Upload
    $poster_path = ''; 
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (isset($_FILES['poster_image']) && $_FILES['poster_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/posters/'; 
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["poster_image"]["name"], PATHINFO_EXTENSION));

        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid('poster_', true) . '.' . $file_extension;
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES["poster_image"]["tmp_name"], $target_file)) {
                $poster_path = $target_file; 
            } else {
                header("Location: admin_dashboard.php?error=uploadfailed");
                exit();
            }
        } else {
            header("Location: admin_dashboard.php?error=invalidfiletype");
            exit();
        }
    }

    // 4. Prepare the SQL INSERT query for the Movie (Added is_rerelease)
    $sql = "INSERT INTO movies (title, duration, genre, language, certification, synopsis, director, release_date, rating, status, formats, trailer_url, poster_image, is_rerelease) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Bind parameters
        // We now have 12 strings, 1 double (rating), and 1 integer (is_rerelease) -> "ssssssssdssssi"
        $stmt->bind_param("ssssssssdssssi", 
            $title, $duration, $genre, $language, $certification, $synopsis, 
            $director, $release_date, $rating, $status, $formats, $trailer_url, $poster_path, $is_rerelease
        );

        // 5. Execute the query and handle Cast/Crew
        if ($stmt->execute()) {
            
            // Get the ID of the movie we just inserted
            $movie_id = $conn->insert_id;

            // --- 6A. Process Cast Members ---
            if (isset($_POST['cast_names']) && is_array($_POST['cast_names'])) {
                $cast_sql = "INSERT INTO movie_cast (movie_id, actor_name, character_name, profile_image) VALUES (?, ?, ?, ?)";
                $cast_stmt = $conn->prepare($cast_sql);

                foreach ($_POST['cast_names'] as $index => $actor_name) {
                    $actor_name = trim($actor_name);
                    if (!empty($actor_name)) { 
                        $character_name = trim($_POST['cast_characters'][$index] ?? '');
                        $cast_img_path = '';

                        if (isset($_FILES['cast_images']['name'][$index]) && $_FILES['cast_images']['error'][$index] === UPLOAD_ERR_OK) {
                            $cast_dir = 'uploads/cast/';
                            if (!is_dir($cast_dir)) mkdir($cast_dir, 0777, true);
                            
                            $ext = strtolower(pathinfo($_FILES['cast_images']['name'][$index], PATHINFO_EXTENSION));
                            if (in_array($ext, $allowed_extensions)) {
                                $target = $cast_dir . uniqid('cast_', true) . '.' . $ext;
                                if (move_uploaded_file($_FILES['cast_images']['tmp_name'][$index], $target)) {
                                    $cast_img_path = $target;
                                }
                            }
                        }

                        if ($cast_stmt) {
                            $cast_stmt->bind_param("isss", $movie_id, $actor_name, $character_name, $cast_img_path);
                            $cast_stmt->execute();
                        }
                    }
                }
                if ($cast_stmt) $cast_stmt->close();
            }

            // --- 6B. Process Crew Members ---
            if (isset($_POST['crew_names']) && is_array($_POST['crew_names'])) {
                $crew_sql = "INSERT INTO movie_crew (movie_id, crew_name, role, profile_image) VALUES (?, ?, ?, ?)";
                $crew_stmt = $conn->prepare($crew_sql);

                foreach ($_POST['crew_names'] as $index => $crew_name) {
                    $crew_name = trim($crew_name);
                    if (!empty($crew_name)) {
                        $role = trim($_POST['crew_roles'][$index] ?? '');
                        $crew_img_path = '';

                        if (isset($_FILES['crew_images']['name'][$index]) && $_FILES['crew_images']['error'][$index] === UPLOAD_ERR_OK) {
                            $crew_dir = 'uploads/crew/';
                            if (!is_dir($crew_dir)) mkdir($crew_dir, 0777, true);
                            
                            $ext = strtolower(pathinfo($_FILES['crew_images']['name'][$index], PATHINFO_EXTENSION));
                            if (in_array($ext, $allowed_extensions)) {
                                $target = $crew_dir . uniqid('crew_', true) . '.' . $ext;
                                if (move_uploaded_file($_FILES['crew_images']['tmp_name'][$index], $target)) {
                                    $crew_img_path = $target;
                                }
                            }
                        }

                        if ($crew_stmt) {
                            $crew_stmt->bind_param("isss", $movie_id, $crew_name, $role, $crew_img_path);
                            $crew_stmt->execute();
                        }
                    }
                }
                if ($crew_stmt) $crew_stmt->close();
            }

            // --- 6C. Process Multi-Language Trailers ---
            if (isset($_POST['trailer_languages']) && is_array($_POST['trailer_languages'])) {
                $tr_sql = "INSERT INTO movie_trailers (movie_id, language, trailer_url) VALUES (?, ?, ?)";
                $tr_stmt = $conn->prepare($tr_sql);
                
                foreach ($_POST['trailer_languages'] as $index => $tr_lang) {
                    $tr_lang = trim($tr_lang);
                    $tr_url = trim($_POST['trailer_urls'][$index] ?? '');
                    if (!empty($tr_lang) && !empty($tr_url)) {
                        if ($tr_stmt) {
                            $tr_stmt->bind_param("iss", $movie_id, $tr_lang, $tr_url);
                            $tr_stmt->execute();
                        }
                    }
                }
                if ($tr_stmt) $tr_stmt->close();
            }

            header("Location: admin_dashboard.php?success=movieadded");
        } else {
            header("Location: admin_dashboard.php?error=sqlfailed");
        }

        $stmt->close();
    } else {
        header("Location: admin_dashboard.php?error=sqlpreparefailed");
    }
} else {
    header("Location: admin_dashboard.php");
}

$conn->close();
exit();
?>