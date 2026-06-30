<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure a default city is set in the session
$current_city = isset($_SESSION['user_city']) ? $_SESSION['user_city'] : 'Mumbai';
if (isset($_GET['city'])) {
    $current_city = trim($_GET['city']);
    $_SESSION['user_city'] = $current_city;
}
?>

<header class="bg-white dark:bg-[#0a0a0a] border-b border-gray-200 dark:border-[#1a1a1a] sticky top-0 z-50 transition-colors duration-300">
    <div class="max-w-[1400px] mx-auto w-full px-6 md:px-12 py-3 flex items-center justify-between">
        
        <div class="flex items-center">
            <a href="index.php" class="flex items-center gap-2 cursor-pointer shrink-0">
                <div class="text-[#F5C518]">
                    <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <mask id="film-cutout-exact">
                            <rect width="24" height="24" fill="white"/>
                            <rect x="8.5" y="4" width="7" height="7" fill="black" rx="0.5"/>
                            <rect x="8.5" y="13" width="7" height="7" fill="black" rx="0.5"/>
                            <rect x="4" y="4" width="2" height="2.5" fill="black" rx="0.5"/>
                            <rect x="4" y="8.5" width="2" height="2.5" fill="black" rx="0.5"/>
                            <rect x="4" y="13" width="2" height="2.5" fill="black" rx="0.5"/>
                            <rect x="4" y="17.5" width="2" height="2.5" fill="black" rx="0.5"/>
                            <rect x="18" y="4" width="2" height="2.5" fill="black" rx="0.5"/>
                            <rect x="18" y="8.5" width="2" height="2.5" fill="black" rx="0.5"/>
                            <rect x="18" y="13" width="2" height="2.5" fill="black" rx="0.5"/>
                            <rect x="18" y="17.5" width="2" height="2.5" fill="black" rx="0.5"/>
                        </mask>
                        <rect x="1.5" y="1.5" width="21" height="21" rx="4" fill="#F5C518" mask="url(#film-cutout-exact)"/>
                    </svg>
                </div>
                <span class="text-xl md:text-2xl font-bold tracking-tight text-gray-900 dark:text-white hidden sm:block">CineBook</span>
            </a>

            <div class="relative group cursor-pointer ml-6 sm:ml-8 z-[60]">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors py-2">
                    <i data-lucide="map-pin" class="w-4 h-4 text-[#F5C518]"></i>
                    <span class="tracking-wide"><?php echo htmlspecialchars($current_city); ?></span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400 ml-1"></i>
                </div>
                
                <div class="absolute top-full left-0 mt-1 w-48 bg-white dark:bg-[#111111] border border-gray-200 dark:border-[#262626] rounded-xl shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 overflow-hidden">
                    <div class="py-2">
                        <?php 
                        $cities = ['Mumbai', 'Delhi', 'Bangalore', 'Kolkata', 'Chennai', 'Hyderabad', 'Pune', 'Ahmedabad'];
                        foreach($cities as $c): 
                            $active = ($c === $current_city) ? 'text-brand bg-gray-50 dark:bg-[#1a1a1a]' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#1a1a1a]';
                        ?>
                            <a href="?city=<?php echo urlencode($c); ?>" class="block px-4 py-2.5 text-sm font-medium transition-colors <?php echo $active; ?>">
                                <?php echo $c; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-5 md:gap-6 flex-1">
            
            <div class="hidden lg:flex items-center bg-gray-100 dark:bg-[#151515] border border-transparent dark:border-[#222222] rounded-xl px-4 py-2 w-[350px] transition-colors duration-300 relative z-[60]">
                <i data-lucide="search" class="w-4 h-4 text-gray-500 dark:text-gray-400 mr-2 shrink-0"></i>
                <input 
                    type="text" 
                    id="header-search-input"
                    placeholder="Search movies..." 
                    autocomplete="off"
                    class="bg-transparent border-none outline-none text-sm w-full placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white"
                >
                <div id="search-results-container" class="absolute top-[calc(100%+8px)] left-0 w-full bg-white dark:bg-[#111111] border border-gray-200 dark:border-[#262626] rounded-xl shadow-lg opacity-0 invisible transition-all duration-200 overflow-hidden">
                    <div id="search-results-list" class="max-h-[350px] overflow-y-auto"></div>
                </div>
            </div>

            <button id="theme-toggle" class="hover:text-gray-900 dark:hover:text-white text-gray-600 dark:text-gray-300 transition-colors">
                <i data-lucide="moon" id="theme-icon-moon" class="w-5 h-5 block"></i>
                <i data-lucide="sun" id="theme-icon-sun" class="w-5 h-5 hidden"></i>
            </button>
            
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                
                <?php 
                    $email = $_SESSION['user_email'];
                    $display_name = explode('@', $email)[0]; 
                ?>

                <div class="relative group cursor-pointer">
                    <div class="flex items-center gap-2 text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white transition-colors pb-1 pt-1">
                        <i data-lucide="user" class="w-5 h-5"></i>
                        <span class="text-sm font-semibold hidden md:block"><?php echo htmlspecialchars($display_name); ?></span>
                    </div>

                    <div class="absolute right-0 mt-1 w-56 bg-white dark:bg-[#111111] border border-gray-200 dark:border-[#262626] rounded-xl shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-[#262626]">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($display_name); ?>
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                                <?php echo htmlspecialchars($email); ?>
                            </p>
                        </div>
                        <div class="py-1 border-b border-gray-200 dark:border-[#262626]">
                            <a href="my_bookings.php" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#1a1a1a] transition-colors">
                                <i data-lucide="ticket" class="w-4 h-4"></i>
                                My Bookings
                            </a>
                        </div>
                        <div class="py-1">
                            <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-[#cc3333] hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>

            <?php else: ?>

                <div class="flex items-center gap-3 md:gap-4 pl-2 md:pl-4 border-l border-gray-200 dark:border-gray-800">
                    <a href="admin/admin_login.php" class="hidden md:inline-block px-5 py-2 text-sm font-semibold bg-transparent border border-gray-300 dark:border-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-white/10 text-gray-900 dark:text-white transition-colors text-center">
                        Admin Login
                    </a>
                    
                    <button id="open-signin-btn" class="px-5 py-2 text-sm font-semibold bg-transparent border border-gray-300 dark:border-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-white/10 text-gray-900 dark:text-white transition-colors">
                        Sign In
                    </button>
                </div>

            <?php endif; ?>
        </div>
    </div>
</header>

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
                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                <?php if (isset($_GET['error'])): ?>
                    <?php 
                    $signin_errors = ['wrongpassword', 'nouser', 'emptyfields', 'sqlerror'];
                    if (in_array($_GET['error'], $signin_errors)): 
                    ?>
                        <div class="bg-red-950/20 border border-red-900/50 text-red-400 text-xs font-semibold p-3 rounded-lg flex items-center gap-2 mb-4">
                            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                            <span>
                                <?php 
                                $err = $_GET['error'];
                                if ($err === 'wrongpassword') echo "Incorrect password. Please try again.";
                                elseif ($err === 'nouser') echo "No account found with this email.";
                                elseif ($err === 'emptyfields') echo "All fields are required.";
                                elseif ($err === 'sqlerror') echo "Database error. Please try again.";
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="space-y-2">
                    <label for="user-email" class="block text-sm font-semibold text-gray-200">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="mail" class="h-5 w-5 text-gray-500"></i>
                        </div>
                        <input type="email" id="user-email" name="email" required placeholder="Enter your email" 
                            class="block w-full pl-10 pr-3 py-2.5 border border-[#262626] rounded-lg bg-[#1a1a1a] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#F5C518] focus:border-transparent sm:text-sm transition-all">
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="user-password" class="block text-sm font-semibold text-gray-200">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="h-5 w-5 text-gray-500"></i>
                        </div>
                        <input type="password" id="user-password" name="password" required placeholder="Enter your password" 
                            class="block w-full pl-10 pr-3 py-2.5 border border-[#262626] rounded-lg bg-[#1a1a1a] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#F5C518] focus:border-transparent sm:text-sm transition-all">
                    </div>
                </div>

                <button type="submit" class="w-full flex justify-center py-3 px-4 rounded-lg text-sm font-bold text-black bg-[#F5C518] hover:bg-[#eab308] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#121212] focus:ring-[#F5C518] transition-colors mt-6">
                    Sign In
                </button>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-400">
                Don't have an account? <button type="button" id="switch-to-signup" class="text-[#F5C518] font-bold hover:underline cursor-pointer">Sign up</button>
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
                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                <?php if (isset($_GET['error'])): ?>
                    <?php 
                    $signup_errors = ['invalidemail', 'emailregistered', 'signup_emptyfields', 'signup_sqlerror'];
                    if (in_array($_GET['error'], $signup_errors)): 
                    ?>
                        <div class="bg-red-950/20 border border-red-900/50 text-red-400 text-xs font-semibold p-3 rounded-lg flex items-center gap-2 mb-4">
                            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                            <span>
                                <?php 
                                $err = $_GET['error'];
                                if ($err === 'invalidemail') echo "Invalid email format.";
                                elseif ($err === 'emailregistered') echo "This email is already registered. Please log in.";
                                elseif ($err === 'signup_emptyfields') echo "All fields are required.";
                                elseif ($err === 'signup_sqlerror') echo "Database error. Please try again.";
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="space-y-2">
                    <label for="reg-name" class="block text-sm font-semibold text-gray-200">Full Name</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="user" class="h-5 w-5 text-gray-500"></i>
                        </div>
                        <input type="text" id="reg-name" name="fullname" required placeholder="Enter your full name" 
                            class="block w-full pl-10 pr-3 py-2.5 border border-[#262626] rounded-lg bg-[#1a1a1a] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#F5C518] focus:border-transparent sm:text-sm transition-all">
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="reg-email" class="block text-sm font-semibold text-gray-200">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="mail" class="h-5 w-5 text-gray-500"></i>
                        </div>
                        <input type="email" id="reg-email" name="email" required placeholder="Enter your email" 
                            class="block w-full pl-10 pr-3 py-2.5 border border-[#262626] rounded-lg bg-[#1a1a1a] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#F5C518] focus:border-transparent sm:text-sm transition-all">
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="reg-phone" class="block text-sm font-semibold text-gray-200">Phone Number</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="phone" class="h-5 w-5 text-gray-500"></i>
                        </div>
                        <input type="tel" id="reg-phone" name="phone" required placeholder="Enter your phone number" 
                            class="block w-full pl-10 pr-3 py-2.5 border border-[#262626] rounded-lg bg-[#1a1a1a] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#F5C518] focus:border-transparent sm:text-sm transition-all">
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="reg-password" class="block text-sm font-semibold text-gray-200">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="h-5 w-5 text-gray-500"></i>
                        </div>
                        <input type="password" id="reg-password" name="password" required placeholder="Create a password" 
                            class="block w-full pl-10 pr-3 py-2.5 border border-[#262626] rounded-lg bg-[#1a1a1a] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#F5C518] focus:border-transparent sm:text-sm transition-all">
                    </div>
                </div>

                <button type="submit" class="w-full flex justify-center py-3 px-4 rounded-lg text-sm font-bold text-black bg-[#F5C518] hover:bg-[#eab308] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#121212] focus:ring-[#F5C518] transition-colors mt-6">
                    Create Account
                </button>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-400">
                Already have an account? <button type="button" id="switch-to-signin" class="text-[#F5C518] font-bold hover:underline cursor-pointer">Sign in</button>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') { lucide.createIcons(); }

    const openSigninBtn = document.getElementById('open-signin-btn');
    const signinModal = document.getElementById('signin-modal');
    const closeSigninBtn = document.getElementById('close-modal-btn');
    const signupModal = document.getElementById('signup-modal');
    const closeSignupBtn = document.getElementById('close-signup-btn');
    const switchToSignupBtn = document.getElementById('switch-to-signup');
    const switchToSigninBtn = document.getElementById('switch-to-signin');

    if (openSigninBtn && signinModal) {
        openSigninBtn.addEventListener('click', () => {
            signinModal.classList.remove('hidden'); signinModal.classList.add('flex');
        });
    }
    if (closeSigninBtn && signinModal) {
        closeSigninBtn.addEventListener('click', () => {
            signinModal.classList.add('hidden'); signinModal.classList.remove('flex');
        });
    }
    if (closeSignupBtn && signupModal) {
        closeSignupBtn.addEventListener('click', () => {
            signupModal.classList.add('hidden'); signupModal.classList.remove('flex');
        });
    }
    if (switchToSignupBtn) {
        switchToSignupBtn.addEventListener('click', () => {
            signinModal.classList.add('hidden'); signinModal.classList.remove('flex');
            signupModal.classList.remove('hidden'); signupModal.classList.add('flex');
        });
    }
    if (switchToSigninBtn) {
        switchToSigninBtn.addEventListener('click', () => {
            signupModal.classList.add('hidden'); signupModal.classList.remove('flex');
            signinModal.classList.remove('hidden'); signinModal.classList.add('flex');
        });
    }
    window.addEventListener('click', (e) => {
        if (e.target === signinModal) { signinModal.classList.add('hidden'); signinModal.classList.remove('flex'); }
        if (e.target === signupModal) { signupModal.classList.add('hidden'); signupModal.classList.remove('flex'); }
    });

    const themeToggleBtn = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;
    const iconMoon = document.getElementById('theme-icon-moon');
    const iconSun = document.getElementById('theme-icon-sun');

    const updateIcons = (isDark) => {
        if (iconMoon && iconSun) {
            if (isDark) {
                iconMoon.classList.add('hidden'); iconMoon.classList.remove('block');
                iconSun.classList.add('block'); iconSun.classList.remove('hidden');
            } else {
                iconSun.classList.add('hidden'); iconSun.classList.remove('block');
                iconMoon.classList.add('block'); iconMoon.classList.remove('hidden');
            }
        }
    };

    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        htmlElement.classList.add('dark'); updateIcons(true);
    } else {
        htmlElement.classList.remove('dark'); updateIcons(false);
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const isDark = htmlElement.classList.contains('dark');
            if (isDark) {
                htmlElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
                updateIcons(false);
            } else {
                htmlElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
                updateIcons(true);
            }
        });
    }

    // Automatically open correct modal if error is present in URL query string
    const urlParams = new URLSearchParams(window.location.search);
    const errorParam = urlParams.get('error');
    if (errorParam) {
        const signupErrors = ['invalidemail', 'emailregistered', 'signup_emptyfields', 'signup_sqlerror'];
        if (signupErrors.includes(errorParam)) {
            if (signupModal) {
                signupModal.classList.remove('hidden');
                signupModal.classList.add('flex');
            }
        } else {
            if (signinModal) {
                signinModal.classList.remove('hidden');
                signinModal.classList.add('flex');
            }
        }
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Live Search Functionality
    const searchInput = document.getElementById('header-search-input');
    const searchContainer = document.getElementById('search-results-container');
    const searchList = document.getElementById('search-results-list');
    let searchTimeout = null;

    if (searchInput && searchContainer && searchList) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            const currentCity = <?php echo json_encode($current_city); ?>;
            
            clearTimeout(searchTimeout);
            
            if (query.length === 0) {
                searchContainer.classList.add('opacity-0', 'invisible');
                searchList.innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`search_movies.php?q=${encodeURIComponent(query)}&city=${encodeURIComponent(currentCity)}`)
                    .then(res => res.json())
                    .then(data => {
                        searchList.innerHTML = '';
                        if (data.length === 0) {
                            searchList.innerHTML = `
                                <div class="px-4 py-3 text-sm font-semibold text-gray-500 dark:text-gray-400 text-center">
                                    No movies found for "${query}" in ${currentCity}.
                                </div>
                            `;
                        } else {
                            data.forEach(movie => {
                                const poster = movie.poster_image ? (movie.poster_image.startsWith('http') ? movie.poster_image : 'admin/' + movie.poster_image) : 'https://via.placeholder.com/100x150?text=No+Poster';
                                const badgeClass = movie.status === 'Now Showing' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                                
                                const a = document.createElement('a');
                                a.href = `movie_details.php?id=${movie.id}`;
                                a.className = "flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-[#1a1a1a] transition-colors border-b border-gray-100 dark:border-[#262626] last:border-0";
                                a.innerHTML = `
                                    <img src="${poster}" class="w-10 h-14 object-cover rounded shadow-sm shrink-0" alt="Poster">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">${movie.title}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">${movie.genre}</p>
                                    </div>
                                    <div class="shrink-0">
                                        <span class="text-[10px] font-bold px-2 py-1 rounded-md ${badgeClass}">${movie.status}</span>
                                    </div>
                                `;
                                searchList.appendChild(a);
                            });
                        }
                        searchContainer.classList.remove('opacity-0', 'invisible');
                    })
                    .catch(err => console.error(err));
            }, 250); 
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchContainer.contains(e.target)) {
                searchContainer.classList.add('opacity-0', 'invisible');
            }
        });
        
        // Show dropdown again if input is clicked and has text
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length > 0 && searchList.innerHTML !== '') {
                searchContainer.classList.remove('opacity-0', 'invisible');
            }
        });
    }
</script>