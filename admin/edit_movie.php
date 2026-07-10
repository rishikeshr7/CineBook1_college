<?php
session_start();
require_once 'dbconnect.php';

// Check if a movie ID was passed
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$movie_id = intval($_GET['id']);

// Fetch Movie Details
$stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$movie_result = $stmt->get_result();
$movie = $movie_result->fetch_assoc();
$stmt->close();

if (!$movie) {
    // If movie doesn't exist, go back home
    header("Location: index.php");
    exit();
}

// Fetch Cast
$cast_stmt = $conn->prepare("SELECT * FROM movie_cast WHERE movie_id = ?");
$cast_stmt->bind_param("i", $movie_id);
$cast_stmt->execute();
$cast_members = $cast_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cast_stmt->close();

// Fetch Crew
$crew_stmt = $conn->prepare("SELECT * FROM movie_crew WHERE movie_id = ?");
$crew_stmt->bind_param("i", $movie_id);
$crew_stmt->execute();
$crew_members = $crew_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$crew_stmt->close();

// Fetch Trailers
$trailer_stmt = $conn->prepare("SELECT * FROM movie_trailers WHERE movie_id = ?");
$trailer_stmt->bind_param("i", $movie_id);
$trailer_stmt->execute();
$movie_trailers = $trailer_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$trailer_stmt->close();

// 2. Process the Update Form if POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $movie_id      = intval($_POST['movie_id']);
    $title         = trim($_POST['title'] ?? '');
    $duration      = trim($_POST['duration'] ?? '');
    $genre         = trim($_POST['genre'] ?? '');
    $language      = trim($_POST['language'] ?? '');
    $certification = trim($_POST['certification'] ?? '');
    $synopsis      = trim($_POST['synopsis'] ?? '');
    $director      = trim($_POST['director'] ?? '');
    $release_date  = trim($_POST['release_date'] ?? '');
    $rating        = trim($_POST['rating'] ?? ''); // Rating is now text
    $formats       = trim($_POST['formats'] ?? '');
    $trailer_url   = trim($_POST['trailer_url'] ?? ''); 
    $is_rerelease  = isset($_POST['is_rerelease']) ? 1 : 0;

    // Auto-determine status from release date
    if (!empty($release_date) && strtotime($release_date) !== false) {
        $release_ts = strtotime($release_date);
        $today_ts = strtotime('today');
        $status = ($release_ts <= $today_ts) ? 'Now Showing' : 'Coming Soon';
    } else {
        $status = trim($_POST['status'] ?? 'Now Showing');
    }

    if (empty($title) || empty($duration) || empty($genre)) {
        header("Location: admin_dashboard.php?error=missingfields");
        exit();
    }

    // Handle Main Poster Image Upload
    $poster_update_sql = "";
    $poster_path = "";
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (isset($_FILES['poster_image']) && $_FILES['poster_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/posters/'; 
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_extension = strtolower(pathinfo($_FILES["poster_image"]["name"], PATHINFO_EXTENSION));

        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid('poster_', true) . '.' . $file_extension;
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES["poster_image"]["tmp_name"], $target_file)) {
                $poster_path = $target_file;
                $poster_update_sql = ", poster_image = ?";
            }
        }
    }

    // Update the movies table
    $sql = "UPDATE movies SET title=?, duration=?, genre=?, language=?, certification=?, synopsis=?, director=?, release_date=?, rating=?, status=?, formats=?, trailer_url=?, is_rerelease=? $poster_update_sql WHERE id=?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // FIXED: bind_param string updated to reflect rating as a String ('s') instead of Double ('d')
        if (!empty($poster_path)) {
            $stmt->bind_param("ssssssssssssisi", $title, $duration, $genre, $language, $certification, $synopsis, $director, $release_date, $rating, $status, $formats, $trailer_url, $is_rerelease, $poster_path, $movie_id);
        } else {
            $stmt->bind_param("ssssssssssssii", $title, $duration, $genre, $language, $certification, $synopsis, $director, $release_date, $rating, $status, $formats, $trailer_url, $is_rerelease, $movie_id);
        }

        if ($stmt->execute()) {
            
            // --- Process Cast Members ---
            $conn->query("DELETE FROM movie_cast WHERE movie_id = $movie_id");

            if (isset($_POST['cast_names']) && is_array($_POST['cast_names'])) {
                $cast_sql = "INSERT INTO movie_cast (movie_id, actor_name, character_name, profile_image) VALUES (?, ?, ?, ?)";
                $cast_stmt = $conn->prepare($cast_sql);
                $existing_cast_images = $_POST['existing_cast_images'] ?? [];

                foreach ($_POST['cast_names'] as $index => $actor_name) {
                    $actor_name = trim($actor_name);
                    if (!empty($actor_name)) { 
                        $character_name = trim($_POST['cast_characters'][$index] ?? '');
                        $cast_img_path = $existing_cast_images[$index] ?? '';

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

            // --- Process Crew Members ---
            $conn->query("DELETE FROM movie_crew WHERE movie_id = $movie_id");

            if (isset($_POST['crew_names']) && is_array($_POST['crew_names'])) {
                $crew_sql = "INSERT INTO movie_crew (movie_id, crew_name, role, profile_image) VALUES (?, ?, ?, ?)";
                $crew_stmt = $conn->prepare($crew_sql);
                $existing_crew_images = $_POST['existing_crew_images'] ?? [];

                foreach ($_POST['crew_names'] as $index => $crew_name) {
                    $crew_name = trim($crew_name);
                    if (!empty($crew_name)) {
                        $role = trim($_POST['crew_roles'][$index] ?? '');
                        $crew_img_path = $existing_crew_images[$index] ?? '';

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

            // --- Process Multi-Language Trailers ---
            $conn->query("DELETE FROM movie_trailers WHERE movie_id = $movie_id");

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

            header("Location: admin_dashboard.php?success=movieupdated");
            exit();
        } else {
            header("Location: admin_dashboard.php?error=sqlfailed");
            exit();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/svg+xml" href="/CineBook/favicon.svg">
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Movie - CineBook Admin</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bgMain: '#0a0a0a',
                        bgCard: '#121212',
                        inputBg: '#1a1a1a',
                        borderMain: '#262626',
                        inputBorder: '#333333',
                        brand: '#F5C518',
                        textMuted: '#a3a3a3',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }
        
        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-bgMain text-gray-900 dark:text-gray-100 font-sans flex h-screen overflow-hidden">

    <?php include "sidebar.php"; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
            
            <div class="max-w-4xl mx-auto w-full">
                <header class="mb-8 flex items-center gap-4">
                    <a href="admin_dashboard.php" class="p-2 rounded-lg bg-gray-200 dark:bg-borderMain hover:bg-gray-300 dark:hover:bg-gray-700 transition-colors">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold mb-1 text-gray-900 dark:text-white">Edit Movie</h1>
                        <p class="text-gray-500 dark:text-textMuted text-sm">Update details for "<?php echo htmlspecialchars($movie['title']); ?>"</p>
                    </div>
                </header>

                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-6 sm:p-8 shadow-sm">
                    <form id="edit-movie-form" action="edit_movie.php?id=<?php echo $movie_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                        
                        <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Title <span class="text-red-500">*</span></label>
                                <input type="text" name="title" value="<?php echo htmlspecialchars($movie['title']); ?>" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Duration <span class="text-red-500">*</span></label>
                                <input type="text" name="duration" value="<?php echo htmlspecialchars($movie['duration']); ?>" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Genre <span class="text-red-500">*</span></label>
                            <input type="text" name="genre" value="<?php echo htmlspecialchars($movie['genre']); ?>" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Language</label>
                                <input type="text" name="language" value="<?php echo htmlspecialchars($movie['language']); ?>" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Certification</label>
                                <select name="certification" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    <option value="UA" <?php if($movie['certification'] == 'UA') echo 'selected'; ?>>UA</option>
                                    <option value="U" <?php if($movie['certification'] == 'U') echo 'selected'; ?>>U</option>
                                    <option value="A" <?php if($movie['certification'] == 'A') echo 'selected'; ?>>A</option>
                                    <option value="S" <?php if($movie['certification'] == 'S') echo 'selected'; ?>>S</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Synopsis</label>
                            <textarea name="synopsis" rows="4" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors resize-none"><?php echo htmlspecialchars($movie['synopsis']); ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Director</label>
                                <input type="text" name="director" value="<?php echo htmlspecialchars($movie['director']); ?>" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Release Date</label>
                                <input type="text" id="edit-release-date" name="release_date" value="<?php echo htmlspecialchars($movie['release_date']); ?>" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Rating(out of 10)</label>
                                <input type="text" name="rating" placeholder="e.g. 8.5/10, Excellent, etc." value="<?php echo htmlspecialchars($movie['rating']); ?>" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Status <span class="text-xs text-gray-400 font-normal">(auto-detected)</span></label>
                                <input type="hidden" name="status" id="edit-status-hidden" value="<?php echo htmlspecialchars($movie['status']); ?>">
                                <div id="edit-status-badge" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder rounded-lg p-3 text-sm font-bold transition-colors text-gray-400 dark:text-gray-500">
                                    Detecting...
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-200 dark:border-inputBorder bg-white dark:bg-[#1a1a1a]/50 rounded-lg p-4 flex justify-between items-center transition-colors">
                            <div>
                                <label for="m-rerelease" class="text-sm font-bold text-gray-900 dark:text-white cursor-pointer">Re-Release</label>
                                <p class="text-xs text-gray-500 dark:text-textMuted mt-0.5">Mark this movie as a re-release of an older title</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" id="m-rerelease" name="is_rerelease" value="1" class="sr-only peer" <?php if($movie['is_rerelease']) echo 'checked'; ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-[#333333] peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand"></div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Formats</label>
                            <input type="text" name="formats" value="<?php echo htmlspecialchars($movie['formats']); ?>" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Update Poster Image</label>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                                <?php if (!empty($movie['poster_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($movie['poster_image']); ?>" class="w-20 h-28 object-cover rounded shadow border border-gray-200 dark:border-borderMain shrink-0">
                                <?php endif; ?>
                                <div class="relative flex-1 w-full">
                                    <input type="file" id="m-poster" name="poster_image" accept="image/*" class="hidden" onchange="updateFileName(this)">
                                    <label for="m-poster" class="w-full flex items-center justify-center gap-2 cursor-pointer bg-gray-50 dark:bg-inputBg hover:bg-gray-100 dark:hover:bg-borderMain text-gray-900 dark:text-white font-semibold py-3 px-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg transition-colors text-sm h-28">
                                        <i data-lucide="upload" class="w-5 h-5 text-gray-400"></i>
                                        <span id="file-name-display" class="text-gray-500">Upload New Poster (Optional)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Trailer URL</label>
                            <input type="url" name="trailer_url" value="<?php echo htmlspecialchars($movie['trailer_url']); ?>" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                        </div>

                        <!-- Additional Multi-language Trailers -->
                        <div class="border border-gray-200 dark:border-borderMain rounded-xl p-5 space-y-4 mt-6 bg-gray-50 dark:bg-[#0c0c0c]">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="video" class="w-4 h-4 text-brand"></i> Additional Trailers (Multi-Language)
                            </h4>
                            <div id="trailer-fields-container" class="space-y-3">
                                <!-- Dynamic rows go here -->
                            </div>
                            <button type="button" onclick="addTrailerRow()" class="w-full mt-2 py-2.5 rounded-lg bg-transparent text-yellow-600 dark:text-brand border border-yellow-600 dark:border-brand text-xs font-bold hover:bg-yellow-50 dark:hover:bg-brand/10 transition-colors flex justify-center items-center gap-1.5">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Trailer Language
                            </button>
                        </div>

                        <div class="space-y-4 mt-10">
                            <h4 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 border-b border-gray-200 dark:border-borderMain pb-2">
                                <i data-lucide="user-plus" class="w-5 h-5 text-brand"></i> Cast Members
                            </h4>
                            
                            <div id="cast-fields-container" class="space-y-4">
                                <?php 
                                $castCount = 0;
                                foreach($cast_members as $cast): 
                                    $castCount++;
                                ?>
                                    <div class="member-row relative border border-dashed border-gray-300 dark:border-borderMain rounded-xl p-5 bg-white dark:bg-[#121212]">
                                        <button type="button" onclick="this.closest('.member-row').remove()" class="absolute top-5 right-5 text-gray-400 hover:text-red-500 transition-colors" title="Remove Member">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </button>
                                        
                                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-4">Edit Cast Member</p>
                                        
                                        <input type="hidden" name="existing_cast_images[]" value="<?php echo htmlspecialchars($cast['profile_image']); ?>">
                                        
                                        <div class="flex flex-col sm:flex-row gap-5 items-start sm:items-center">
                                            <div class="shrink-0 relative group">
                                                <input type="file" id="existing-cast-img-<?php echo $castCount; ?>" name="cast_images[]" class="hidden" accept="image/*" onchange="updateCastCrewImageName(this)">
                                                <label for="existing-cast-img-<?php echo $castCount; ?>" class="w-16 h-16 rounded-full bg-gray-100 dark:bg-[#222222] border border-gray-200 dark:border-transparent flex items-center justify-center cursor-pointer overflow-hidden group-hover:opacity-80 transition-opacity" title="Change Image">
                                                    <?php if(!empty($cast['profile_image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($cast['profile_image']); ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <i data-lucide="upload" class="w-5 h-5 text-gray-400"></i>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                            <div class="flex-1 space-y-3 w-full pr-8 sm:pr-0">
                                                <input type="text" name="cast_names[]" value="<?php echo htmlspecialchars($cast['actor_name']); ?>" placeholder="Actor/Actress name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-500 transition-colors">
                                                <input type="text" name="cast_characters[]" value="<?php echo htmlspecialchars($cast['character_name']); ?>" placeholder="Character name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-500 transition-colors">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="button" onclick="addCastMember()" class="w-full mt-2 py-3 rounded-lg bg-transparent text-yellow-600 dark:text-brand border border-yellow-600 dark:border-brand text-sm font-bold hover:bg-yellow-50 dark:hover:bg-brand/10 transition-colors flex justify-center items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i> Add More Cast
                            </button>
                        </div>

                        <div class="space-y-4 mt-10">
                            <h4 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 border-b border-gray-200 dark:border-borderMain pb-2">
                                <i data-lucide="users" class="w-5 h-5 text-brand"></i> Crew Members
                            </h4>
                            
                            <div id="crew-fields-container" class="space-y-4">
                                <?php 
                                $crewCount = 0;
                                foreach($crew_members as $crew): 
                                    $crewCount++;
                                ?>
                                    <div class="member-row relative border border-dashed border-gray-300 dark:border-borderMain rounded-xl p-5 bg-white dark:bg-[#121212]">
                                        <button type="button" onclick="this.closest('.member-row').remove()" class="absolute top-5 right-5 text-gray-400 hover:text-red-500 transition-colors" title="Remove Member">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </button>
                                        
                                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-4">Edit Crew Member</p>
                                        
                                        <input type="hidden" name="existing_crew_images[]" value="<?php echo htmlspecialchars($crew['profile_image']); ?>">
                                        
                                        <div class="flex flex-col sm:flex-row gap-5 items-start sm:items-center">
                                            <div class="shrink-0 relative group">
                                                <input type="file" id="existing-crew-img-<?php echo $crewCount; ?>" name="crew_images[]" class="hidden" accept="image/*" onchange="updateCastCrewImageName(this)">
                                                <label for="existing-crew-img-<?php echo $crewCount; ?>" class="w-16 h-16 rounded-full bg-gray-100 dark:bg-[#222222] border border-gray-200 dark:border-transparent flex items-center justify-center cursor-pointer overflow-hidden group-hover:opacity-80 transition-opacity" title="Change Image">
                                                    <?php if(!empty($crew['profile_image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($crew['profile_image']); ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <i data-lucide="upload" class="w-5 h-5 text-gray-400"></i>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                            <div class="flex-1 space-y-3 w-full pr-8 sm:pr-0">
                                                <input type="text" name="crew_names[]" value="<?php echo htmlspecialchars($crew['crew_name']); ?>" placeholder="Crew member name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-500 transition-colors">
                                                <input type="text" name="crew_roles[]" value="<?php echo htmlspecialchars($crew['role']); ?>" placeholder="Role (e.g. Cinematographer, Editor)" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-500 transition-colors">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="button" onclick="addCrewMember()" class="w-full mt-2 py-3 rounded-lg bg-transparent text-yellow-600 dark:text-brand border border-yellow-600 dark:border-brand text-sm font-bold hover:bg-yellow-50 dark:hover:bg-brand/10 transition-colors flex justify-center items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i> Add More Crew
                            </button>
                        </div>

                        <div class="pt-6 border-t border-gray-200 dark:border-borderMain mt-8 flex justify-end gap-4">
                            <a href="admin_dashboard.php" class="px-6 py-3 rounded-lg font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-borderMain transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-8 py-3 rounded-lg bg-[#F5C518] text-black font-bold hover:bg-yellow-500 transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        const themeToggle = document.getElementById('toggle-theme');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                }
            });
        }

        // Update main poster file name label
        function updateFileName(input) {
            const displaySpan = document.getElementById('file-name-display');
            if (input.files && input.files.length > 0) {
                displaySpan.textContent = input.files[0].name;
                displaySpan.classList.remove('text-gray-500');
                displaySpan.classList.add('text-brand', 'dark:text-brand');
            } else {
                displaySpan.textContent = 'Upload New Poster (Optional)';
                displaySpan.classList.add('text-gray-500');
                displaySpan.classList.remove('text-brand', 'dark:text-brand');
            }
        }

        // Initialize counters dynamically based on existing rows
        let castCount = <?php echo $castCount + 100; ?>; 
        let crewCount = <?php echo $crewCount + 100; ?>;

        let trailerCount = 0;
        function addTrailerRow(lang = '', url = '') {
            trailerCount++;
            const container = document.getElementById('trailer-fields-container');
            const row = document.createElement('div');
            row.className = "trailer-row flex items-center gap-3 border-b border-gray-800 pb-3 last:border-none last:pb-0 mt-3";
            row.innerHTML = `
                <input type="text" name="trailer_languages[]" value="${lang}" placeholder="Language (e.g. Hindi, Tamil)" class="w-1/3 bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-2.5 text-xs focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
                <input type="url" name="trailer_urls[]" value="${url}" placeholder="Trailer Embed URL (https://www.youtube.com/embed/...)" class="flex-1 bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-2.5 text-xs focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
                <button type="button" onclick="this.closest('.trailer-row').remove()" class="text-gray-400 hover:text-red-500 transition-colors p-1" title="Remove Trailer">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            `;
            container.appendChild(row);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // On document load, pre-populate existing trailers
        document.addEventListener("DOMContentLoaded", function() {
            <?php foreach($movie_trailers as $tr): ?>
                addTrailerRow(
                    "<?php echo addslashes($tr['language']); ?>", 
                    "<?php echo addslashes($tr['trailer_url']); ?>"
                );
            <?php endforeach; ?>
        });

        function addCastMember() {
            castCount++;
            const container = document.getElementById('cast-fields-container');
            const row = document.createElement('div');
            
            row.className = "member-row relative border border-dashed border-gray-300 dark:border-borderMain rounded-xl p-5 bg-white dark:bg-[#121212] mt-4";
            
            row.innerHTML = `
                <button type="button" onclick="this.closest('.member-row').remove()" class="absolute top-5 right-5 text-gray-400 hover:text-red-500 transition-colors" title="Remove Member">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-4">Add Cast Member</p>
                
                <div class="flex flex-col sm:flex-row gap-5 items-start sm:items-center">
                    <div class="shrink-0">
                        <input type="file" id="cast-img-${castCount}" name="cast_images[]" class="hidden" accept="image/*" onchange="updateCastCrewImageName(this)">
                        <label for="cast-img-${castCount}" class="w-16 h-16 rounded-full bg-gray-100 dark:bg-[#222222] border border-gray-200 dark:border-transparent flex items-center justify-center cursor-pointer hover:bg-gray-200 dark:hover:bg-[#333333] transition-colors shadow-sm">
                            <i data-lucide="upload" class="w-5 h-5 text-gray-400"></i>
                        </label>
                    </div>
                    <div class="flex-1 space-y-3 w-full pr-8 sm:pr-0">
                        <input type="text" name="cast_names[]" placeholder="Actor/Actress name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-500 transition-colors">
                        <input type="text" name="cast_characters[]" placeholder="Character name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-500 transition-colors">
                    </div>
                </div>
            `;
            
            container.appendChild(row);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function addCrewMember() {
            crewCount++;
            const container = document.getElementById('crew-fields-container');
            const row = document.createElement('div');
            
            row.className = "member-row relative border border-dashed border-gray-300 dark:border-borderMain rounded-xl p-5 bg-white dark:bg-[#121212] mt-4";
            
            row.innerHTML = `
                <button type="button" onclick="this.closest('.member-row').remove()" class="absolute top-5 right-5 text-gray-400 hover:text-red-500 transition-colors" title="Remove Member">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-4">Add Crew Member</p>
                
                <div class="flex flex-col sm:flex-row gap-5 items-start sm:items-center">
                    <div class="shrink-0">
                        <input type="file" id="crew-img-${crewCount}" name="crew_images[]" class="hidden" accept="image/*" onchange="updateCastCrewImageName(this)">
                        <label for="crew-img-${crewCount}" class="w-16 h-16 rounded-full bg-gray-100 dark:bg-[#222222] border border-gray-200 dark:border-transparent flex items-center justify-center cursor-pointer hover:bg-gray-200 dark:hover:bg-[#333333] transition-colors shadow-sm">
                            <i data-lucide="upload" class="w-5 h-5 text-gray-400"></i>
                        </label>
                    </div>
                    <div class="flex-1 space-y-3 w-full pr-8 sm:pr-0">
                        <input type="text" name="crew_names[]" placeholder="Crew member name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-500 transition-colors">
                        <input type="text" name="crew_roles[]" placeholder="Role (e.g. Cinematographer, Editor)" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-500 transition-colors">
                    </div>
                </div>
            `;
            
            container.appendChild(row);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // Add a visual green checkmark if a user changes the image
        function updateCastCrewImageName(input) {
            const label = input.nextElementSibling;
            if (input.files && input.files[0]) {
                label.classList.remove('bg-gray-100', 'dark:bg-[#222222]', 'border-gray-200');
                label.classList.add('bg-green-50', 'dark:bg-green-950/30', 'border-green-500');
                label.innerHTML = `<i data-lucide="check" class="w-5 h-5 text-green-500"></i>`;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }
        // Auto-detect status from release date
        const editReleaseDateInput = document.getElementById('edit-release-date');
        const editStatusHidden = document.getElementById('edit-status-hidden');
        const editStatusBadge = document.getElementById('edit-status-badge');

        function updateEditStatusFromDate() {
            const dateStr = editReleaseDateInput.value.trim();
            if (!dateStr) {
                editStatusBadge.textContent = 'Enter release date to detect';
                editStatusBadge.className = 'w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder rounded-lg p-3 text-sm font-bold transition-colors text-gray-400 dark:text-gray-500';
                return;
            }
            const parsed = new Date(dateStr);
            if (isNaN(parsed.getTime())) {
                editStatusBadge.textContent = 'Invalid date format';
                editStatusBadge.className = 'w-full bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 text-sm font-bold transition-colors text-red-500';
                return;
            }
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            parsed.setHours(0, 0, 0, 0);

            if (parsed <= today) {
                editStatusHidden.value = 'Now Showing';
                editStatusBadge.textContent = '● Now Showing';
                editStatusBadge.className = 'w-full bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-3 text-sm font-bold transition-colors text-emerald-600 dark:text-emerald-400';
            } else {
                editStatusHidden.value = 'Coming Soon';
                editStatusBadge.textContent = '● Coming Soon';
                editStatusBadge.className = 'w-full bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-sm font-bold transition-colors text-blue-600 dark:text-blue-400';
            }
        }

        if (editReleaseDateInput) {
            editReleaseDateInput.addEventListener('input', updateEditStatusFromDate);
            editReleaseDateInput.addEventListener('change', updateEditStatusFromDate);
            updateEditStatusFromDate();
        }
    </script>
</body>
</html>

