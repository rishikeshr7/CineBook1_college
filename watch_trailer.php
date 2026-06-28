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
    header("Location: index.php");
    exit();
}

// Fetch additional trailers
$trailer_stmt = $conn->prepare("SELECT * FROM movie_trailers WHERE movie_id = ? ORDER BY id ASC");
$trailer_stmt->bind_param("i", $movie_id);
$trailer_stmt->execute();
$movie_trailers = $trailer_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$trailer_stmt->close();

// Helper to convert any YouTube URL format into a clean embed URL
function get_embed_url($url) {
    if (empty($url)) return '';
    if (strpos($url, '/embed/') !== false) {
        return $url;
    }
    
    $video_id = '';
    // Extract video ID from youtube watch, embed, or short link
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $url, $match)) {
        $video_id = $match[1];
    }
    
    if (!empty($video_id)) {
        return "https://www.youtube.com/embed/" . $video_id . "?autoplay=1&rel=0";
    }
    return $url;
}

// Build the array of all available trailers
$trailers = [];

// 1. Add Default/Primary trailer
if (!empty($movie['trailer_url'])) {
    $default_lang = 'Default';
    if (!empty($movie['language'])) {
        $langs = explode(',', $movie['language']);
        $default_lang = trim($langs[0]);
    }
    $trailers[] = [
        'language' => $default_lang,
        'trailer_url' => get_embed_url($movie['trailer_url']),
        'is_default' => true
    ];
}

// 2. Add additional language trailers
foreach ($movie_trailers as $tr) {
    $trailers[] = [
        'language' => trim($tr['language']),
        'trailer_url' => get_embed_url($tr['trailer_url']),
        'is_default' => false
    ];
}

// Fetch Recommendations (Other movies currently showing or coming soon)
$rec_movies = [];
$rec_query = "SELECT * FROM movies WHERE id != ? ORDER BY rating DESC, release_date DESC LIMIT 6";
$rec_stmt = $conn->prepare($rec_query);
$rec_stmt->bind_param("i", $movie_id);
$rec_stmt->execute();
$rec_result = $rec_stmt->get_result();
while ($row = $rec_result->fetch_assoc()) {
    $rec_movies[] = $row;
}
$rec_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch Trailer - <?php echo htmlspecialchars($movie['title']); ?> - CineBook</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bgMain: '#0B0B0C',
                        bgCard: '#131315',
                        brand: '#F5C518',
                        textMuted: '#98989A',
                        borderMain: '#222225'
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-bgMain text-gray-900 dark:text-gray-100 font-sans flex flex-col min-h-screen transition-colors duration-300">

    <?php include("header.php"); ?>

    <main class="flex-1 w-full max-w-6xl mx-auto px-4 md:px-8 py-8">
        
        <!-- Main Layout Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left & Center: Player & Info -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Video Player Container -->
                <div class="relative pb-[56.25%] h-0 rounded-2xl overflow-hidden shadow-2xl bg-black border border-gray-200 dark:border-borderMain transition-colors">
                    <?php if (!empty($trailers)): ?>
                        <iframe 
                            id="trailer-iframe"
                            class="absolute top-0 left-0 w-full h-full"
                            src="<?php echo $trailers[0]['trailer_url']; ?>" 
                            title="<?php echo htmlspecialchars($movie['title']); ?> Trailer"
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                            allowfullscreen>
                        </iframe>
                    <?php else: ?>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 dark:text-gray-600 bg-gray-900">
                            <i data-lucide="video-off" class="w-16 h-16 mb-3"></i>
                            <p class="text-sm font-semibold">No trailer matches found for this movie</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Trailer Title & Language Bar -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-200 dark:border-borderMain pb-6">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white transition-colors">
                            <?php echo htmlspecialchars($movie['title']); ?> <span class="text-gray-400 font-normal">Trailer</span>
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-textMuted mt-1">Select language below to play corresponding trailer audio/video</p>
                    </div>

                    <!-- Multi-Language Selector Pills -->
                    <?php if (count($trailers) > 1): ?>
                        <div class="flex items-center gap-2 overflow-x-auto hide-scrollbar self-start md:self-auto py-1">
                            <?php foreach ($trailers as $index => $tr): 
                                $isActive = ($index === 0);
                                $classes = $isActive 
                                    ? "bg-[#F5C518] text-black border-[#F5C518]" 
                                    : "bg-transparent text-gray-500 dark:text-textMuted border-gray-300 dark:border-gray-700 hover:text-gray-900 dark:hover:text-white hover:border-gray-500 dark:hover:border-gray-500";
                            ?>
                                <button 
                                    type="button" 
                                    onclick="switchTrailer('<?php echo htmlspecialchars($tr['trailer_url']); ?>', this)"
                                    class="lang-pill px-4 py-2 border rounded-full text-xs font-bold transition-all uppercase tracking-wider shrink-0 <?php echo $classes; ?>">
                                    <?php echo htmlspecialchars($tr['language']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Movie Detailed Card -->
                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-2xl p-6 md:p-8 shadow-sm transition-colors space-y-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <span class="px-2 py-1 bg-yellow-50 dark:bg-yellow-950/20 text-[#F5C518] text-xs font-bold border border-yellow-200 dark:border-yellow-900/30 rounded mr-2 uppercase tracking-wide">
                                <?php echo htmlspecialchars($movie['certification']); ?>
                            </span>
                            <span class="text-sm font-semibold text-gray-500 dark:text-textMuted uppercase tracking-wide">
                                <?php echo htmlspecialchars($movie['duration']); ?> • <?php echo htmlspecialchars($movie['genre']); ?>
                            </span>
                        </div>
                        
                        <!-- Rating -->
                        <div class="flex items-center gap-1.5 bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-200 dark:border-yellow-900/30 px-3.5 py-1.5 rounded-xl">
                            <i data-lucide="star" class="w-4 h-4 text-[#F5C518] fill-current"></i>
                            <span class="text-sm font-extrabold text-gray-900 dark:text-white"><?php echo htmlspecialchars($movie['rating']); ?></span>
                            <span class="text-xs text-gray-400 font-semibold">/ 10</span>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 dark:border-borderMain pt-6">
                        <h3 class="text-base font-extrabold uppercase tracking-wide text-gray-900 dark:text-white mb-2.5">About the Movie</h3>
                        <p class="text-sm text-gray-600 dark:text-textMuted leading-relaxed">
                            <?php echo htmlspecialchars($movie['synopsis'] ?: 'No synopsis available.'); ?>
                        </p>
                    </div>

                    <div class="border-t border-gray-100 dark:border-borderMain pt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-textMuted">
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-400 dark:text-textMuted/60 mb-0.5 tracking-wider">Director</p>
                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($movie['director'] ?: 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-400 dark:text-textMuted/60 mb-0.5 tracking-wider">Release Date</p>
                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($movie['release_date'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Sidebar: Poster Card & CTA -->
            <div class="space-y-6">
                
                <!-- Poster Card -->
                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
                    <div class="aspect-[2/3] w-full bg-gray-100 dark:bg-gray-900 shadow-inner overflow-hidden relative">
                        <?php 
                        $poster_src = htmlspecialchars($movie['poster_image']);
                        if (strpos($poster_src, 'http') !== 0) {
                            $poster_src = 'admin/' . $poster_src;
                        }
                        ?>
                        <img src="<?php echo $poster_src; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?> Poster" class="w-full h-full object-cover">
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($movie['title']); ?></h2>
                            <p class="text-xs text-gray-400 dark:text-textMuted mt-0.5"><?php echo htmlspecialchars($movie['language']); ?></p>
                        </div>

                        <!-- CTA Book tickets button -->
                        <?php if ($movie['status'] === 'Now Showing'): ?>
                            <a href="book_tickets.php?id=<?php echo $movie['id']; ?>" class="w-full py-3 bg-[#F5C518] hover:bg-yellow-500 text-black font-extrabold rounded-xl transition-all duration-200 shadow-sm flex items-center justify-center gap-2 hover:scale-[1.01]">
                                <i data-lucide="ticket" class="w-4 h-4"></i> Book Tickets
                            </a>
                        <?php else: ?>
                            <button disabled class="w-full py-3 bg-gray-200 dark:bg-gray-800 text-gray-400 dark:text-gray-600 font-extrabold rounded-xl cursor-not-allowed flex items-center justify-center gap-2 text-sm uppercase tracking-wide">
                                <i data-lucide="calendar-clock" class="w-4 h-4"></i> Coming Soon
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>

        <!-- Recommendations Section -->
        <?php if (!empty($rec_movies)): ?>
            <div class="mt-16 border-t border-gray-200 dark:border-borderMain pt-10">
                <h2 class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-6 flex items-center gap-2.5">
                    <i data-lucide="sparkles" class="w-5 h-5 text-brand"></i> Trending Movies
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-5">
                    <?php foreach ($rec_movies as $rm): ?>
                        <a href="watch_trailer.php?id=<?php echo $rm['id']; ?>" class="group block space-y-2">
                            <div class="aspect-[2/3] w-full rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 shadow-inner relative group-hover:scale-[1.02] transition-transform duration-300">
                                <?php 
                                $rm_poster = htmlspecialchars($rm['poster_image']);
                                if (strpos($rm_poster, 'http') !== 0) {
                                    $rm_poster = 'admin/' . $rm_poster;
                                }
                                ?>
                                <img src="<?php echo $rm_poster; ?>" alt="<?php echo htmlspecialchars($rm['title']); ?>" class="w-full h-full object-cover">
                                
                                <!-- Play icon overlay -->
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <div class="w-10 h-10 bg-white/95 rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                        <i data-lucide="play" class="w-5 h-5 text-black fill-current ml-0.5"></i>
                                    </div>
                                </div>
                            </div>
                            <h3 class="text-xs font-bold text-gray-900 dark:text-white truncate group-hover:text-[#F5C518] transition-colors"><?php echo htmlspecialchars($rm['title']); ?></h3>
                            <div class="flex items-center gap-1 text-[10px] text-gray-400">
                                <i data-lucide="star" class="w-3 h-3 text-[#F5C518] fill-current"></i>
                                <span><?php echo htmlspecialchars($rm['rating']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Dynamic Trailer Switcher
        function switchTrailer(url, element) {
            const iframe = document.getElementById('trailer-iframe');
            if (iframe) {
                iframe.src = url;
            }
            
            // Reset active pills
            document.querySelectorAll('.lang-pill').forEach(btn => {
                btn.classList.remove('bg-[#F5C518]', 'text-black', 'border-[#F5C518]');
                btn.classList.add('bg-transparent', 'text-gray-500', 'dark:text-textMuted', 'border-gray-300', 'dark:border-gray-700', 'hover:text-gray-900', 'hover:border-gray-500', 'dark:hover:text-white', 'dark:hover:border-gray-500');
            });
            
            // Set active clicked pill
            element.classList.remove('bg-transparent', 'text-gray-500', 'dark:text-textMuted', 'border-gray-300', 'dark:border-gray-700', 'hover:text-gray-900', 'hover:border-gray-500', 'dark:hover:text-white', 'dark:hover:border-gray-500');
            element.classList.add('bg-[#F5C518]', 'text-black', 'border-[#F5C518]');
        }
    </script>
</body>
</html>
