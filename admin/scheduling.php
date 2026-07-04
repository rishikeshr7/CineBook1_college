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
            
            <?php if (isset($_GET['error']) && $_GET['error'] == 'conflict'): ?>
                <div id="scheduling-alert" class="mb-8 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 dark:bg-red-900/20 dark:border-red-900/50 dark:text-red-400 font-medium flex items-start gap-3 shadow-sm transition-opacity duration-500">
                    <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 shrink-0"></i>
                    <div>
                        <h4 class="text-base font-bold mb-1">Scheduling Conflict Detected</h4>
                        <p class="text-sm opacity-90">This screen is already booked during that time. Please ensure there is at least a 55-minute gap between movies for cleaning and turnaround.</p>
                    </div>
                </div>
            <?php elseif (isset($_GET['success']) && $_GET['success'] == 'showtimeadded'): ?>
                <div id="scheduling-alert" class="mb-8 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-900/50 dark:text-green-400 font-medium flex items-center gap-3 shadow-sm transition-opacity duration-500">
                    <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
                    <p class="text-sm">Showtime successfully added to the schedule!</p>
                </div>
            <?php endif; ?>

            <script>
                const alertBox = document.getElementById('scheduling-alert');
                if (alertBox) {
                    setTimeout(() => {
                        alertBox.style.opacity = '0';
                        setTimeout(() => alertBox.remove(), 500);
                        
                        const url = new URL(window.location);
                        url.searchParams.delete('error');
                        url.searchParams.delete('success');
                        window.history.replaceState({}, '', url);
                    }, 4000);
                }
            </script>

            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold mb-1 text-gray-900 dark:text-white">Scheduling</h1>
                    <p class="text-gray-500 dark:text-textMuted text-sm">Manage movie showtimes and screen allocations</p>
                </div>
                <a href="add_showtime.php" class="flex items-center gap-2 bg-[#F5C518] hover:bg-yellow-500 text-black px-5 py-2.5 rounded-lg font-bold text-sm transition-colors shadow-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i> Add Showtime
                </a>
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

    <script>
        lucide.createIcons();

        // Theme Toggle Script
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

        // Delete Showtime Confirmation
        window.confirmDeleteShowtime = function(id) {
            if (confirm('Are you sure you want to delete this showtime? This action cannot be undone.')) {
                // Redirects to a deletion script, passing the ID
                window.location.href = `delete_showtime.php?id=${id}`;
            }
        }
    </script>
</body>
</html>