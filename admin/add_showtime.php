<?php
session_start();

// Include database connection
require_once 'dbconnect.php';

// Check if the form was actually submitted via AJAX (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Sanitize and retrieve input data (Added City here)
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

    // 2. Basic Validation for required fields (Added City validation here)
    if ($movie_id === 0 || empty($city) || empty($theater_id) || empty($show_date) || empty($show_time)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'type' => 'missingfields']);
        exit();
    }

    // 3. Prevent Overlaps & Enforce 55-Min Rule
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

    if (!empty($new_movie['release_date']) && strtotime($show_date) < strtotime($new_movie['release_date'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'type' => 'prerelease',
            'message' => 'Cannot schedule before the release date (' . date('d M Y', strtotime($new_movie['release_date'])) . ').'
        ]);
        exit();
    }

    $new_dur_mins = getMinutesFromDuration($new_movie['duration'] ?? '0h 0m');
    $new_start = strtotime("$show_date $show_time");
    $new_end = $new_start + (($new_dur_mins + 55) * 60);

    $conflict_sql = "
        SELECT s.show_date, s.show_time, m.duration 
        FROM showtimes s
        JOIN movies m ON s.movie_id = m.id
        WHERE s.theater_id = ? AND s.screen_id = ? 
        AND s.show_date BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 1 DAY)
    ";
    $conflict_stmt = $conn->prepare($conflict_sql);
    $conflict_stmt->bind_param("ssss", $theater_id, $screen_id, $show_date, $show_date);
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
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'type' => 'conflict',
            'recommended_time' => $recommended_time
        ]);
        exit();
    }

    // 4. Prepare the SQL INSERT query (Added city column here)
    $sql = "INSERT INTO showtimes (movie_id, city, theater_id, screen_id, format, language, show_date, show_time, total_seats, price_regular, price_premium, price_vip) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("isssssssiddd", 
            $movie_id, 
            $city,
            $theater_id, 
            $screen_id, 
            $format, 
            $language, 
            $show_date, 
            $show_time, 
            $total_seats, 
            $price_regular, 
            $price_premium, 
            $price_vip
        );

        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'type' => 'sqlfailed']);
        }
        $stmt->close();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'type' => 'sqlpreparefailed']);
    }
    exit();
}

// --- FULL PAGE HTML FOR GET REQUESTS ---
$movies_result = $conn->query("SELECT id, title FROM movies ORDER BY title ASC");
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
    <title>Add New Showtime - CineBook Admin</title>

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
                        <h1 class="text-3xl font-bold mb-1 text-gray-900 dark:text-white">Add New Showtime</h1>
                        <p class="text-gray-500 dark:text-textMuted text-sm">Schedule a new screening for a movie</p>
                    </div>
                </header>

                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-6 sm:p-8 shadow-sm">
                    <form id="add-showtime-form" action="add_showtime.php" method="POST" class="space-y-6">
                        
                        <div id="modal-alert-container"></div>

                        <div>
                            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Movie <span class="text-red-500">*</span></label>
                            <select name="movie_id" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                <option value="" disabled selected>Select Movie</option>
                                <?php 
                                    if ($movies_result && $movies_result->num_rows > 0) {
                                        while($m_row = $movies_result->fetch_assoc()) {
                                            echo '<option value="' . $m_row['id'] . '">' . htmlspecialchars($m_row['title']) . '</option>';
                                        }
                                    }
                                ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">City <span class="text-red-500">*</span></label>
                                <select id="city-select" name="city" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    <option value="" disabled selected>Select City</option>
                                    <option value="Mumbai">Mumbai - Maharashtra</option>
                                    <option value="Delhi">Delhi - Delhi</option>
                                    <option value="Bangalore">Bangalore - Karnataka</option>
                                    <option value="Kolkata">Kolkata - West Bengal</option>
                                    <option value="Chennai">Chennai - Tamil Nadu</option>
                                    <option value="Hyderabad">Hyderabad - Telangana</option>
                                    <option value="Pune">Pune - Maharashtra</option>
                                    <option value="Ahmedabad">Ahmedabad - Gujarat</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Theater <span class="text-red-500">*</span></label>
                                <select id="theater-select" name="theater_id" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    <option value="" disabled selected>Select city first</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Screen</label>
                                <select name="screen_id" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    <option>Audi 1</option>
                                    <option>Audi 2</option>
                                    <option>Audi 3</option>
                                    <option>Audi 4</option>
                                    <option>Audi 5</option>
                                    <option>Gold Class</option>
                                    <option>Platinum Lounge</option>
                                    <option>Director's Cut</option>
                                    <option>Royal Recliner</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Format</label>
                                <select name="format" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    <option>2D</option>
                                    <option>3D</option>
                                    <option>IMAX 2D</option>
                                    <option>IMAX 3D</option>
                                    <option>4DX</option>
                                    <option>IMAX Laser</option>
                                    <option>Dolby Atmos</option>
                                    <option>ICE</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Language</label>
                                <select name="language" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    <option>English</option>
                                    <option>Hindi</option>
                                    <option>Bengali</option>
                                    <option>Kannada</option>
                                    <option>Tamil</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Date <span class="text-red-500">*</span></label>
                                <input type="date" name="show_date" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Time <span class="text-red-500">*</span></label>
                                <input type="time" name="show_time" value="18:00" required class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Total Seats</label>
                                <input type="number" name="total_seats" value="200" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
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
                                        <input type="number" name="price_regular" value="150" min="0" step="0.5" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg pl-8 pr-3 py-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-2 text-gray-500 dark:text-gray-400">Premium</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400 font-semibold text-sm">₹</span>
                                        </div>
                                        <input type="number" name="price_premium" value="200" min="0" step="0.5" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg pl-8 pr-3 py-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-2 text-gray-500 dark:text-gray-400">VIP</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400 font-semibold text-sm">₹</span>
                                        </div>
                                        <input type="number" name="price_vip" value="300" min="0" step="0.5" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg pl-8 pr-3 py-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-200 dark:border-borderMain mt-8 flex justify-end gap-4">
                            <a href="scheduling.php" class="px-6 py-3 rounded-lg font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-borderMain transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-8 py-3 rounded-lg bg-[#F5C518] text-black font-bold hover:bg-yellow-500 transition-colors">
                                Add Showtime
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

        if (citySelect) {
            citySelect.addEventListener('change', function() {
                const city = this.value;
                theaterSelect.innerHTML = '';
                
                if (theatersByCity[city]) {
                    theatersByCity[city].forEach(theater => {
                        const option = document.createElement('option');
                        option.value = theater;
                        option.textContent = theater;
                        theaterSelect.appendChild(option);
                    });
                } else {
                    theaterSelect.innerHTML = '<option value="" disabled selected>Select city first</option>';
                }
            });
        }

        // AJAX Form Submission for Showtime
        const showtimeForm = document.getElementById('add-showtime-form');
        if (showtimeForm) {
            showtimeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const form = this;
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Processing...';
                submitBtn.disabled = true;
                
                fetch('add_showtime.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    const alertContainer = document.getElementById('modal-alert-container');
                    if (data.status === 'success') {
                        const dateVal = formData.get('show_date');
                        let urlParams = '';
                        if (dateVal) {
                            const d = new Date(dateVal);
                            urlParams = `&m=${d.getMonth() + 1}&y=${d.getFullYear()}`;
                        }
                        window.location.href = `scheduling.php?success=showtimeadded${urlParams}`;
                    } else if (data.status === 'error' && data.type === 'conflict') {
                        alertContainer.innerHTML = `
                            <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 dark:bg-red-900/20 dark:border-red-900/50 dark:text-red-400 font-medium flex flex-col gap-2 shadow-sm">
                                <div class="flex items-start gap-3">
                                    <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 shrink-0"></i>
                                    <div>
                                        <h4 class="text-base font-bold mb-1">Scheduling Conflict Detected</h4>
                                        <p class="text-sm opacity-90">This screen is already booked. Please ensure a 55-minute gap.</p>
                                    </div>
                                </div>
                                <div class="mt-2 pl-8 flex items-center justify-between bg-red-100 dark:bg-red-900/40 p-2 rounded-lg">
                                    <span class="text-sm">Recommended Time: <strong class="text-red-700 dark:text-red-300">${data.recommended_time}</strong></span>
                                    <button type="button" onclick="document.querySelector('input[name=\\'show_time\\']').value='${data.recommended_time}'; document.getElementById('modal-alert-container').innerHTML='';" class="text-xs font-bold bg-white dark:bg-[#1a1a1a] px-3 py-1.5 rounded-md hover:bg-gray-50 transition-colors border border-red-200 dark:border-red-800 shadow-sm text-gray-900 dark:text-white">Use this time</button>
                                </div>
                            </div>
                        `;
                        lucide.createIcons();
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    } else if (data.status === 'error' && data.type === 'prerelease') {
                        alertContainer.innerHTML = `
                            <div class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 dark:bg-amber-900/20 dark:border-amber-900/50 dark:text-amber-400 font-medium shadow-sm">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="calendar-x" class="w-5 h-5 shrink-0"></i>
                                    <span>${data.message}</span>
                                </div>
                            </div>
                        `;
                        lucide.createIcons();
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    } else {
                        alertContainer.innerHTML = `
                            <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 dark:bg-red-900/20 dark:border-red-900/50 dark:text-red-400 font-medium shadow-sm flex gap-2">
                                <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0"></i> 
                                An unexpected error occurred.
                            </div>
                        `;
                        lucide.createIcons();
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    document.getElementById('modal-alert-container').innerHTML = `
                        <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 dark:bg-red-900/20 dark:border-red-900/50 dark:text-red-400 font-medium shadow-sm flex gap-2">
                            <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0"></i> 
                            A network error occurred.
                        </div>
                    `;
                    lucide.createIcons();
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });
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