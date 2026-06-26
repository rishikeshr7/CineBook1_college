<?php
session_start();
require_once 'dbconnect.php';

// --- Dynamic Calendar Logic ---
$m = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');
$y = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');

if ($m < 1) { $m = 12; $y--; }
if ($m > 12) { $m = 1; $y++; }

$firstDayOfMonth = mktime(0, 0, 0, $m, 1, $y);
$daysInMonth = date('t', $firstDayOfMonth);
$startDayOfWeek = date('w', $firstDayOfMonth); 
$monthName = date('F', $firstDayOfMonth);

$prevM = $m - 1; $prevY = $y;
$nextM = $m + 1; $nextY = $y;

$isCurrentMonth = ($m == date('n') && $y == date('Y'));
$todayDate = date('j');


// --- Dynamic Database Queries ---

// 1. Fetch Statistics
$total_showtimes_query = $conn->query("SELECT COUNT(*) FROM showtimes");
$total_showtimes = $total_showtimes_query ? $total_showtimes_query->fetch_row()[0] : 0;

$month_showtimes_query = $conn->query("SELECT COUNT(*) FROM showtimes WHERE MONTH(show_date) = $m AND YEAR(show_date) = $y");
$month_showtimes = $month_showtimes_query ? $month_showtimes_query->fetch_row()[0] : 0;

$active_movies_query = $conn->query("SELECT COUNT(*) FROM movies WHERE status = 'Now Showing'");
$active_movies = $active_movies_query ? $active_movies_query->fetch_row()[0] : 0;

// 2. Fetch Movies for the Dropdown (Modal)
$movies_result = $conn->query("SELECT id, title FROM movies ORDER BY title ASC");

// 3. Fetch Showtimes for the Selected Month (Calendar & List)
$calendar_events = [];
$all_showtimes_list = [];

$stmt = $conn->prepare("
    SELECT s.*, m.title as movie_title 
    FROM showtimes s 
    LEFT JOIN movies m ON s.movie_id = m.id 
    WHERE MONTH(s.show_date) = ? AND YEAR(s.show_date) = ?
    ORDER BY s.show_date ASC, s.show_time ASC
");

if ($stmt) {
    $stmt->bind_param("ii", $m, $y);
    $stmt->execute();
    $showtimes_result = $stmt->get_result();

    while ($row = $showtimes_result->fetch_assoc()) {
        $day = (int)date('j', strtotime($row['show_date']));
        // Group by day for the calendar
        $calendar_events[$day][] = $row;
        // Keep a flat list for the bottom section
        $all_showtimes_list[] = $row;
    }
    $stmt->close();
}

// Group showtimes by Date string for the new list design
$grouped_showtimes = [];
foreach ($all_showtimes_list as $showtime) {
    $date_str = date('F j, Y', strtotime($showtime['show_date']));
    $grouped_showtimes[$date_str][] = $showtime;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBook Admin - Scheduling</title>
    
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
        ::-webkit-scrollbar-thumb { background: #444; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #666; }
        
        body, aside, div, header, input, button, table, tr, td, th {
            transition: background-color 0.2s, border-color 0.2s, color 0.2s;
        }

        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }
        ::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-bgMain text-gray-900 dark:text-gray-100 font-sans flex h-screen overflow-hidden">

    <?php include "sidebar.php"; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
            
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold mb-1 text-gray-900 dark:text-white">Scheduling</h1>
                    <p class="text-gray-500 dark:text-textMuted text-sm">Manage movie showtimes and screen allocations</p>
                </div>
                <button id="open-showtime-btn" class="flex items-center gap-2 bg-[#F5C518] hover:bg-yellow-500 text-black px-5 py-2.5 rounded-lg font-bold text-sm transition-colors shadow-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i> Add Showtime
                </button>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
                
                <div class="lg:col-span-2 bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-6 shadow-sm">
                    
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            <?php echo $monthName . ' ' . $y; ?>
                        </h2>
                        <div class="flex items-center gap-4">
                            <a href="?m=<?php echo $prevM; ?>&y=<?php echo $prevY; ?>" class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                <i data-lucide="chevron-left" class="w-5 h-5"></i>
                            </a>
                            <a href="?m=<?php echo date('n'); ?>&y=<?php echo date('Y'); ?>" class="text-xs font-semibold text-gray-500 hover:text-brand transition-colors">Today</a>
                            <a href="?m=<?php echo $nextM; ?>&y=<?php echo $nextY; ?>" class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                <i data-lucide="chevron-right" class="w-5 h-5"></i>
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-7 gap-2">
                        <?php foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day): ?>
                            <div class="text-center text-xs font-semibold text-gray-500 dark:text-textMuted pb-2"><?php echo $day; ?></div>
                        <?php endforeach; ?>

                        <?php for($i = 0; $i < $startDayOfWeek; $i++): ?>
                            <div class="min-h-[80px]"></div>
                        <?php endfor; ?>

                        <?php for($d = 1; $d <= $daysInMonth; $d++): ?>
                            <?php 
                                $isToday = ($isCurrentMonth && $d == $todayDate);
                                $cellClass = $isToday 
                                    ? "border-brand bg-brand/5 dark:bg-brand/10 dark:border-brand/40 shadow-sm" 
                                    : "border-gray-200 dark:border-borderMain bg-white dark:bg-[#151515] hover:border-gray-300 dark:hover:border-gray-600 transition-colors cursor-pointer";
                                $textClass = $isToday ? "text-brand" : "text-gray-900 dark:text-gray-100";
                            ?>
                            <div class="border rounded-lg min-h-[80px] p-2 flex flex-col gap-1 <?php echo $cellClass; ?>">
                                <span class="text-sm font-bold <?php echo $textClass; ?>"><?php echo $d; ?></span>
                                
                                <?php if (isset($calendar_events[$d])): ?>
                                    <?php 
                                        $count = 0;
                                        foreach($calendar_events[$d] as $event): 
                                            if ($count < 2): // Max 2 pills
                                                $time = date('H:i', strtotime($event['show_time']));
                                    ?>
                                        <div class="bg-brand/10 text-yellow-700 dark:text-brand text-[10px] font-bold px-1.5 py-0.5 rounded truncate" title="<?php echo htmlspecialchars($event['movie_title']); ?>">
                                            <?php echo $time; ?>
                                        </div>
                                    <?php 
                                            endif;
                                            $count++;
                                        endforeach; 
                                        
                                        if ($count > 2): 
                                    ?>
                                        <div class="text-[10px] text-gray-400 dark:text-textMuted text-center mt-0.5">+<?php echo ($count - 2); ?> more</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="lg:col-span-1 bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-6 shadow-sm flex flex-col gap-4">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Statistics</h2>
                    
                    <div class="bg-gray-50 dark:bg-inputBg border border-gray-100 dark:border-borderMain rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500 dark:text-textMuted mb-1">Total Showtimes</div>
                        <div class="text-2xl font-bold text-brand"><?php echo number_format($total_showtimes); ?></div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-inputBg border border-gray-100 dark:border-borderMain rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500 dark:text-textMuted mb-1">This Month</div>
                        <div class="text-2xl font-bold text-brand"><?php echo number_format($month_showtimes); ?></div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-inputBg border border-gray-100 dark:border-borderMain rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500 dark:text-textMuted mb-1">Active Movies</div>
                        <div class="text-2xl font-bold text-brand"><?php echo number_format($active_movies); ?></div>
                    </div>
                </div>
                
            </div>

            <div class="flex flex-col max-w-6xl">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">All Showtimes</h2>
                
                <div class="overflow-y-auto max-h-[600px] pr-2 custom-scrollbar flex flex-col gap-8 pb-10">
                    
                    <?php if (!empty($grouped_showtimes)): ?>
                        <?php foreach($grouped_showtimes as $date_str => $shows): ?>
                            <div class="flex relative">
                                <div class="w-1 bg-[#F5C518] rounded-full mr-5 shrink-0 mt-1.5 mb-1.5"></div>
                                
                                <div class="flex-1 flex flex-col min-w-0">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
                                        <?php echo $date_str; ?>
                                    </div>
                                    
                                    <div class="flex flex-col gap-3">
                                        <?php foreach($shows as $showtime): ?>
                                            <?php $display_time = date('H:i', strtotime($showtime['show_time'])); ?>
                                            
                                            <div class="bg-white dark:bg-[#141414] border border-gray-200 dark:border-[#222] rounded-xl p-5 flex justify-between items-center group hover:bg-gray-50 dark:hover:bg-[#1a1a1a] transition-colors shadow-sm dark:shadow-none">
                                                
                                                <div class="flex flex-col gap-0.5 truncate pr-4">
                                                    <span class="font-bold text-gray-900 dark:text-white text-base truncate">
                                                        <?php echo htmlspecialchars($showtime['movie_title'] ?? 'Unknown Movie'); ?>
                                                    </span>
                                                    
                                                    <span class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                        <span class="text-yellow-600 dark:text-[#F5C518] font-medium">
                                                            <?php echo htmlspecialchars($showtime['city'] ?? 'Mumbai'); ?> • <?php echo htmlspecialchars($showtime['theater_id']); ?> • <?php echo htmlspecialchars($showtime['screen_id']); ?>
                                                        </span>
                                                        • <?php echo $display_time; ?> • <?php echo htmlspecialchars($showtime['format']); ?> • <?php echo htmlspecialchars($showtime['language']); ?>
                                                    </span>
                                                    
                                                    <span class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                        0/<?php echo (int)$showtime['total_seats']; ?> seats available
                                                    </span>
                                                </div>
                                                
                                                <div class="flex items-center gap-5 shrink-0 pl-2">
                                                    <a href="edit_showtime.php?id=<?php echo $showtime['id']; ?>" class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors" title="Edit Showtime">
                                                        <i data-lucide="edit" class="w-[18px] h-[18px]"></i>
                                                    </a>
                                                    
                                                    <button onclick="confirmDeleteShowtime(<?php echo $showtime['id']; ?>)" class="text-red-900 dark:text-red-500/80 hover:text-red-600 dark:hover:text-red-500 transition-colors" title="Delete Showtime">
                                                        <i data-lucide="trash-2" class="w-[18px] h-[18px]"></i>
                                                    </button>
                                                </div>
                                                
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-10 text-gray-500 dark:text-textMuted bg-white dark:bg-bgCard rounded-xl border border-dashed border-gray-200 dark:border-borderMain">
                            No showtimes scheduled for this month.
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </main>

    <div id="showtime-modal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex justify-center items-center p-4 transition-opacity">
        <div class="bg-bgCard w-full max-w-2xl rounded-xl shadow-2xl border border-borderMain flex flex-col max-h-[95vh]">
            
            <div class="p-6 flex justify-between items-center border-b border-borderMain shrink-0">
                <h3 class="text-2xl font-bold text-white">Add New Showtime</h3>
                <button class="close-modal-btn text-gray-400 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                <form id="add-showtime-form" action="add_showtime.php" method="POST" class="space-y-5">
                    
                    <div>
                        <label class="block text-sm font-bold mb-2 text-white">Movie <span class="text-red-500">*</span></label>
                        <select name="movie_id" required class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            <option value="" disabled selected>Select Movie</option>
                            <?php 
                                // Dynamically populate dropdown from DB
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
                            <label class="block text-sm font-bold mb-2 text-white">City <span class="text-red-500">*</span></label>
                            <select id="city-select" name="city" required class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
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
                            <label class="block text-sm font-bold mb-2 text-white">Theater <span class="text-red-500">*</span></label>
                            <select id="theater-select" name="theater_id" required class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                <option value="" disabled selected>Select city first</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold mb-2 text-white">Screen</label>
                            <select name="screen_id" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
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
                            <label class="block text-sm font-bold mb-2 text-white">Format</label>
                            <select name="format" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
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
                            <label class="block text-sm font-bold mb-2 text-white">Language</label>
                            <select name="language" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                <option>English</option>
                                <option>Hindi</option>
                                <option>Bengali</option>
                                <option>Kannada</option>
                                <option>Tamil</option>

                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2 text-white">Date <span class="text-red-500">*</span></label>
                            <input type="date" name="show_date" required class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold mb-2 text-white">Time <span class="text-red-500">*</span></label>
                            <input type="time" name="show_time" value="18:00" required class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2 text-white">Total Seats</label>
                            <input type="number" name="total_seats" value="200" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-white mt-2 mb-3">Pricing</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold mb-2 text-gray-300">Regular </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-400 font-semibold text-sm">₹</span>
                                    </div>
                                    <input type="number" name="price_regular" value="150" min="0" step="0.5" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg pl-8 pr-3 py-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-2 text-gray-300">Premium </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-400 font-semibold text-sm">₹</span>
                                    </div>
                                    <input type="number" name="price_premium" value="200" min="0" step="0.5" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg pl-8 pr-3 py-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-2 text-gray-300">VIP </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-400 font-semibold text-sm">₹</span>
                                    </div>
                                    <input type="number" name="price_vip" value="300" min="0" step="0.5" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg pl-8 pr-3 py-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 flex justify-between items-center border-t border-borderMain mt-6">
                        <button type="button" class="close-modal-btn text-white text-sm font-bold hover:text-gray-300 transition-colors px-4 py-2">
                            Cancel
                        </button>
                        <button type="submit" class="w-[200px] py-3 rounded-lg bg-brand text-black text-sm font-bold hover:bg-yellow-500 transition-colors shadow-md">
                            Add Showtime
                        </button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Theme Toggle Script
        const themeToggle = document.getElementById('toggle-theme');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
            });
        }

        // Modal Logic
        const modal = document.getElementById('showtime-modal');
        const openBtn = document.getElementById('open-showtime-btn');
        const closeBtns = document.querySelectorAll('.close-modal-btn');

        openBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
        });

        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                modal.classList.add('hidden');
            });
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    
        // Delete Showtime Confirmation
        window.confirmDeleteShowtime = function(id) {
            if (confirm('Are you sure you want to delete this showtime? This action cannot be undone.')) {
                // Redirects to a deletion script, passing the ID
                window.location.href = `delete_showtime.php?id=${id}`;
            }
        }

        // --- Dynamic City to Theater Logic ---
        const citySelect = document.getElementById('city-select');
        const theaterSelect = document.getElementById('theater-select');

        // Dictionary mapping cities to their respective theaters
        const theatersByCity = {
            "Mumbai": ["PVR Juhu", "INOX Nariman Point", "Cinepolis Andheri", "Carnival Cinemas Mumbai", "PVR IMAX Lower Parel", "PVR Gold Juhu"],
            "Delhi": ["PVR Select Citywalk", "INOX Nehru Place", "Cinepolis Saket", "PVR IMAX Saket", "Carnival Cinemas Delhi", "PVR Director's Cut"],
            "Bangalore": ["PVR Forum Mall", "INOX Garuda Mall", "Cinepolis ETA Mall", "PVR IMAX Koramangala"],
            "Kolkata": ["PVR South City", "INOX Quest Mall", "Cinepolis Acropolis", "Carnival Cinemas Salt Lake"],
            "Chennai": ["PVR VR Mall", "INOX Citi Centre", "Cinepolis BSR Mall", "PVR IMAX VR Mall"],
            "Hyderabad": ["PVR Inorbit Mall", "INOX GVK One", "Cinepolis CCPL Mall", "PVR Gold Banjara Hills"],
            "Pune": ["PVR Phoenix Mall", "INOX Amanora", "Cinepolis Westend", "Carnival Cinemas Kalyani Nagar"],
            "Ahmedabad": ["PVR Acropolis", "INOX Himalaya Mall", "Cinepolis AlphaOne"]
        };

        if (citySelect && theaterSelect) {
            citySelect.addEventListener('change', function() {
                const selectedCity = this.value;
                
                // Clear current options
                theaterSelect.innerHTML = '<option value="" disabled selected>Select Theater</option>';
                
                // Populate new options based on selected city
                if (selectedCity && theatersByCity[selectedCity]) {
                    theatersByCity[selectedCity].forEach(function(theater) {
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
    </script>
</body>
</html>