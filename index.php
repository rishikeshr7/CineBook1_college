<?php
session_start();
require_once 'dbconnect.php';

// 1. Get the current selected city (falls back to Mumbai)
$selected_city = isset($_GET['city']) ? trim($_GET['city']) : (isset($_SESSION['user_city']) ? $_SESSION['user_city'] : 'Mumbai');
$_SESSION['user_city'] = $selected_city;

$now_showing = [];
$coming_soon = [];
$featured_movie = null;

// 2. Fetch "Now Showing" movies specifically playing in the selected city
$ns_sql = "
    SELECT DISTINCT m.* FROM movies m 
    INNER JOIN showtimes s ON m.id = s.movie_id 
    WHERE m.status = 'Now Showing' AND s.city = ? AND s.show_date >= CURRENT_DATE
    ORDER BY m.created_at DESC
";
if (isset($conn)) {
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

    // 3. Fetch "Coming Soon" movies globally
    $cs_sql = "SELECT * FROM movies WHERE status = 'Coming Soon' ORDER BY created_at DESC";
    $cs_result = $conn->query($cs_sql);
    if ($cs_result && $cs_result->num_rows > 0) {
        while($row = $cs_result->fetch_assoc()) {
            $coming_soon[] = $row;
        }
    }
}

// Fallback dummy data for visual matching if DB is empty
if (empty($now_showing) && empty($coming_soon)) {
    $featured_movie = [
        'title' => 'Dune: Part Two',
        'status' => 'Now Showing',
        'certification' => 'UA',
        'duration' => '166 min',
        'genre' => 'Sci-Fi, Adventure, Drama',
        'synopsis' => 'Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family.',
        'poster_image' => 'https://images.unsplash.com/photo-1614730321146-b6fa6a46bcb4?q=80&w=2574&auto=format&fit=crop', // Earth from space placeholder
        'trailer_url' => '#'
    ];
} else {
    // Set Featured Movie (Hero Banner)
    if (!empty($now_showing)) {
        $featured_movie = $now_showing[0];
    } elseif (!empty($coming_soon)) {
        $featured_movie = $coming_soon[0];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
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

        /* Hide scrollbar for carousels */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .hero-bg {
            background-size: cover;
            background-position: center;
        }

        html:not(.dark) #theme-icon-moon { display: block !important; }
        html:not(.dark) #theme-icon-sun { display: none !important; }
        html.dark #theme-icon-moon { display: none !important; }
        html.dark #theme-icon-sun { display: block !important; }
    </style>
</head>
<body class="bg-white dark:bg-[#121212] text-gray-900 dark:text-gray-100 min-h-screen flex flex-col transition-colors duration-300">

    <?php include("header.php"); ?>

    <?php 
        $hero_bg_image = '';
        if ($featured_movie) {
            // Using placeholder logic or actual DB path
            if(filter_var($featured_movie['poster_image'], FILTER_VALIDATE_URL)) {
                $hero_bg_image = htmlspecialchars($featured_movie['poster_image']);
            } else if (!empty($featured_movie['poster_image'])) {
                $hero_bg_image = 'admin/' . htmlspecialchars($featured_movie['poster_image']);
            } else {
                $hero_bg_image = 'https://images.unsplash.com/photo-1614730321146-b6fa6a46bcb4?q=80&w=2574&auto=format&fit=crop';
            }
        }
    ?>

    <section class="hero-bg py-24 md:py-32 px-8 md:px-16 relative" style="background-image: linear-gradient(to right, rgba(10, 15, 25, 0.95) 0%, rgba(10, 15, 25, 0.7) 40%, rgba(10, 15, 25, 0.1) 100%), url('<?php echo $hero_bg_image; ?>');">
        <div class="max-w-4xl space-y-7 relative z-10">
            <?php if ($featured_movie): ?>
                <div class="inline-flex items-center gap-2 bg-[#F5C518] text-black px-5 py-2 rounded-full text-sm font-bold uppercase tracking-wider">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M6 4l15 8-15 8z"/></svg>
                    <?php echo htmlspecialchars($featured_movie['status']); ?>
                </div>

                <h1 class="text-6xl md:text-[6rem] leading-none font-extrabold text-white tracking-tight">
                    <?php echo htmlspecialchars($featured_movie['title']); ?>
                </h1>

                <div class="flex items-center gap-4 text-white text-lg font-medium pt-1">
                    <span class="px-4 py-1.5 bg-[#333333] rounded-full text-sm font-semibold text-white tracking-wide">
                        <?php echo htmlspecialchars($featured_movie['certification']); ?>
                    </span>
                    <span class="text-white">•</span>
                    <span><?php echo htmlspecialchars($featured_movie['duration']); ?></span>
                    <span class="text-white">•</span>
                    <span><?php echo htmlspecialchars($featured_movie['genre']); ?></span>
                </div>

                <p class="text-xl text-white max-w-2xl leading-relaxed pt-2">
                    <?php echo htmlspecialchars($featured_movie['synopsis']); ?>
                </p>

                <div class="flex flex-wrap items-center gap-4 pt-4">
                    <a href="<?php echo htmlspecialchars($featured_movie['trailer_url'] ?? '#'); ?>" class="flex items-center gap-2.5 bg-[#F5C518] text-black px-7 py-3 rounded-xl font-semibold hover:bg-[#eab308] transition-colors text-lg">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M6 4l15 8-15 8z"/></svg>
                        Watch Trailer
                    </a>
                    
                    <a href="movie_details.php?id=<?php echo $featured_movie['id'] ?? '#'; ?>" class="flex items-center justify-center bg-transparent border border-gray-300 text-white px-7 py-3 rounded-xl font-semibold hover:bg-white/10 transition-colors text-lg">
                        Book Tickets
                    </a>
                </div>
            <?php else: ?>
                <h1 class="text-5xl md:text-7xl font-bold text-white tracking-tight">Welcome to CineBook</h1>
                <p class="text-lg text-gray-300 max-w-2xl leading-relaxed">No movies are currently playing in <?php echo htmlspecialchars($selected_city); ?>. Try selecting a different location!</p>
            <?php endif; ?>
        </div>
    </section>

    <div class="bg-white dark:bg-[#121212] border-b border-gray-200 dark:border-gray-800 py-4 px-8 transition-colors duration-300 shadow-sm">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-gray-200 mr-2">
                <i data-lucide="filter" class="w-4 h-4"></i>
                Filters:
            </div>
            
            <div class="flex gap-2">
                <button class="px-5 py-2 rounded-lg text-sm font-bold bg-primary text-black shadow-sm">All</button>
                <button class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">Now Showing</button>
                <button class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">Coming Soon</button>
            </div>

            <div class="ml-2 flex gap-3">
                <select class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 outline-none hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer appearance-none pr-8 relative">
                    <option>All Genres</option>
                    <option>Action</option>
                    <option>Sci-Fi</option>
                </select>

                <select class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 outline-none hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer appearance-none pr-8 relative">
                    <option>All Languages</option>
                    <option>English</option>
                    <option>Hindi</option>
                </select>
            </div>
        </div>
    </div>

    <main class="flex-1 px-8 py-10 space-y-16">
        <section>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-300">Now Showing in <?php echo htmlspecialchars($selected_city); ?></h2>
                <div class="flex gap-2">
                    <button onclick="scrollCarousel('now-showing-list', 'left')" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-gray-600 dark:text-gray-400 transition-colors">
                        <i data-lucide="chevron-left" class="w-6 h-6"></i>
                    </button>
                    <button onclick="scrollCarousel('now-showing-list', 'right')" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-gray-600 dark:text-gray-400 transition-colors">
                        <i data-lucide="chevron-right" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <div id="now-showing-list" class="flex flex-row flex-nowrap w-full gap-6 overflow-x-auto hide-scrollbar scroll-smooth pb-4">
                <?php if (!empty($now_showing)): ?>
                    <?php foreach($now_showing as $movie): ?>
                        <?php 
                            $poster = !empty($movie['poster_image']) ? 'admin/'.htmlspecialchars($movie['poster_image']) : 'https://via.placeholder.com/400x600?text=No+Poster'; 
                        ?>
                        <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="shrink-0 flex-none w-[220px] md:w-[260px] group cursor-pointer block">
                            <div class="rounded-xl overflow-hidden mb-3 h-[320px] md:h-[380px] bg-gray-200 dark:bg-gray-800 transition-colors duration-300 relative shadow-md">
                                <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 absolute inset-0">
                            </div>
                            <h3 class="font-bold text-[16px] text-gray-900 dark:text-white leading-tight mb-1 truncate transition-colors duration-300">
                                <?php echo htmlspecialchars($movie['title']); ?>
                            </h3>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 transition-colors duration-300">
                                <?php echo htmlspecialchars($movie['genre']); ?>
                            </p>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 dark:text-gray-400 w-full">No movies currently showing in <?php echo htmlspecialchars($selected_city); ?>.</p>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-300">Coming Soon</h2>
                <div class="flex gap-2">
                    <button onclick="scrollCarousel('coming-soon-list', 'left')" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-gray-600 dark:text-gray-400 transition-colors">
                        <i data-lucide="chevron-left" class="w-6 h-6"></i>
                    </button>
                    <button onclick="scrollCarousel('coming-soon-list', 'right')" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-gray-600 dark:text-gray-400 transition-colors">
                        <i data-lucide="chevron-right" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <div id="coming-soon-list" class="flex flex-row flex-nowrap w-full gap-6 overflow-x-auto hide-scrollbar scroll-smooth pb-4">
                <?php if (!empty($coming_soon)): ?>
                    <?php foreach($coming_soon as $movie): ?>
                        <?php 
                            $poster = !empty($movie['poster_image']) ? 'admin/'.htmlspecialchars($movie['poster_image']) : 'https://via.placeholder.com/400x600?text=No+Poster'; 
                        ?>
                        <a href="movie_details.php?id=<?php echo $movie['id']; ?>" class="shrink-0 flex-none w-[220px] md:w-[260px] group cursor-pointer block">
                            <div class="rounded-xl overflow-hidden mb-3 h-[320px] md:h-[380px] bg-gray-200 dark:bg-gray-800 transition-colors duration-300 relative shadow-md">
                                <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 absolute inset-0">
                            </div>
                            <h3 class="font-bold text-[16px] text-gray-900 dark:text-white leading-tight mb-1 truncate transition-colors duration-300">
                                <?php echo htmlspecialchars($movie['title']); ?>
                            </h3>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 transition-colors duration-300">
                                <?php echo htmlspecialchars($movie['genre']); ?>
                            </p>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 dark:text-gray-400 w-full">No upcoming movies at this time.</p>
                <?php endif; ?>
            </div>
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
        function scrollCarousel(containerId, direction) {
            const container = document.getElementById(containerId);
            const scrollAmount = 300;
            if (container) {
                container.scrollBy({
                    left: direction === 'left' ? -scrollAmount : scrollAmount,
                    behavior: 'smooth'
                });
            }
        }
        
        lucide.createIcons();

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

        const themeToggleBtn = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;

        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            htmlElement.classList.add('dark');
        } else {
            htmlElement.classList.remove('dark');
        }

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                const isDark = htmlElement.classList.contains('dark');
                if (isDark) {
                    htmlElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                } else {
                    htmlElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                }
            });
        }
    </script>
</body>
</html>