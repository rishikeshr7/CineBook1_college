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

// Calculate duration in minutes
$duration_str = $movie['duration'];
$duration_minutes = 120; // default
if (preg_match('/(?:(\d+)h)?\s*(?:(\d+)m)?/i', $duration_str, $matches)) {
    $m = 0;
    if (!empty($matches[1])) $m += intval($matches[1]) * 60;
    if (!empty($matches[2])) $m += intval($matches[2]);
    if ($m > 0) $duration_minutes = $m;
}

// Review Eligibility Check
$can_review = false;
$has_reviewed = false;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // 1. Check if they already reviewed
    $rev_check = $conn->prepare("SELECT id FROM movie_reviews WHERE movie_id = ? AND user_id = ?");
    $rev_check->bind_param("ii", $movie_id, $user_id);
    $rev_check->execute();
    if ($rev_check->get_result()->num_rows > 0) {
        $has_reviewed = true;
    }
    $rev_check->close();
    
    // 2. Check if they booked and 30 mins passed since movie ended
    if (!$has_reviewed) {
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
    }
}

// Fetch Reviews
$reviews = [];
$rev_stmt = $conn->prepare("
    SELECT r.*, u.fullname 
    FROM movie_reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.movie_id = ?
    ORDER BY r.created_at DESC
");
$rev_stmt->bind_param("i", $movie_id);
$rev_stmt->execute();
$rev_result = $rev_stmt->get_result();
while ($r = $rev_result->fetch_assoc()) {
    $reviews[] = $r;
}
$rev_stmt->close();
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
                <?php if ($has_reviewed): ?>
                    <span class="px-4 py-2 bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400 font-bold rounded-lg text-sm flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Reviewed
                    </span>
                <?php elseif ($can_review): ?>
                    <button onclick="openReviewModal()" class="px-4 py-2 bg-brand text-black font-bold rounded-lg hover:bg-yellow-500 transition-colors text-sm shadow-sm">
                        Write a Review
                    </button>
                <?php else: ?>
                    <button onclick="alert('You can only rate and review this movie 30 minutes after your booked show ends.')" class="px-4 py-2 bg-gray-200 text-gray-500 dark:bg-gray-800 dark:text-gray-400 font-bold rounded-lg text-sm shadow-sm opacity-70 cursor-not-allowed">
                        Write a Review
                    </button>
                <?php endif; ?>
            </div>
            
            <?php if(empty($reviews)): ?>
                <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-10 flex flex-col items-center justify-center text-center bg-gray-50 dark:bg-bgCard">
                    <div class="w-16 h-16 bg-gray-200 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4 text-gray-400">
                        <i data-lucide="message-circle" class="w-8 h-8"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No reviews yet. Be the first to review!</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach($reviews as $review): ?>
                        <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-5 bg-white dark:bg-bgCard">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($review['fullname']); ?></h4>
                                <div class="flex items-center gap-1 text-brand">
                                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                    <span class="font-bold text-sm text-gray-900 dark:text-white"><?php echo $review['rating']; ?>/10</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                            <p class="text-xs text-gray-400 mt-3"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>

    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center px-4">
        <div class="bg-white dark:bg-bgCard w-full max-w-lg rounded-2xl p-6 shadow-xl transform scale-95 opacity-0 transition-all duration-200" id="reviewModalContent">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Write a Review</h3>
                <button type="button" onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="submit_review.php" method="POST" class="space-y-4">
                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">Rating (1-10) <span class="text-red-500">*</span></label>
                    <input type="number" name="rating" min="1" max="10" required class="w-full p-3 bg-gray-50 dark:bg-[#1A1A1A] border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:border-brand">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">Review</label>
                    <textarea name="review_text" rows="4" class="w-full p-3 bg-gray-50 dark:bg-[#1A1A1A] border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:border-brand resize-none" placeholder="What did you think of the movie?"></textarea>
                </div>
                
                <button type="submit" class="w-full py-3 bg-brand text-black font-bold rounded-lg hover:bg-yellow-500 transition-colors">
                    Submit Review
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        function openReviewModal() {
            const modal = document.getElementById('reviewModal');
            const content = document.getElementById('reviewModalContent');
            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }
        
        function closeReviewModal() {
            const modal = document.getElementById('reviewModal');
            const content = document.getElementById('reviewModalContent');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }
    </script>
</body>
</html>