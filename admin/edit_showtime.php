<?php
session_start();
require_once 'dbconnect.php';

$error_message = '';

// --- 1. HANDLE FORM SUBMISSION (UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $movie_id      = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
    $city          = trim($_POST['city'] ?? '');
    $theater_id    = trim($_POST['theater_id'] ?? '');
    $screen_id     = trim($_POST['screen_id'] ?? '');
    $format        = trim($_POST['format'] ?? '');
    $language      = trim($_POST['language'] ?? '');
    $show_date     = trim($_POST['show_date'] ?? '');
    $show_time     = trim($_POST['show_time'] ?? '');
    $total_seats   = isset($_POST['total_seats']) ? (int)$_POST['total_seats'] : 0;
    $price_regular = isset($_POST['price_regular']) ? (float)$_POST['price_regular'] : 0.00;
    $price_premium = isset($_POST['price_premium']) ? (float)$_POST['price_premium'] : 0.00;
    $price_vip     = isset($_POST['price_vip']) ? (float)$_POST['price_vip'] : 0.00;

    if ($id === 0 || $movie_id === 0 || empty($city) || empty($theater_id) || empty($show_date) || empty($show_time)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Prevent Overlaps & Enforce 55-Min Rule for Edit (excluding current showtime)
        function getMinutesFromDuration($durationStr) {
            $hours = 0; $minutes = 0;
            if (preg_match('/(\d+)h/', $durationStr, $matches)) { $hours = (int)$matches[1]; }
            if (preg_match('/(\d+)m/', $durationStr, $matches)) { $minutes = (int)$matches[1]; }
            return ($hours * 60) + $minutes;
        }

        $dur_stmt = $conn->prepare("SELECT duration, release_date FROM movies WHERE id = ? LIMIT 1");
        $dur_stmt->bind_param("i", $movie_id);
        $dur_stmt->execute();
        $dur_res = $dur_stmt->get_result();
        $new_movie = $dur_res->fetch_assoc();
        $dur_stmt->close();

        $prerelease_error = false;
        if (!empty($new_movie['release_date']) && strtotime($show_date) < strtotime($new_movie['release_date'])) {
            $prerelease_error = true;
            $error_message = 'Cannot schedule before the release date (' . date('d M Y', strtotime($new_movie['release_date'])) . ').';
        }

        if (!$prerelease_error) {
            $new_dur_mins = getMinutesFromDuration($new_movie['duration'] ?? '0h 0m');
            $new_start = strtotime("$show_date $show_time");
            $new_end = $new_start + (($new_dur_mins + 55) * 60);

            $conflict_sql = "
                SELECT s.id, s.show_date, s.show_time, m.duration 
                FROM showtimes s
                JOIN movies m ON s.movie_id = m.id
                WHERE s.theater_id = ? AND s.screen_id = ? 
                AND s.id != ?
                AND s.show_date BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 1 DAY)
            ";
            $conflict_stmt = $conn->prepare($conflict_sql);
            $conflict_stmt->bind_param("ssiss", $theater_id, $screen_id, $id, $show_date, $show_date);
            $conflict_stmt->execute();
            $conflict_res = $conflict_stmt->get_result();

            $has_conflict = false;
            $max_existing_end = 0;
            while ($row = $conflict_res->fetch_assoc()) {
                $ex_start = strtotime($row['show_date'] . ' ' . $row['show_time']);
                $ex_dur_mins = getMinutesFromDuration($row['duration'] ?? '0h 0m');
                $ex_end = $ex_start + (($ex_dur_mins + 55) * 60);

                if ($new_start < $ex_end && $new_end > $ex_start) {
                    $has_conflict = true;
                    if ($ex_end > $max_existing_end) {
                        $max_existing_end = $ex_end;
                    }
                }
            }
            $conflict_stmt->close();

            if ($has_conflict) {
                $recommended_time = date('H:i', $max_existing_end);
                $error_message = "Scheduling Conflict Detected! The screen is already booked. Recommended Time: " . $recommended_time;
            } else {
                $sql = "UPDATE showtimes SET movie_id=?, city=?, theater_id=?, screen_id=?, format=?, language=?, show_date=?, show_time=?, total_seats=?, price_regular=?, price_premium=?, price_vip=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param("isssssssidddi", 
                        $movie_id, $city, $theater_id, $screen_id, $format, $language, 
                        $show_date, $show_time, $total_seats, 
                        $price_regular, $price_premium, $price_vip, $id
                    );

                    if ($stmt->execute()) {
                        $m = date('n', strtotime($show_date));
                        $y = date('Y', strtotime($show_date));
                        header("Location: scheduling.php?success=showtimeupdated&m=$m&y=$y");
                        exit();
                    } else {
                        $error_message = "Failed to update the showtime. Please try again.";
                    }
                    $stmt->close();
                } else {
                    $error_message = "Database error: Could not prepare statement.";
                }
            }
        }
    }
}

// --- 2. FETCH EXISTING DATA FOR THE FORM (GET) ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    if (empty($error_message)) {
        header("Location: scheduling.php?error=invalidid");
        exit();
    }
}

$showtime_id = $_GET['id'];

// If POST failed, we still want to fetch original or use POST data, but for simplicity we re-fetch if no error_message, 
// or use the original values from DB if there was an error to allow them to correct it.
$stmt = $conn->prepare("SELECT * FROM showtimes WHERE id = ?");
$stmt->bind_param("i", $showtime_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: scheduling.php?error=notfound");
    exit();
}

$showtime = $result->fetch_assoc();
$stmt->close();

$movies_result = $conn->query("SELECT id, title FROM movies ORDER BY title ASC");

// Define cities array to help with dropdown populating
$cities = ['Mumbai', 'Delhi', 'Bangalore', 'Kolkata', 'Chennai', 'Hyderabad', 'Pune', 'Ahmedabad'];

// We need to pass the selected theater to JS so it can pre-select it
$selected_city = $showtime['city'] ?? '';
$selected_theater = htmlspecialchars($showtime['theater_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Showtime - CineBook Admin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bgMain:      '#0a0a0a',
                        bgCard:      '#121212',
                        inputBg:     '#1a1a1a',
                        borderMain:  '#262626',
                        inputBorder: '#333333',
                        brand:       '#F5C518',
                        textMuted:   '#a3a3a3',
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
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }
        
        ::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.5;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-bgMain text-gray-900 dark:text-gray-100 font-sans flex h-screen overflow-hidden">

    <?php include "sidebar.php"; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">

            <div class="max-w-4xl mx-auto w-full">
                <header class="mb-8 flex items-center gap-4">
                    <a href="scheduling.php" class="p-2 rounded-lg bg-gray-200 dark:bg-borderMain hover:bg-gray-300 dark:hover:bg-gray-700 transition-colors">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold mb-1 text-gray-900 dark:text-white">Edit Showtime</h1>
                        <p class="text-gray-500 dark:text-textMuted text-sm">Update details for this specific screening</p>
                    </div>
                </header>

                <?php if (!empty($error_message)): ?>
                    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 dark:bg-red-900/20 dark:border-red-900/50 dark:text-red-400 font-medium shadow-sm flex items-start gap-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 shrink-0"></i>
                        <div>
                            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-6 sm:p-8 shadow-sm">
                    <form action="edit_showtime.php?id=<?php echo $showtime_id; ?>" method="POST" class="space-y-6">
                        
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($showtime['id']); ?>">

                        <div>
                            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Movie <span class="text-red-500">*</span></label>
                            <select name="movie_id" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                <option value="" disabled>Select Movie</option>
                                <?php 
                                    if ($movies_result && $movies_result->num_rows > 0) {
                                        while($m_row = $movies_result->fetch_assoc()) {
                                            $selected = ($m_row['id'] == $showtime['movie_id']) ? 'selected' : '';
                                            echo '<option value="' . $m_row['id'] . '" ' . $selected . '>' . htmlspecialchars($m_row['title']) . '</option>';
                                        }
                                    }
                                ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">City <span class="text-red-500">*</span></label>
                                <select id="city-select" name="city" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    <option value="" disabled>Select City</option>
                                    <option value="Mumbai" <?php if($selected_city == 'Mumbai') echo 'selected'; ?>>Mumbai - Maharashtra</option>
                                    <option value="Delhi" <?php if($selected_city == 'Delhi') echo 'selected'; ?>>Delhi - Delhi</option>
                                    <option value="Bangalore" <?php if($selected_city == 'Bangalore') echo 'selected'; ?>>Bangalore - Karnataka</option>
                                    <option value="Kolkata" <?php if($selected_city == 'Kolkata') echo 'selected'; ?>>Kolkata - West Bengal</option>
                                    <option value="Chennai" <?php if($selected_city == 'Chennai') echo 'selected'; ?>>Chennai - Tamil Nadu</option>
                                    <option value="Hyderabad" <?php if($selected_city == 'Hyderabad') echo 'selected'; ?>>Hyderabad - Telangana</option>
                                    <option value="Pune" <?php if($selected_city == 'Pune') echo 'selected'; ?>>Pune - Maharashtra</option>
                                    <option value="Ahmedabad" <?php if($selected_city == 'Ahmedabad') echo 'selected'; ?>>Ahmedabad - Gujarat</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Theater <span class="text-red-500">*</span></label>
                                <select id="theater-select" name="theater_id" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    <!-- Populated by JS based on City -->
                                    <option value="<?php echo htmlspecialchars($showtime['theater_id']); ?>" selected><?php echo htmlspecialchars($showtime['theater_id']); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Screen</label>
                                <select name="screen_id" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    <option <?php echo ($showtime['screen_id'] == 'Audi 1') ? 'selected' : ''; ?>>Audi 1</option>
                                    <option <?php echo ($showtime['screen_id'] == 'Audi 2') ? 'selected' : ''; ?>>Audi 2</option>
                                    <option <?php echo ($showtime['screen_id'] == 'Audi 3') ? 'selected' : ''; ?>>Audi 3</option>
                                    <option <?php echo ($showtime['screen_id'] == 'Audi 4') ? 'selected' : ''; ?>>Audi 4</option>
                                    <option <?php echo ($showtime['screen_id'] == 'Audi 5') ? 'selected' : ''; ?>>Audi 5</option>
                                    <option <?php echo ($showtime['screen_id'] == 'Gold Class') ? 'selected' : ''; ?>>Gold Class</option>
                                    <option <?php echo ($showtime['screen_id'] == 'Platinum Lounge') ? 'selected' : ''; ?>>Platinum Lounge</option>
                                    <option <?php echo ($showtime['screen_id'] == "Director's Cut") ? 'selected' : ''; ?>>Director's Cut</option>
                                    <option <?php echo ($showtime['screen_id'] == 'Royal Recliner') ? 'selected' : ''; ?>>Royal Recliner</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Format</label>
                                <select name="format" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    <option <?php echo ($showtime['format'] == '2D') ? 'selected' : ''; ?>>2D</option>
                                    <option <?php echo ($showtime['format'] == '3D') ? 'selected' : ''; ?>>3D</option>
                                    <option <?php echo ($showtime['format'] == 'IMAX 2D') ? 'selected' : ''; ?>>IMAX 2D</option>
                                    <option <?php echo ($showtime['format'] == 'IMAX 3D') ? 'selected' : ''; ?>>IMAX 3D</option>
                                    <option <?php echo ($showtime['format'] == '4DX') ? 'selected' : ''; ?>>4DX</option>
                                    <option <?php echo ($showtime['format'] == 'IMAX Laser') ? 'selected' : ''; ?>>IMAX Laser</option>
                                    <option <?php echo ($showtime['format'] == 'Dolby Atmos') ? 'selected' : ''; ?>>Dolby Atmos</option>
                                    <option <?php echo ($showtime['format'] == 'ICE') ? 'selected' : ''; ?>>ICE</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Language</label>
                                <select name="language" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    <option <?php echo ($showtime['language'] == 'English') ? 'selected' : ''; ?>>English</option>
                                    <option <?php echo ($showtime['language'] == 'Hindi') ? 'selected' : ''; ?>>Hindi</option>
                                    <option <?php echo ($showtime['language'] == 'Bengali') ? 'selected' : ''; ?>>Bengali</option>
                                    <option <?php echo ($showtime['language'] == 'Kannada') ? 'selected' : ''; ?>>Kannada</option>
                                    <option <?php echo ($showtime['language'] == 'Tamil') ? 'selected' : ''; ?>>Tamil</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Date <span class="text-red-500">*</span></label>
                                <input type="date" name="show_date" value="<?php echo htmlspecialchars($showtime['show_date']); ?>" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Time <span class="text-red-500">*</span></label>
                                <input type="time" name="show_time" value="<?php echo date('H:i', strtotime($showtime['show_time'])); ?>" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Total Seats</label>
                                <input type="number" name="total_seats" value="<?php echo htmlspecialchars($showtime['total_seats']); ?>" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white mt-4 mb-4">Pricing</h4>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold mb-2 text-gray-500 dark:text-gray-400">Regular</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400 font-semibold text-sm">₹</span>
                                        </div>
                                        <input type="number" name="price_regular" value="<?php echo htmlspecialchars($showtime['price_regular']); ?>" min="0" step="0.5" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg pl-8 pr-3 py-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-2 text-gray-500 dark:text-gray-400">Premium</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400 font-semibold text-sm">₹</span>
                                        </div>
                                        <input type="number" name="price_premium" value="<?php echo htmlspecialchars($showtime['price_premium']); ?>" min="0" step="0.5" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg pl-8 pr-3 py-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-2 text-gray-500 dark:text-gray-400">VIP</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400 font-semibold text-sm">₹</span>
                                        </div>
                                        <input type="number" name="price_vip" value="<?php echo htmlspecialchars($showtime['price_vip']); ?>" min="0" step="0.5" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg pl-8 pr-3 py-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-200 dark:border-borderMain mt-8 flex justify-end gap-4">
                            <a href="scheduling.php" class="px-6 py-3 rounded-lg font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-borderMain transition-colors">
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

        // City to Theater dynamic dropdown logic
        const theatersByCity = {
            'Mumbai': ['PVR ICON, Andheri', 'INOX Megaplex, Malad', 'Cinepolis, Kurla', 'Sterling Cinema, Fort'],
            'Delhi': ["PVR Director's Cut, Vasant Kunj", 'INOX, Nehru Place', 'Carnival Cinemas, Connaught Place'],
            'Bangalore': ['PVR IMAX, Koramangala', 'Cinepolis, Bannerghatta', 'INOX, Whitefield'],
            'Kolkata': ['INOX South City', 'PVR Diamond Plaza', 'Cinepolis Acropolis'],
            'Chennai': ['PVR SPI Cinemas, Royapettah', 'INOX Citi Centre', 'AGS Cinemas, T Nagar'],
            'Hyderabad': ['AMB Cinemas, Gachibowli', 'PVR Inorbit Mall', 'Prasads IMAX'],
            'Pune': ['PVR Phoenix Marketcity', 'Cinepolis Westend', 'INOX Bund Garden'],
            'Ahmedabad': ['PVR Acropolis', 'Cinepolis AlphaOne', 'INOX Himalaya Mall']
        };

        const citySelect = document.getElementById('city-select');
        const theaterSelect = document.getElementById('theater-select');
        const preSelectedTheater = "<?php echo addslashes($selected_theater); ?>";

        function updateTheaters(city) {
            theaterSelect.innerHTML = '';
            
            if (theatersByCity[city]) {
                theatersByCity[city].forEach(theater => {
                    const option = document.createElement('option');
                    option.value = theater;
                    option.textContent = theater;
                    if (theater === preSelectedTheater) option.selected = true;
                    theaterSelect.appendChild(option);
                });
            } else {
                theaterSelect.innerHTML = '<option value="" disabled selected>Select city first</option>';
            }
        }

        if (citySelect) {
            citySelect.addEventListener('change', function() {
                updateTheaters(this.value);
            });
            // Initialize on load
            if (citySelect.value) {
                updateTheaters(citySelect.value);
            }
        }

        // Theme toggle (shared with sidebar)
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
    </script>
</body>
</html>