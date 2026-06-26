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

$genres = explode(',', $movie['genre']);

// Fetch available dates for this movie
$dates_query = $conn->prepare("SELECT DISTINCT show_date FROM showtimes WHERE movie_id = ? AND show_date >= CURRENT_DATE ORDER BY show_date ASC LIMIT 14");
$dates_query->bind_param("i", $movie_id);
$dates_query->execute();
$available_dates_result = $dates_query->get_result();
$available_dates = [];
while($d = $available_dates_result->fetch_assoc()) {
    $available_dates[] = $d['show_date'];
}

// Determine selected date
$selected_date = isset($_GET['date']) ? $_GET['date'] : (count($available_dates) > 0 ? $available_dates[0] : date('Y-m-d'));

// Fetch available languages for this movie
$lang_query = $conn->prepare("SELECT DISTINCT language FROM showtimes WHERE movie_id = ? AND show_date >= CURRENT_DATE");
$lang_query->bind_param("i", $movie_id);
$lang_query->execute();
$available_langs_result = $lang_query->get_result();
$available_langs = [];
while($l = $available_langs_result->fetch_assoc()) {
    $available_langs[] = $l['language'];
}

// Determine selected language
$selected_lang = isset($_GET['lang']) ? $_GET['lang'] : 'All';

// Fetch Showtimes for the selected date and language
$shows_sql = "SELECT * FROM showtimes WHERE movie_id = ? AND show_date = ?";
if ($selected_lang !== 'All') {
    $shows_sql .= " AND language = ?";
}
$shows_sql .= " ORDER BY theater_id ASC, format ASC, show_time ASC";

$shows_stmt = $conn->prepare($shows_sql);
if ($selected_lang !== 'All') {
    $shows_stmt->bind_param("iss", $movie_id, $selected_date, $selected_lang);
} else {
    $shows_stmt->bind_param("is", $movie_id, $selected_date);
}
$shows_stmt->execute();
$shows_result = $shows_stmt->get_result();

$theaters = [];
while($show = $shows_result->fetch_assoc()) {
    $t_id = $show['theater_id'];
    $fmt = $show['format'];
    
    if(!isset($theaters[$t_id])) {
        $theaters[$t_id] = [
            'name' => $t_id,
            'city' => $show['city'] ?? 'Mumbai',
            'formats' => []
        ];
    }
    if(!isset($theaters[$t_id]['formats'][$fmt])) {
        $theaters[$t_id]['formats'][$fmt] = [];
    }
    $theaters[$t_id]['formats'][$fmt][] = $show;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tickets - <?php echo htmlspecialchars($movie['title']); ?></title>
    
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
                        borderMain: '#262626'
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
        
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Enforce theme icon visibility globally */
        html:not(.dark) #theme-icon-moon { display: block !important; }
        html:not(.dark) #theme-icon-sun { display: none !important; }
        html.dark #theme-icon-moon { display: none !important; }
        html.dark #theme-icon-sun { display: block !important; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-bgMain text-gray-900 dark:text-gray-100 font-sans flex flex-col min-h-screen transition-colors duration-300 relative pb-24">

    <?php include("header.php"); ?>

    <div class="max-w-6xl mx-auto px-6 py-10 w-full flex-1">
        
        <div class="mb-8 border-b border-gray-200 dark:border-borderMain pb-6">
            <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-gray-900 dark:text-white mb-4 transition-colors">
                <?php echo htmlspecialchars($movie['title']); ?>
            </h1>
            <div class="flex flex-wrap items-center gap-3">
                <?php foreach($genres as $genre): if(!empty(trim($genre))): ?>
                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-xs font-semibold border border-gray-200 dark:border-gray-700 transition-colors">
                        <?php echo htmlspecialchars(trim($genre)); ?>
                    </span>
                <?php endif; endforeach; ?>
                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-xs font-semibold border border-gray-200 dark:border-gray-700 transition-colors">
                    <?php echo htmlspecialchars($movie['certification']); ?>
                </span>
                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-xs font-semibold border border-gray-200 dark:border-gray-700 transition-colors">
                    <?php echo htmlspecialchars($movie['duration']); ?>
                </span>
            </div>
        </div>

        <div class="mb-8">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3 transition-colors">Select Date</h3>
            <div class="flex overflow-x-auto hide-scrollbar gap-3 pb-2">
                <?php foreach($available_dates as $date): 
                    $timestamp = strtotime($date);
                    $dayName = date('D', $timestamp);
                    $dayNum = date('j', $timestamp);
                    $monthShort = date('M', $timestamp);
                    
                    $isToday = ($date === date('Y-m-d'));
                    $isTomorrow = ($date === date('Y-m-d', strtotime('+1 day')));
                    
                    $displayBottom = $isToday ? "Today" : ($isTomorrow ? "Tomorrow" : "$monthShort $dayNum");
                    
                    $isActive = ($date === $selected_date);
                    
                    $url = "book_tickets.php?id=$movie_id&date=$date&lang=$selected_lang";
                    
                    $classes = $isActive 
                        ? "border-brand text-brand bg-yellow-50 dark:bg-transparent shadow-sm dark:shadow-none" 
                        : "border-gray-200 dark:border-borderMain bg-white dark:bg-transparent text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-gray-300 dark:hover:border-gray-500";
                ?>
                    <a href="<?php echo $url; ?>" class="flex flex-col items-center justify-center min-w-[70px] py-2 rounded-lg border <?php echo $classes; ?> transition-colors shrink-0">
                        <span class="text-xs uppercase font-medium mb-1"><?php echo $dayName; ?></span>
                        <span class="text-sm font-bold"><?php echo $displayBottom; ?></span>
                    </a>
                <?php endforeach; ?>
                
                <?php if(empty($available_dates)): ?>
                    <p class="text-gray-500 text-sm">No upcoming showtimes available.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-10">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3 transition-colors">Select Language</h3>
            <div class="flex overflow-x-auto hide-scrollbar gap-3 pb-2">
                <a href="book_tickets.php?id=<?php echo $movie_id; ?>&date=<?php echo $selected_date; ?>&lang=All" 
                   class="px-5 py-2 rounded-full border text-sm font-semibold transition-colors <?php echo ($selected_lang === 'All') ? 'border-brand text-brand bg-yellow-50 dark:bg-transparent shadow-sm dark:shadow-none' : 'border-gray-200 dark:border-borderMain bg-white dark:bg-transparent text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-gray-300 dark:hover:border-gray-500'; ?>">
                    All
                </a>
                
                <?php foreach($available_langs as $lang): 
                    $isActive = ($selected_lang === $lang);
                    $classes = $isActive ? "border-brand text-brand bg-yellow-50 dark:bg-transparent shadow-sm dark:shadow-none" : "border-gray-200 dark:border-borderMain bg-white dark:bg-transparent text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-gray-300 dark:hover:border-gray-500";
                ?>
                    <a href="book_tickets.php?id=<?php echo $movie_id; ?>&date=<?php echo $selected_date; ?>&lang=<?php echo urlencode($lang); ?>" 
                       class="px-5 py-2 rounded-full border text-sm font-semibold transition-colors <?php echo $classes; ?>">
                        <?php echo htmlspecialchars($lang); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="space-y-6">
            <?php if(empty($theaters)): ?>
                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-10 text-center flex flex-col items-center justify-center shadow-sm transition-colors">
                    <i data-lucide="calendar-x" class="w-12 h-12 text-gray-400 dark:text-gray-600 mb-3"></i>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-300 mb-1">No Shows Available</h3>
                    <p class="text-gray-500 text-sm">Try selecting a different date or language.</p>
                </div>
            <?php else: ?>
                
                <?php foreach($theaters as $theater_name => $theater_data): ?>
                    <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-6 shadow-sm transition-colors">
                        
                        <div class="flex flex-col md:flex-row justify-between md:items-start gap-4 mb-6">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2 transition-colors"><?php echo htmlspecialchars($theater_name); ?></h2>
                                <div class="flex flex-col gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                        <?php echo htmlspecialchars($theater_data['city']); ?>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-brand font-medium">
                                        <i data-lucide="navigation" class="w-3.5 h-3.5"></i>
                                        2.5 km
                                    </div>
                                </div>
                                
                                <div class="flex flex-wrap gap-2 mt-4">
                                    <span class="flex items-center gap-1 px-2 py-1 bg-yellow-50 dark:bg-[#1a1a1a] rounded text-[10px] text-yellow-700 dark:text-yellow-600 border border-yellow-200 dark:border-yellow-900/30 font-semibold uppercase transition-colors">
                                        <i data-lucide="star" class="w-3 h-3"></i> IMAX
                                    </span>
                                    <span class="flex items-center gap-1 px-2 py-1 bg-yellow-50 dark:bg-[#1a1a1a] rounded text-[10px] text-yellow-700 dark:text-yellow-600 border border-yellow-200 dark:border-yellow-900/30 font-semibold uppercase transition-colors">
                                        <i data-lucide="star" class="w-3 h-3"></i> Dolby Atmos
                                    </span>
                                    <span class="flex items-center gap-1 px-2 py-1 bg-yellow-50 dark:bg-[#1a1a1a] rounded text-[10px] text-yellow-700 dark:text-yellow-600 border border-yellow-200 dark:border-yellow-900/30 font-semibold uppercase transition-colors">
                                        <i data-lucide="star" class="w-3 h-3"></i> Parking
                                    </span>
                                    <span class="flex items-center gap-1 px-2 py-1 bg-yellow-50 dark:bg-[#1a1a1a] rounded text-[10px] text-yellow-700 dark:text-yellow-600 border border-yellow-200 dark:border-yellow-900/30 font-semibold uppercase transition-colors">
                                        <i data-lucide="star" class="w-3 h-3"></i> Food Court
                                    </span>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                8 Screens
                            </div>
                        </div>

                        <div class="space-y-6">
                            <?php foreach($theater_data['formats'] as $format_name => $shows): ?>
                                <div>
                                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-3 uppercase tracking-wider transition-colors"><?php echo htmlspecialchars($format_name); ?></h4>
                                    <div class="flex flex-wrap gap-4">
                                        <?php foreach($shows as $show): 
                                            $time = date('H:i', strtotime($show['show_time']));
                                            $seats_available = (int)$show['total_seats'];
                                            $price = number_format($show['price_regular'], 0);
                                        ?>
                                            <button type="button" 
                                                data-showtime-id="<?php echo $show['id']; ?>"
                                                data-time="<?php echo $time; ?>"
                                                data-format="<?php echo htmlspecialchars($format_name); ?>"
                                                data-lang="<?php echo htmlspecialchars($show['language']); ?>"
                                                data-theater="<?php echo htmlspecialchars($theater_name); ?>"
                                                onclick="selectShowtime(this)"
                                                class="showtime-btn border border-gray-200 dark:border-borderMain bg-white dark:bg-transparent hover:border-brand dark:hover:border-brand rounded-lg p-3 flex flex-col min-w-[130px] transition-all text-left group cursor-pointer relative overflow-hidden">
                                                
                                                <div class="flex items-center gap-1.5 time-text text-gray-900 dark:text-white font-bold text-lg mb-1 group-hover:text-yellow-600 dark:group-hover:text-brand transition-colors">
                                                    <i data-lucide="clock" class="w-4 h-4 time-icon text-gray-400 dark:text-gray-500 group-hover:text-yellow-600 dark:group-hover:text-brand transition-colors"></i>
                                                    <?php echo $time; ?>
                                                </div>
                                                <div class="text-[10px] font-medium text-gray-500 dark:text-gray-400 mb-1 transition-colors">
                                                    <?php echo htmlspecialchars($show['language']); ?> - <?php echo htmlspecialchars($show['screen_id']); ?>
                                                </div>
                                                <div class="flex items-center gap-1 text-[10px]">
                                                    <span class="text-emerald-600 dark:text-emerald-500 font-bold"><?php echo $seats_available; ?> available</span>
                                                    <span class="text-gray-500">from ₹<?php echo $price; ?></span>
                                                </div>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
                
            <?php endif; ?>
        </div>

    </div>

    <div id="booking-banner" class="fixed bottom-0 left-0 right-0 bg-white dark:bg-[#121212] border-t border-gray-200 dark:border-[#262626] shadow-[0_-4px_20px_rgba(0,0,0,0.05)] dark:shadow-[0_-4px_20px_rgba(0,0,0,0.5)] transform translate-y-full transition-transform duration-300 z-50">
        <div class="max-w-6xl mx-auto px-6 py-4 flex flex-col md:flex-row items-center justify-between gap-4 md:gap-0">
            
            <div class="flex flex-col text-center md:text-left w-full md:w-auto">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Selected Showtime</span>
                <span id="banner-details" class="text-sm md:text-base font-bold text-gray-900 dark:text-white">
                    --
                </span>
                <span id="banner-theater" class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">
                    --
                </span>
            </div>
            
            <div class="flex items-center gap-4 w-full md:w-auto justify-end">
                <button type="button" onclick="closeBanner()" class="text-sm font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors px-2 hidden sm:block">
                    Back
                </button>
                <form action="seat_selection.php" method="GET" class="m-0 p-0 w-full md:w-auto">
                    <input type="hidden" name="showtime_id" id="banner-showtime-id" value="">
                    <button type="submit" class="w-full md:w-auto px-8 py-3 bg-brand text-black text-sm font-bold rounded-lg hover:bg-yellow-500 transition-colors shadow-sm">
                        Select Seats
                    </button>
                </form>
            </div>

        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Handle Showtime Selection and Bottom Banner
        function selectShowtime(btn) {
            // 1. Reset all buttons to default state
            document.querySelectorAll('.showtime-btn').forEach(el => {
                // Remove active classes
                el.classList.remove('border-brand', 'dark:border-brand', 'bg-yellow-50', 'dark:bg-brand/10', 'ring-1', 'ring-brand');
                // Add default classes
                el.classList.add('border-gray-200', 'dark:border-borderMain', 'bg-white', 'dark:bg-transparent');
                
                // Reset text/icon colors
                el.querySelector('.time-text').classList.remove('text-yellow-600', 'dark:text-brand');
                el.querySelector('.time-text').classList.add('text-gray-900', 'dark:text-white');
                el.querySelector('.time-icon').classList.remove('text-yellow-600', 'dark:text-brand');
                el.querySelector('.time-icon').classList.add('text-gray-400', 'dark:text-gray-500');
            });

            // 2. Set Active state on clicked button
            btn.classList.remove('border-gray-200', 'dark:border-borderMain', 'bg-white', 'dark:bg-transparent');
            btn.classList.add('border-brand', 'dark:border-brand', 'bg-yellow-50', 'dark:bg-brand/10', 'ring-1', 'ring-brand');
            
            btn.querySelector('.time-text').classList.remove('text-gray-900', 'dark:text-white');
            btn.querySelector('.time-text').classList.add('text-yellow-600', 'dark:text-brand');
            btn.querySelector('.time-icon').classList.remove('text-gray-400', 'dark:text-gray-500');
            btn.querySelector('.time-icon').classList.add('text-yellow-600', 'dark:text-brand');

            // 3. Extract data attributes
            const time = btn.getAttribute('data-time');
            const format = btn.getAttribute('data-format');
            const lang = btn.getAttribute('data-lang');
            const theater = btn.getAttribute('data-theater');
            const showtimeId = btn.getAttribute('data-showtime-id');

            // 4. Populate Banner
            document.getElementById('banner-details').innerText = `${time} • ${format} • ${lang}`;
            document.getElementById('banner-theater').innerText = theater;
            document.getElementById('banner-showtime-id').value = showtimeId;

            // 5. Slide up the banner
            const banner = document.getElementById('booking-banner');
            banner.classList.remove('translate-y-full');
            banner.classList.add('translate-y-0');
        }

        function closeBanner() {
            // Hide banner
            const banner = document.getElementById('booking-banner');
            banner.classList.add('translate-y-full');
            banner.classList.remove('translate-y-0');

            // Remove active highlight from all buttons
            document.querySelectorAll('.showtime-btn').forEach(el => {
                el.classList.remove('border-brand', 'dark:border-brand', 'bg-yellow-50', 'dark:bg-brand/10', 'ring-1', 'ring-brand');
                el.classList.add('border-gray-200', 'dark:border-borderMain', 'bg-white', 'dark:bg-transparent');
                
                el.querySelector('.time-text').classList.remove('text-yellow-600', 'dark:text-brand');
                el.querySelector('.time-text').classList.add('text-gray-900', 'dark:text-white');
                el.querySelector('.time-icon').classList.remove('text-yellow-600', 'dark:text-brand');
                el.querySelector('.time-icon').classList.add('text-gray-400', 'dark:text-gray-500');
            });
        }
    </script>
</body>
</html>