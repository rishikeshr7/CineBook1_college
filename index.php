<?php
session_start();
require_once 'dbconnect.php';

// 1. Get the current selected city (falls back to Mumbai)
$selected_city = isset($_GET['city']) ? trim($_GET['city']) : (isset($_SESSION['user_city']) ? $_SESSION['user_city'] : 'Mumbai');
$_SESSION['user_city'] = $selected_city;

$now_showing = [];
$coming_soon = [];

if (isset($conn)) {
    // 2. Fetch "Now Showing" movies specifically playing in the selected city
    $ns_sql = "
        SELECT DISTINCT m.* FROM movies m 
        INNER JOIN showtimes s ON m.id = s.movie_id 
        WHERE m.status = 'Now Showing' AND s.city = ? AND s.show_date >= CURRENT_DATE
        ORDER BY m.created_at DESC
    ";
    $ns_stmt = $conn->prepare($ns_sql);
    if ($ns_stmt) {
        $ns_stmt->bind_param("s", $selected_city);
        $ns_stmt->execute();
        $ns_result = $ns_stmt->get_result();
        while ($row = $ns_result->fetch_assoc()) {
            $now_showing[] = $row;
        }
        $ns_stmt->close();
    }

    // 3. Fetch ALL "Coming Soon" movies globally
    $cs_result = $conn->query("SELECT * FROM movies WHERE status = 'Coming Soon' ORDER BY created_at DESC");
    if ($cs_result) {
        while ($row = $cs_result->fetch_assoc()) {
            $coming_soon[] = $row;
        }
    }
}

// 4. Extract unique genres from all movies
$all_genres = [];
if (isset($conn)) {
    $genres_res = $conn->query("SELECT genre FROM movies WHERE genre IS NOT NULL AND genre != ''");
    if ($genres_res) {
        while($r = $genres_res->fetch_assoc()) {
            $parts = explode(',', $r['genre']);
            foreach($parts as $p) {
                $p = trim($p);
                if (!empty($p) && !in_array($p, $all_genres)) {
                    $all_genres[] = $p;
                }
            }
        }
    }
}
sort($all_genres);

// 5. Define 22 scheduled Indian languages + English
$all_languages = [
    'Assamese', 'Bengali', 'Bodo', 'Dogri', 'English', 'Gujarati', 'Hindi', 
    'Kannada', 'Kashmiri', 'Konkani', 'Maithili', 'Malayalam', 'Manipuri', 
    'Marathi', 'Nepali', 'Odia', 'Punjabi', 'Sanskrit', 'Santali', 'Sindhi', 
    'Tamil', 'Telugu', 'Urdu'
];

/* ============================================================
   HERO CAROUSEL SOURCE
   Hero shows Now Showing (for the selected city) followed by
   Coming Soon — same data + same poster logic as the rails
   below, so hero always matches what's on the page.
   ============================================================ */
$hero_movies = array_merge($now_showing, $coming_soon);

if (empty($hero_movies)) {
    $hero_movies = [
        [
            'id'            => 0,
            'title'         => 'Welcome to CineBook',
            'status'        => 'Now Showing',
            'certification' => 'UA',
            'duration'      => '—',
            'genre'         => 'Browse movies below',
            'synopsis'      => 'Your ultimate movie booking destination. Select a city to discover what\'s playing near you.',
            'poster_image'  => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=2670&auto=format&fit=crop',
        ]
    ];
}

// Helper to get poster URL for a movie
function heroImageUrl($movie) {
    if (empty($movie['poster_image'])) {
        return 'https://images.unsplash.com/photo-1614730321146-b6fa6a46bcb4?q=80&w=2574&auto=format&fit=crop';
    }
    if (filter_var($movie['poster_image'], FILTER_VALIDATE_URL)) {
        return htmlspecialchars($movie['poster_image']);
    }
    return 'admin/' . htmlspecialchars($movie['poster_image']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/svg+xml" href="/CineBook/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBook - Home</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#F5C518',
                    }
                }
            }
        }
    </script>
    
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }

        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        .hero-bg { background-size: cover; background-position: center; }

        html:not(.dark) #theme-icon-moon { display: block !important; }
        html:not(.dark) #theme-icon-sun  { display: none  !important; }
        html.dark #theme-icon-moon       { display: none  !important; }
        html.dark #theme-icon-sun        { display: block !important; }

        /* ---- Hero carousel: one banner visible at a time ----
           Every slide is stacked exactly on top of the others
           (position: absolute; inset: 0) and only moved via
           transform: translateX(). Only ONE slide is ever at
           translateX(0) — everything else sits off-screen to
           the left or right. This is immune to width/flex math
           bugs: there is no "row" that can show two banners
           side by side, because slides are never laid out in
           normal flow next to each other. */
        #hero-track {
            position: relative;
            width: 100%;
            height: 100%;
        }
        .hero-slide {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            transition: transform 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ---- Carousel (Now Showing / Coming Soon rails) ---- */
        .carousel-track {
            display: flex;
            transition: transform 0.45s cubic-bezier(0.4,0,0.2,1);
            will-change: transform;
        }
        .carousel-card {
            flex: 0 0 auto;
        }
        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ffffff;
            color: #111111;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: transform 0.2s, background 0.2s, color 0.2s;
        }
        html.dark .carousel-arrow {
            background: #222222;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
        }
        .carousel-arrow:hover { transform: translateY(-50%); }
        .carousel-arrow.left  { left:  -20px; }
        .carousel-arrow.right { right: -20px; }
        .carousel-dot {
            width: 8px; height: 8px;
            border-radius: 9999px;
            background: #d1d5db;
            transition: background 0.3s, width 0.3s;
            cursor: pointer;
            border: none;
            padding: 0;
        }
        html.dark .carousel-dot { background: #4b5563; }
        .carousel-dot.active    { background: #F5C518; width: 24px; }
        .movie-poster-card {
            position: relative;
            overflow: hidden;
            border-radius: 14px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .movie-poster-card:hover { transform: scale(1.04); }
        .movie-poster-card img { width:100%; height:100%; object-fit:cover; display:block; }
        .movie-poster-card .card-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.3s;
            display: flex; flex-direction: column;
            justify-content: flex-end;
            padding: 12px;
        }
        .movie-poster-card:hover .card-overlay { opacity: 1; }
        .badge-rerelease {
            position: absolute; top: 0; left: 50%; transform: translateX(-50%);
            background: #F5C518; color: #000; font-size: 10px; font-weight: 700;
            letter-spacing: .05em; text-transform: uppercase;
            padding: 3px 10px; border-radius: 0 0 8px 8px; z-index: 10;
            white-space: nowrap;
        }
    </style>
</head>
<body class="bg-white dark:bg-[#121212] text-gray-900 dark:text-gray-100 min-h-screen flex flex-col transition-colors duration-300">

    <?php include("header.php"); ?>

    <!-- ===================== HERO CAROUSEL ===================== -->
    <section id="hero-carousel" class="relative overflow-hidden bg-black" style="height: clamp(420px, 58vw, 600px);">

        <!-- Track -->
        <div id="hero-track">

        <!-- Slides: each one is stacked on top of the others.
             Server-side we already place slide 0 at translateX(0)
             and every other slide off-screen to the right, so the
             layout is correct on first paint even before JS runs. -->
        <?php foreach ($hero_movies as $hi => $hm): ?>
        <?php $hBg = heroImageUrl($hm); ?>
        <div class="hero-slide" data-index="<?php echo $hi; ?>"
             style="transform: translateX(<?php echo $hi === 0 ? '0' : '100'; ?>%);">

            <!-- Solid black base (primary background color) -->
            <div class="absolute inset-0 bg-black"></div>
            <!-- Blurred poster, dimmed further and layered on top of the black base -->
            <div class="absolute inset-0" style="background:url('<?php echo $hBg; ?>') center/cover no-repeat; filter:blur(18px) brightness(0.3); transform:scale(1.08); opacity:0.45;"></div>
            <!-- Dark overlay -->
            <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/60 to-black/20"></div>

            <!-- Content row -->
            <div class="relative z-10 h-full w-full max-w-7xl mx-auto flex items-center px-16 md:px-24 gap-8 md:gap-16">

                <!-- LEFT: Movie info -->
                <div class="flex-1 min-w-0 flex flex-col justify-center gap-3 py-8">

                    <!-- Status badge -->
                    <div class="inline-flex items-center gap-2 self-start bg-[#F5C518] text-black px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M6 4l15 8-15 8z"/></svg>
                        <?php echo htmlspecialchars($hm['status']); ?>
                    </div>

                    <!-- Title -->
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-white leading-tight tracking-tight">
                        <?php echo htmlspecialchars($hm['title']); ?>
                    </h1>

                    <!-- Meta -->
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <?php if (!empty($hm['certification'])): ?>
                        <span class="px-2.5 py-0.5 bg-white/15 border border-white/25 rounded text-white text-xs font-bold">
                            <?php echo htmlspecialchars($hm['certification']); ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($hm['duration'])): ?>
                        <span class="text-gray-300"><?php echo htmlspecialchars($hm['duration']); ?> • </span>
                        <?php endif; ?>
                        <?php if (!empty($hm['genre'])): ?>
                        <span class="text-[#F5C518] font-medium"><?php echo htmlspecialchars($hm['genre']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($hm['language'])): ?>
                        <span class="text-gray-400">• <?php echo htmlspecialchars($hm['language']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Synopsis -->
                    <?php if (!empty($hm['synopsis'])): ?>
                    <p class="text-sm text-gray-300 leading-relaxed max-w-md line-clamp-3">
                        <?php echo htmlspecialchars($hm['synopsis']); ?>
                    </p>
                    <?php endif; ?>

                    <!-- CTA buttons -->
                    <?php if (!empty($hm['id']) && $hm['id'] > 0): ?>
                    <div class="flex flex-wrap gap-3 pt-1">
                        <a href="watch_trailer.php?id=<?php echo $hm['id']; ?>"
                           class="flex items-center gap-2 bg-[#F5C518] text-black px-5 py-2.5 rounded-xl font-bold hover:bg-[#eab308] transition-colors text-sm shadow-lg">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M6 4l15 8-15 8z"/></svg>
                            Watch Trailer
                        </a>
                        <a href="movie_details.php?id=<?php echo $hm['id']; ?>"
                           class="flex items-center gap-2 bg-white/10 backdrop-blur border border-white/30 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-white/20 transition-colors text-sm">
                            Book Tickets
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- RIGHT: Poster image -->
                <div class="hidden sm:flex flex-col items-center gap-4 py-8 shrink-0">
                    <div class="relative group" style="width:clamp(140px,14vw,200px);">
                        <div style="width:100%;padding-top:150%;position:relative;border-radius:16px;overflow:hidden;box-shadow:0 8px 40px rgba(245,197,24,0.35), 0 4px 20px rgba(0,0,0,0.6);">
                            <img src="<?php echo $hBg; ?>"
                                 alt="<?php echo htmlspecialchars($hm['title']); ?>"
                                 style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"
                                 loading="lazy">
                            <div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,0.12) 0%,transparent 50%,transparent 100%);border-radius:16px;pointer-events:none;"></div>
                        </div>
                        <div class="mt-2 text-center">
                            <span class="text-xs font-bold px-3 py-1 rounded-full
                                <?php echo strtolower(trim($hm['status'])) === 'coming soon' ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' : 'bg-[#F5C518]/20 text-[#F5C518] border border-[#F5C518]/30'; ?>">
                                <?php echo htmlspecialchars($hm['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div> <!-- End hero track -->

        <!-- Left / Right arrows -->
        <button id="hero-prev" type="button"
                class="absolute left-4 top-1/2 -translate-y-1/2 z-30 w-11 h-11 rounded-full bg-black/50 backdrop-blur border border-white/20 text-white flex items-center justify-center hover:bg-[#F5C518] hover:text-black transition-all shadow-xl">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button id="hero-next" type="button"
                class="absolute right-4 top-1/2 -translate-y-1/2 z-30 w-11 h-11 rounded-full bg-black/50 backdrop-blur border border-white/20 text-white flex items-center justify-center hover:bg-[#F5C518] hover:text-black transition-all shadow-xl">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 6 15 12 9 18"/></svg>
        </button>

        <!-- Dot indicators -->
        <div id="hero-dots" class="absolute bottom-5 left-1/2 -translate-x-1/2 z-30 flex items-center gap-2"></div>

        <!-- Progress bar -->
        <div id="hero-progress" class="absolute bottom-0 left-0 h-[3px] bg-[#F5C518] z-30 transition-none" style="width:0%"></div>

    </section>

    <!-- Filters Section -->
    <div class="bg-gray-50 dark:bg-[#0a0a0a] border-b border-gray-200 dark:border-[#262626] py-4 px-8 transition-colors duration-300 shadow-sm">
        <div class="max-w-7xl mx-auto w-full flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white mr-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="21 4 3 4 10 12 10 20 14 20 14 12 21 4"></polygon>
                </svg>
                Filters:
            </div>
            
            <div class="flex gap-2">
                <button id="btn-filter-all" onclick="setFilterStatus('All')" class="filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-[#F5C518] text-black border border-transparent transition-colors">All</button>
                <button id="btn-filter-ns" onclick="setFilterStatus('Now Showing')" class="filter-btn px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 dark:text-white bg-white dark:bg-black border border-gray-200 dark:border-[#262626] hover:bg-gray-50 dark:hover:bg-[#1a1a1a] transition-colors">Now Showing</button>
                <button id="btn-filter-cs" onclick="setFilterStatus('Coming Soon')" class="filter-btn px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 dark:text-white bg-white dark:bg-black border border-gray-200 dark:border-[#262626] hover:bg-gray-50 dark:hover:bg-[#1a1a1a] transition-colors">Coming Soon</button>
            </div>

            <div class="ml-2 flex gap-2">
                <div class="relative">
                    <select id="filter-genre" onchange="applyFilters()" class="px-4 py-2 bg-white dark:bg-black border border-gray-200 dark:border-[#262626] rounded-xl text-sm font-semibold text-gray-700 dark:text-white outline-none hover:bg-gray-50 dark:hover:bg-[#1a1a1a] transition-colors cursor-pointer appearance-none pr-10">
                        <option value="All">All Genres</option>
                        <?php foreach($all_genres as $g): ?>
                            <option value="<?php echo htmlspecialchars(strtolower($g)); ?>"><?php echo htmlspecialchars($g); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <svg class="w-4 h-4 pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>

                <div class="relative">
                    <select id="filter-language" onchange="applyFilters()" class="px-4 py-2 bg-white dark:bg-black border border-gray-200 dark:border-[#262626] rounded-xl text-sm font-semibold text-gray-700 dark:text-white outline-none hover:bg-gray-50 dark:hover:bg-[#1a1a1a] transition-colors cursor-pointer appearance-none pr-10">
                        <option value="All">All Languages</option>
                        <?php foreach($all_languages as $l): ?>
                            <option value="<?php echo htmlspecialchars(strtolower($l)); ?>"><?php echo htmlspecialchars($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <svg class="w-4 h-4 pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="flex-1 w-full max-w-7xl mx-auto px-8 py-6 space-y-12">

        <!-- NOW SHOWING CAROUSEL -->
        <section id="section-now-showing">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Now Showing in <?php echo htmlspecialchars($selected_city); ?></h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Currently playing near you</p>
                </div>
                <div class="flex items-center gap-3">
                    <button id="ns-prev" class="carousel-arrow left" style="position:relative;transform:none;left:auto;right:auto;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button id="ns-next" class="carousel-arrow right" style="position:relative;transform:none;left:auto;right:auto;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 6 15 12 9 18"/></svg>
                    </button>
                </div>
            </div>

            <?php if (!empty($now_showing)): ?>
                <div class="relative overflow-hidden">
                    <div id="ns-track" class="carousel-track gap-5">
                        <?php foreach($now_showing as $idx => $movie): ?>
                            <?php $poster = !empty($movie['poster_image']) ? 'admin/'.htmlspecialchars($movie['poster_image']) : 'https://via.placeholder.com/400x600/1a1a1a/ffffff?text=No+Poster'; ?>
                            <div class="carousel-card movie-card w-[165px] md:w-[185px]"
                                 data-genre="<?php echo htmlspecialchars(strtolower($movie['genre'] ?? '')); ?>"
                                 data-language="<?php echo htmlspecialchars(strtolower($movie['language'] ?? '')); ?>"
                                 data-status="<?php echo htmlspecialchars($movie['status']); ?>">
                                <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="block">
                                    <div class="movie-poster-card h-[240px] md:h-[265px] mb-3 shadow-lg">
                                        <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" loading="lazy">
                                        <?php if (!empty($movie['is_rerelease'])): ?>
                                            <span class="badge-rerelease">Re-Release</span>
                                        <?php endif; ?>
                                        <div class="card-overlay">
                                            <!-- Hover gradient only, text/buttons removed as requested -->
                                        </div>
                                    </div>
                                    <h3 class="font-bold text-[13px] text-gray-900 dark:text-white leading-snug mb-0.5 truncate"><?php echo htmlspecialchars($movie['title']); ?></h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo htmlspecialchars($movie['genre'] ?? ''); ?></p>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Poster dots placed at the bottom -->
                <div id="ns-dots" class="flex flex-wrap items-center justify-center gap-2 mt-6"></div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="w-14 h-14 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75.125A1.125 1.125 0 013 18.375V5.625m0 0a1.125 1.125 0 011.125-1.125h3.75M3 5.625V4.5M21 5.625a1.125 1.125 0 00-1.125-1.125h-3.75M21 5.625V4.5m0 1.125v12.75A1.125 1.125 0 0119.875 19.5M15 4.5h1.125"/></svg>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No movies showing in <?php echo htmlspecialchars($selected_city); ?></p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try selecting a different city</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- COMING SOON CAROUSEL -->
        <section id="section-coming-soon">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Coming Soon</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Upcoming releases to look forward to</p>
                </div>
                <div class="flex items-center gap-3">
                    <button id="cs-prev" class="carousel-arrow left" style="position:relative;transform:none;left:auto;right:auto;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button id="cs-next" class="carousel-arrow right" style="position:relative;transform:none;left:auto;right:auto;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 6 15 12 9 18"/></svg>
                    </button>
                </div>
            </div>

            <?php if (!empty($coming_soon)): ?>
                <div class="relative overflow-hidden">
                    <div id="cs-track" class="carousel-track gap-5">
                        <?php foreach($coming_soon as $idx => $movie): ?>
                            <?php $poster = !empty($movie['poster_image']) ? 'admin/'.htmlspecialchars($movie['poster_image']) : 'https://via.placeholder.com/400x600/1a1a1a/ffffff?text=No+Poster'; ?>
                            <div class="carousel-card movie-card w-[165px] md:w-[185px]"
                                 data-genre="<?php echo htmlspecialchars(strtolower($movie['genre'] ?? '')); ?>"
                                 data-language="<?php echo htmlspecialchars(strtolower($movie['language'] ?? '')); ?>"
                                 data-status="<?php echo htmlspecialchars($movie['status']); ?>">
                                <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="block">
                                    <div class="movie-poster-card h-[240px] md:h-[265px] mb-3 shadow-lg">
                                        <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" loading="lazy">
                                        <?php if (!empty($movie['is_rerelease'])): ?>
                                            <span class="badge-rerelease">Re-Release</span>
                                        <?php endif; ?>
                                        <div class="card-overlay">
                                            <span class="inline-block bg-white/20 text-white text-[11px] font-bold px-3 py-1 rounded-full mb-1 w-fit backdrop-blur-sm">Coming Soon</span>
                                            <p class="text-white text-xs font-semibold truncate"><?php echo htmlspecialchars($movie['language'] ?? ''); ?></p>
                                        </div>
                                    </div>
                                    <h3 class="font-bold text-[13px] text-gray-900 dark:text-white leading-snug mb-0.5 truncate"><?php echo htmlspecialchars($movie['title']); ?></h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo htmlspecialchars($movie['genre'] ?? ''); ?></p>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Poster dots placed at the bottom -->
                <div id="cs-dots" class="flex flex-wrap items-center justify-center gap-2 mt-6"></div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="w-14 h-14 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5"/></svg>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No upcoming movies right now</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Check back soon for new releases</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <div id="signin-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/70 backdrop-blur-sm transition-opacity">
        <div class="bg-[#121212] border border-[#262626] rounded-xl w-full max-w-sm mx-4 shadow-2xl relative flex flex-col font-sans">
            <div class="flex items-center justify-between p-6 border-b border-[#262626]">
                <h2 class="text-2xl font-bold text-white tracking-tight">Sign In</h2>
                <button id="close-modal-btn" class="text-gray-400 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6">
                <form action="user_login.php" method="POST" class="space-y-5">
                    <div class="space-y-2">
                        <label for="user-email" class="block text-sm font-semibold text-gray-200">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="mail" class="h-5 w-5 text-gray-500"></i>
                            </div>
                            <input type="email" id="user-email" name="email" required placeholder="Enter your email" 
                                class="block w-full pl-10 pr-3 py-2.5 border border-[#262626] rounded-lg bg-[#1a1a1a] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-all">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="user-password" class="block text-sm font-semibold text-gray-200">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="lock" class="h-5 w-5 text-gray-500"></i>
                            </div>
                            <input type="password" id="user-password" name="password" required placeholder="Enter your password" 
                                class="block w-full pl-10 pr-3 py-2.5 border border-[#262626] rounded-lg bg-[#1a1a1a] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-all">
                        </div>
                    </div>

                    <button type="submit" class="w-full flex justify-center py-3 px-4 rounded-lg text-sm font-bold text-black bg-primary hover:bg-[#eab308] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#121212] focus:ring-primary transition-colors mt-6">
                        Sign In
                    </button>
                </form>
                
                <div class="mt-6 text-center text-sm text-gray-400">
                    Don't have an account? <button type="button" id="switch-to-signup" class="text-primary font-bold hover:underline cursor-pointer">Sign up</button>
                </div>
            </div>
        </div>
    </div>

    <div id="signup-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/70 backdrop-blur-sm transition-opacity">
        <div class="bg-[#121212] border border-[#262626] rounded-xl w-full max-w-sm mx-4 shadow-2xl relative flex flex-col font-sans">
            <div class="flex items-center justify-between p-6 border-b border-[#262626]">
                <h2 class="text-2xl font-bold text-white tracking-tight">Create Account</h2>
                <button id="close-signup-btn" class="text-gray-400 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="p-6">
                <form action="user_signup.php" method="POST" class="space-y-4">
                    <div class="space-y-2">
                        <label for="reg-name" class="block text-sm font-semibold text-gray-200">Full Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="user" class="h-5 w-5 text-gray-500"></i>
                            </div>
                            <input type="text" id="reg-name" name="fullname" required placeholder="Enter your full name" 
                                class="block w-full pl-10 pr-3 py-2.5 border border-[#262626] rounded-lg bg-[#1a1a1a] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-all">
                        </div>
                    </div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 rounded-lg text-sm font-bold text-black bg-primary hover:bg-[#eab308] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#121212] focus:ring-primary transition-colors mt-6">
                        Create Account
                    </button>
                </form>
                
                <div class="mt-6 text-center text-sm text-gray-400">
                    Already have an account? <button type="button" id="switch-to-signin" class="text-primary font-bold hover:underline cursor-pointer">Sign in</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () { // <-- everything below is now scoped to this function,
                    //     so it can NEVER collide with a same-named
                    //     variable declared in header.php or anywhere else.

        /* ========================================================
           HERO BANNER CAROUSEL
           - Slides come from PHP $hero_movies (Now Showing for
             the selected city).
           - Track width = slideCount * 100%, each slide width
             = 100% / slideCount, so translateX math never drifts.
           - Wrapped in DOMContentLoaded so elements always exist
             before we try to attach listeners (this is the most
             common reason arrow clicks silently do nothing).
           ======================================================== */
        document.addEventListener('DOMContentLoaded', function () {
            const track    = document.getElementById('hero-track');
            const slides   = track ? Array.from(track.querySelectorAll('.hero-slide')) : [];
            const prevBtn  = document.getElementById('hero-prev');
            const nextBtn  = document.getElementById('hero-next');
            const dotsWrap = document.getElementById('hero-dots');
            const progBar  = document.getElementById('hero-progress');

            if (!track || slides.length === 0) return;

            const INTERVAL = 6000;
            const total = slides.length;
            let current = 0;
            let timer;

            // Only one slide? Hide nav entirely instead of leaving dead buttons.
            if (total <= 1) {
                if (prevBtn)  prevBtn.style.display  = 'none';
                if (nextBtn)  nextBtn.style.display  = 'none';
                if (progBar)  progBar.style.display  = 'none';
                if (dotsWrap) dotsWrap.style.display = 'none';
                return;
            }

            if (dotsWrap) {
                slides.forEach((_, i) => {
                    const dot = document.createElement('button');
                    dot.type = 'button';
                    dot.style.cssText = [
                        'display:inline-block',
                        'width:' + (i === 0 ? '24px' : '8px'),
                        'height:8px',
                        'border-radius:9999px',
                        'border:none',
                        'cursor:pointer',
                        'transition:width 0.3s,background 0.3s',
                        'background:' + (i === 0 ? '#F5C518' : 'rgba(255,255,255,0.4)')
                    ].join(';');
                    dot.setAttribute('aria-label', 'Slide ' + (i + 1));
                    dot.addEventListener('click', () => { goTo(i); resetAuto(); });
                    dotsWrap.appendChild(dot);
                });
            }

            // Position every slide relative to `current` using the
            // SHORTEST wrap-around path, so slide 0 -> last slide
            // animates as a single short slide left/right instead of
            // sliding all the way across every slide in between.
            function render() {
                slides.forEach((slide, i) => {
                    let diff = i - current;
                    if (diff > total / 2) diff -= total;
                    if (diff < -total / 2) diff += total;
                    slide.style.transform = `translateX(${diff * 100}%)`;
                });

                if (dotsWrap) {
                    Array.from(dotsWrap.children).forEach((d, i) => {
                        d.style.width      = i === current ? '24px' : '8px';
                        d.style.background = i === current ? '#F5C518' : 'rgba(255,255,255,0.4)';
                    });
                }

                if (progBar) {
                    progBar.style.transition = 'none';
                    progBar.style.width = '0%';
                    void progBar.offsetWidth;
                    progBar.style.transition = 'width ' + INTERVAL + 'ms linear';
                    progBar.style.width = '100%';
                }
            }

            function goTo(index) {
                current = ((index % total) + total) % total;
                render();
            }

            function startAuto() {
                clearInterval(timer);
                timer = setInterval(() => goTo(current + 1), INTERVAL);
            }
            function resetAuto() {
                clearInterval(timer);
                startAuto();
            }

            prevBtn.addEventListener('click', () => { goTo(current - 1); resetAuto(); });
            nextBtn.addEventListener('click', () => { goTo(current + 1); resetAuto(); });

            const heroEl = document.getElementById('hero-carousel');
            heroEl.addEventListener('mouseenter', () => clearInterval(timer));
            heroEl.addEventListener('mouseleave', () => startAuto());

            render();
            startAuto();
        });


        /* ========================================================
           CAROUSEL ENGINE (For Now Showing & Coming Soon sections)
           ======================================================== */
        function initCarousel(trackId, prevId, nextId, dotsId) {
            const track   = document.getElementById(trackId);
            const prevBtn = document.getElementById(prevId);
            const nextBtn = document.getElementById(nextId);
            const dotsWrap = document.getElementById(dotsId);
            if (!track) return null;

            const cards = Array.from(track.querySelectorAll('.carousel-card'));
            if (!cards.length) return null;

            const GAP = 20; 
            let currentIndex = 0;
            let visibleCards = [];

            function getCardWidth() {
                const c = visibleCards[0] || cards[0];
                return c ? c.offsetWidth + GAP : 200;
            }

            function getVisibleCount() {
                const cw = getCardWidth();
                return Math.max(1, Math.floor(track.parentElement.offsetWidth / cw));
            }

            function buildDots() {
                if (!dotsWrap) return;
                dotsWrap.innerHTML = '';
                const vc = getVisibleCount();
                const maxIndex = Math.max(0, visibleCards.length - vc);
                const dotCount = maxIndex + 1;
                
                if (dotCount <= 1) return;

                for (let i = 0; i < dotCount; i++) {
                    const dot = document.createElement('button');
                    dot.className = 'carousel-dot' + (i === currentIndex ? ' active' : '');
                    dot.setAttribute('aria-label', 'Slide ' + (i+1));
                    dot.addEventListener('click', () => { goTo(i); resetTimer(); });
                    dotsWrap.appendChild(dot);
                }
            }

            function updateDots() {
                if (!dotsWrap) return;
                Array.from(dotsWrap.children).forEach((dot, i) => {
                    dot.classList.toggle('active', i === currentIndex);
                });
            }

            function goTo(index) {
                if (!visibleCards.length) return;
                const vc = getVisibleCount();
                const maxIndex = Math.max(0, visibleCards.length - vc);
                
                currentIndex = Math.max(0, Math.min(index, maxIndex));

                let offset = 0;
                if (visibleCards[currentIndex]) {
                    offset = visibleCards[currentIndex].offsetLeft;
                }
                track.style.transform = 'translateX(-' + offset + 'px)';
                updateDots();
            }

            function next() {
                const vc = getVisibleCount();
                const maxIndex = Math.max(0, visibleCards.length - vc);
                if (currentIndex >= maxIndex) {
                    goTo(0); // loop back to first poster
                } else {
                    goTo(currentIndex + 1); // move by exactly one poster
                }
            }
            
            function prev() {
                const vc = getVisibleCount();
                const maxIndex = Math.max(0, visibleCards.length - vc);
                if (currentIndex <= 0) {
                    goTo(maxIndex); // loop to last possible poster view
                } else {
                    goTo(currentIndex - 1); // move back exactly one poster
                }
            }

            let timer;
            function startTimer() {
                timer = setInterval(next, 5000);
            }
            function resetTimer() {
                clearInterval(timer);
                startTimer();
            }

            if (prevBtn) prevBtn.addEventListener('click', () => { prev(); resetTimer(); });
            if (nextBtn) nextBtn.addEventListener('click', () => { next(); resetTimer(); });

            function refresh() {
                visibleCards = cards.filter(c => c.style.display !== 'none');
                currentIndex = 0;
                track.style.transform = 'translateX(0)';
                buildDots();
            }

            track.parentElement.addEventListener('mouseenter', () => clearInterval(timer));
            track.parentElement.addEventListener('mouseleave', () => startTimer());

            setTimeout(refresh, 80);
            window.addEventListener('resize', () => setTimeout(refresh, 100));
            startTimer();

            return { refresh, goTo, next, prev };
        }

        const nsCarousel = initCarousel('ns-track', 'ns-prev', 'ns-next', 'ns-dots');
        const csCarousel = initCarousel('cs-track', 'cs-prev', 'cs-next', 'cs-dots');

        const openSigninBtn = document.getElementById('open-signin-btn');
        const signinModal = document.getElementById('signin-modal');
        const closeSigninBtn = document.getElementById('close-modal-btn');
        const signupModal = document.getElementById('signup-modal');
        const closeSignupBtn = document.getElementById('close-signup-btn');
        const switchToSignupBtn = document.getElementById('switch-to-signup');
        const switchToSigninBtn = document.getElementById('switch-to-signin');

        if (openSigninBtn && signinModal) {
            openSigninBtn.addEventListener('click', () => {
                signinModal.classList.remove('hidden');
                signinModal.classList.add('flex');
            });
        }

        if (closeSigninBtn && signinModal) {
            closeSigninBtn.addEventListener('click', () => {
                signinModal.classList.add('hidden');
                signinModal.classList.remove('flex');
            });
        }

        if (closeSignupBtn && signupModal) {
            closeSignupBtn.addEventListener('click', () => {
                signupModal.classList.add('hidden');
                signupModal.classList.remove('flex');
            });
        }

        if (switchToSignupBtn) {
            switchToSignupBtn.addEventListener('click', () => {
                signinModal.classList.add('hidden');
                signinModal.classList.remove('flex');
                signupModal.classList.remove('hidden');
                signupModal.classList.add('flex');
            });
        }

        if (switchToSigninBtn) {
            switchToSigninBtn.addEventListener('click', () => {
                signupModal.classList.add('hidden');
                signupModal.classList.remove('flex');
                signinModal.classList.remove('hidden');
                signinModal.classList.add('flex');
            });
        }

        window.addEventListener('click', (e) => {
            if (e.target === signinModal) {
                signinModal.classList.add('hidden');
                signinModal.classList.remove('flex');
            }
            if (e.target === signupModal) {
                signupModal.classList.add('hidden');
                signupModal.classList.remove('flex');
            }
        });

        // Theme toggle (#theme-toggle) is wired up in header.php,
        // since that's where the button itself lives — intentionally
        // not duplicated here to avoid a double-toggle bug.
        
        // --- Filtering Logic ---
        let currentStatusFilter = 'All';

        function setFilterStatus(status) {
            currentStatusFilter = status;
            
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.className = "filter-btn px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 dark:text-white bg-white dark:bg-black border border-gray-200 dark:border-[#262626] hover:bg-gray-50 dark:hover:bg-[#1a1a1a] transition-colors";
            });
            
            let activeBtnId = 'btn-filter-all';
            if (status === 'Now Showing') activeBtnId = 'btn-filter-ns';
            if (status === 'Coming Soon') activeBtnId = 'btn-filter-cs';
            
            const activeBtn = document.getElementById(activeBtnId);
            if (activeBtn) {
                activeBtn.className = "filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-[#F5C518] text-black border border-transparent transition-colors";
            }
            
            applyFilters();
        }

        function applyFilters() {
            const genreSelect = document.getElementById('filter-genre');
            const langSelect  = document.getElementById('filter-language');
            
            const selectedGenre = genreSelect ? genreSelect.value.toLowerCase() : 'all';
            const selectedLang  = langSelect  ? langSelect.value.toLowerCase()  : 'all';
            
            const safeCurrentStatus = currentStatusFilter.trim().toLowerCase();
            
            const cards = document.querySelectorAll('.movie-card');
            let visibleNsCount = 0, visibleCsCount = 0;
            
            cards.forEach(card => {
                const cardStatus = (card.getAttribute('data-status') || '').trim().toLowerCase();
                const cardGenre  = (card.getAttribute('data-genre')  || '').toLowerCase();
                const cardLang   = (card.getAttribute('data-language')|| '').toLowerCase();
                
                let show = true;
                if (currentStatusFilter !== 'All' && cardStatus !== safeCurrentStatus) show = false;
                if (selectedGenre !== 'all' && !cardGenre.includes(selectedGenre)) show = false;
                if (selectedLang  !== 'all' && !cardLang.includes(selectedLang))   show = false;
                
                card.style.display = show ? '' : 'none';
                
                if (show) {
                    if (cardStatus === 'now showing') visibleNsCount++;
                    if (cardStatus === 'coming soon')  visibleCsCount++;
                }
            });
            
            const nsSection = document.getElementById('section-now-showing');
            const csSection = document.getElementById('section-coming-soon');
            if (nsSection) nsSection.style.display = currentStatusFilter === 'Coming Soon' ? 'none' : '';
            if (csSection) csSection.style.display  = currentStatusFilter === 'Now Showing' ? 'none' : '';
            
            setTimeout(() => {
                if (nsCarousel) nsCarousel.refresh();
                if (csCarousel) csCarousel.refresh();
            }, 50);
        }

        // Exposed on window because the filter buttons/selects use
        // inline onclick="setFilterStatus(...)" / onchange="applyFilters()"
        // in the HTML — those inline handlers can only see GLOBAL
        // functions, and everything in this script is otherwise
        // scoped inside the IIFE. This is the one intentional
        // exception, added on purpose (not a leftover mistake).
        window.setFilterStatus = setFilterStatus;
        window.applyFilters = applyFilters;

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

    })(); // end IIFE
    </script>
</body>
</html>

