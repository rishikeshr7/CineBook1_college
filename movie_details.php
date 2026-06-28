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

// Check if any trailers exist (default or multi-language)
$has_trailers = !empty($movie['trailer_url']);
if (!$has_trailers) {
    $tr_check = $conn->prepare("SELECT COUNT(*) FROM movie_trailers WHERE movie_id = ?");
    $tr_check->bind_param("i", $movie_id);
    $tr_check->execute();
    $tr_check->bind_result($tr_count);
    $tr_check->fetch();
    $tr_check->close();
    if ($tr_count > 0) {
        $has_trailers = true;
    }
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

// Format Data
$poster_src = !empty($movie['poster_image']) ? 'admin/' . htmlspecialchars($movie['poster_image']) : 'https://via.placeholder.com/400x600?text=No+Poster';
$release_date = date('F j, Y', strtotime($movie['release_date']));
$genres = explode(',', $movie['genre']);
$formats = explode(',', $movie['formats']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - CineBook</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bgMain: '#0a0a0a',
                        bgCard: '#121212',
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
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        .hero-blur-bg {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url('<?php echo $poster_src; ?>');
            background-size: cover;
            background-position: center 20%;
            filter: blur(40px);
            opacity: 0.15;
            z-index: -1;
        }

        /* Bulletproof CSS to enforce theme icon visibility */
        html:not(.dark) #theme-icon-moon { display: block !important; }
        html:not(.dark) #theme-icon-sun { display: none !important; }
        html.dark #theme-icon-moon { display: none !important; }
        html.dark #theme-icon-sun { display: block !important; }
    </style>
</head>
<body class="bg-white dark:bg-bgMain text-gray-900 dark:text-gray-100 font-sans flex flex-col min-h-screen transition-colors duration-300">

    <?php include("header.php"); ?>

    <div class="relative w-full overflow-hidden border-b border-gray-200 dark:border-gray-800 pb-10">
        <div class="hero-blur-bg dark:opacity-20 opacity-10"></div>
        
        <div class="max-w-6xl mx-auto px-6 pt-16 flex flex-col md:flex-row gap-10 relative z-10">
            <div class="shrink-0 mx-auto md:mx-0 w-[260px] md:w-[320px]">
                <img src="<?php echo $poster_src; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-auto rounded-xl shadow-2xl object-cover border border-gray-200 dark:border-gray-800">
            </div>

            <div class="flex-1 flex flex-col justify-center space-y-5">
                <div class="flex justify-between items-start">
                    <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-gray-900 dark:text-white">
                        <?php echo htmlspecialchars($movie['title']); ?>
                    </h1>
                    <button class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                        <i data-lucide="heart" class="w-6 h-6"></i>
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-sm font-medium text-gray-600 dark:text-gray-400">
                    <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded font-bold text-xs border border-gray-300 dark:border-gray-700">
                        <?php echo htmlspecialchars($movie['certification']); ?>
                    </span>
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-4 h-4"></i> <?php echo $release_date; ?>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="clock" class="w-4 h-4"></i> <?php echo htmlspecialchars($movie['duration']); ?>
                    </div>
                    <div class="flex gap-2">
                        <?php foreach($genres as $genre): ?>
                            <span class="text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars(trim($genre)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex items-center gap-4 py-1">
                    <div class="flex items-center gap-1">
                        <?php 
                            $rating10 = (float)$movie['rating'];
                            $stars5 = round($rating10 / 2);
                            for ($i = 1; $i <= 5; $i++): 
                                if ($i <= $stars5):
                        ?>
                            <svg class="w-5 h-5 text-brand fill-current" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <?php else: ?>
                            <svg class="w-5 h-5 text-gray-300 dark:text-gray-700 fill-current" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <?php 
                                endif; 
                            endfor; 
                        ?>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white"><?php echo $rating10; ?>/10</span>
                    <div class="flex items-center gap-1.5 text-gray-500 text-sm">
                        <i data-lucide="heart" class="w-4 h-4"></i> 12,340 likes
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 pt-2">
                    <a href="book_tickets.php?id=<?php echo $movie['id']; ?>" class="px-8 py-3 bg-brand text-black font-bold rounded-lg hover:bg-yellow-500 transition-colors shadow-sm">
                        Book Tickets
                    </a>
                    <?php if($has_trailers): ?>
                    <a href="watch_trailer.php?id=<?php echo $movie['id']; ?>" class="px-8 py-3 bg-white dark:bg-transparent border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white font-bold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors flex items-center gap-2">
                        <i data-lucide="play" class="w-5 h-5"></i> Watch Trailer
                    </a>
                    <?php endif; ?>
                </div>

                <div class="pt-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Synopsis</h3>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed text-sm md:text-base">
                        <?php echo nl2br(htmlspecialchars($movie['synopsis'])); ?>
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-6 pt-4">
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Director</span>
                        <span class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="user" class="w-4 h-4 text-brand"></i>
                            <?php echo htmlspecialchars($movie['director']); ?>
                        </span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Languages</span>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($movie['language']); ?>
                        </span>
                    </div>
                </div>

                <div class="pt-2">
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mb-2">Formats</span>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach($formats as $format): if(!empty(trim($format))): ?>
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded text-xs font-medium">
                                <?php echo htmlspecialchars(trim($format)); ?>
                            </span>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-6 py-12 w-full space-y-12 flex-1">
        
        <section>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Cast</h2>
            <div class="flex overflow-x-auto hide-scrollbar gap-6 pb-4">
                <?php if(empty($cast_members)): ?>
                    <p class="text-gray-500">No cast information available.</p>
                <?php else: ?>
                    <?php foreach($cast_members as $cast): 
                        $img = !empty($cast['profile_image']) ? 'admin/'.htmlspecialchars($cast['profile_image']) : 'https://via.placeholder.com/150?text=No+Image';
                    ?>
                    <div class="flex flex-col items-center w-[120px] shrink-0 text-center group">
                        <div class="w-28 h-28 rounded-full overflow-hidden mb-3 border-4 border-transparent group-hover:border-gray-200 dark:group-hover:border-gray-700 transition-all">
                            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($cast['actor_name']); ?>" class="w-full h-full object-cover">
                        </div>
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white leading-tight">
                            <?php echo htmlspecialchars($cast['actor_name']); ?>
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <?php echo htmlspecialchars($cast['character_name']); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Crew</h2>
            <div class="flex overflow-x-auto hide-scrollbar gap-6 pb-4">
                <?php if(empty($crew_members)): ?>
                    <p class="text-gray-500">No crew information available.</p>
                <?php else: ?>
                    <?php foreach($crew_members as $crew): 
                        $img = !empty($crew['profile_image']) ? 'admin/'.htmlspecialchars($crew['profile_image']) : 'https://via.placeholder.com/150?text=No+Image';
                    ?>
                    <div class="flex flex-col items-center w-[120px] shrink-0 text-center group">
                        <div class="w-28 h-28 rounded-full overflow-hidden mb-3 border-4 border-transparent group-hover:border-gray-200 dark:group-hover:border-gray-700 transition-all">
                            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($crew['crew_name']); ?>" class="w-full h-full object-cover">
                        </div>
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white leading-tight">
                            <?php echo htmlspecialchars($crew['crew_name']); ?>
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <?php echo htmlspecialchars($crew['role']); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="message-square" class="w-6 h-6 text-gray-400"></i> User Reviews
                </h2>
                <button class="px-4 py-2 bg-brand text-black font-bold rounded-lg hover:bg-yellow-500 transition-colors text-sm shadow-sm">
                    Write a Review
                </button>
            </div>
            
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-10 flex flex-col items-center justify-center text-center bg-gray-50 dark:bg-bgCard">
                <div class="w-16 h-16 bg-gray-200 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4 text-gray-400">
                    <i data-lucide="message-circle" class="w-8 h-8"></i>
                </div>
                <p class="text-gray-500 dark:text-gray-400 font-medium">No reviews yet. Be the first to review!</p>
            </div>
        </section>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>