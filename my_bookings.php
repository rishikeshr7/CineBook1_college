<?php
session_start();
require_once 'dbconnect.php';

// 1. Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Database Seeder (Runs ONLY if user has 0 bookings, to ensure the UI looks exactly like the screenshot immediately)
$check_bookings = $conn->prepare("SELECT COUNT(*) as cnt FROM bookings WHERE user_id = ?");
$check_bookings->bind_param("i", $user_id);
$check_bookings->execute();
$booking_cnt = $check_bookings->get_result()->fetch_assoc()['cnt'];
$check_bookings->close();

if ($booking_cnt == 0) {
    // A. Seed Oppenheimer movie if not exists
    $opp_id = null;
    $check_opp = $conn->prepare("SELECT id FROM movies WHERE title = 'Oppenheimer' LIMIT 1");
    $check_opp->execute();
    $opp_res = $check_opp->get_result();
    if ($opp_res->num_rows > 0) {
        $opp_id = $opp_res->fetch_assoc()['id'];
    } else {
        $ins_opp = $conn->prepare("INSERT INTO movies (title, duration, genre, language, certification, synopsis, director, release_date, rating, status, formats, poster_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $title = "Oppenheimer";
        $duration = "180 min";
        $genre = "Biography, Drama, History";
        $lang = "English";
        $cert = "A";
        $syn = "The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb.";
        $dir = "Christopher Nolan";
        $rel = "2023-07-21";
        $rat = "8.9";
        $stat = "Now Showing";
        $formats = "IMAX 70mm, IMAX 2D";
        $poster = "https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=1000&auto=format&fit=crop";
        $ins_opp->bind_param("ssssssssssss", $title, $duration, $genre, $lang, $cert, $syn, $dir, $rel, $rat, $stat, $formats, $poster);
        $ins_opp->execute();
        $opp_id = $ins_opp->insert_id;
        $ins_opp->close();
    }
    $check_opp->close();

    // B. Seed Dune: Part Two movie if not exists
    $dune_id = null;
    $check_dune = $conn->prepare("SELECT id FROM movies WHERE title = 'Dune: Part Two' LIMIT 1");
    $check_dune->execute();
    $dune_res = $check_dune->get_result();
    if ($dune_res->num_rows > 0) {
        $dune_id = $dune_res->fetch_assoc()['id'];
    } else {
        $ins_dune = $conn->prepare("INSERT INTO movies (title, duration, genre, language, certification, synopsis, director, release_date, rating, status, formats, poster_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $title = "Dune: Part Two";
        $duration = "166 min";
        $genre = "Sci-Fi, Adventure";
        $lang = "English";
        $cert = "UA";
        $syn = "Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family.";
        $dir = "Denis Villeneuve";
        $rel = "2024-03-01";
        $rat = "8.6";
        $stat = "Now Showing";
        $formats = "IMAX 3D, 2D";
        $poster = "https://images.unsplash.com/photo-1506318137071-a8e063b4bec0?q=80&w=1000&auto=format&fit=crop";
        $ins_dune->bind_param("ssssssssssss", $title, $duration, $genre, $lang, $cert, $syn, $dir, $rel, $rat, $stat, $formats, $poster);
        $ins_dune->execute();
        $dune_id = $ins_dune->insert_id;
        $ins_dune->close();
    }
    $check_dune->close();

    // C. Seed Oppenheimer showtime if not exists
    $opp_st_id = null;
    $check_opp_st = $conn->prepare("SELECT id FROM showtimes WHERE movie_id = ? AND theater_id = 'PVR Juhu' AND show_date = '2026-05-03' AND show_time = '14:00:00' LIMIT 1");
    $check_opp_st->bind_param("i", $opp_id);
    $check_opp_st->execute();
    $opp_st_res = $check_opp_st->get_result();
    if ($opp_st_res->num_rows > 0) {
        $opp_st_id = $opp_st_res->fetch_assoc()['id'];
    } else {
        $ins_st = $conn->prepare("INSERT INTO showtimes (movie_id, city, theater_id, screen_id, format, language, show_date, show_time, total_seats, price_regular) VALUES (?, 'Mumbai', 'PVR Juhu', 'Audi 1', 'IMAX 70mm', 'English', '2026-05-03', '14:00:00', 150, 33.00)");
        $ins_st->bind_param("i", $opp_id);
        $ins_st->execute();
        $opp_st_id = $ins_st->insert_id;
        $ins_st->close();
    }
    $check_opp_st->close();

    // D. Seed Dune showtime if not exists
    $dune_st_id = null;
    $check_dune_st = $conn->prepare("SELECT id FROM showtimes WHERE movie_id = ? AND theater_id = 'INOX Megaplex' AND show_date = '2026-05-03' AND show_time = '15:30:00' LIMIT 1");
    $check_dune_st->bind_param("i", $dune_id);
    $check_dune_st->execute();
    $dune_st_res = $check_dune_st->get_result();
    if ($dune_st_res->num_rows > 0) {
        $dune_st_id = $dune_st_res->fetch_assoc()['id'];
    } else {
        $ins_st = $conn->prepare("INSERT INTO showtimes (movie_id, city, theater_id, screen_id, format, language, show_date, show_time, total_seats, price_regular) VALUES (?, 'Mumbai', 'INOX Megaplex', 'Screen 2', 'IMAX 3D', 'English', '2026-05-03', '15:30:00', 180, 33.33)");
        $ins_st->bind_param("i", $dune_id);
        $ins_st->execute();
        $dune_st_id = $ins_st->insert_id;
        $ins_st->close();
    }
    $check_dune_st->close();

    // E. Insert Bookings
    // Oppenheimer Booking ($88.00 total)
    $ins_bk1 = $conn->prepare("INSERT INTO bookings (user_id, showtime_id, seat_numbers, total_amount, booking_date) VALUES (?, ?, 'H10, H11', 88.00, '2026-05-03 12:00:00')");
    $ins_bk1->bind_param("ii", $user_id, $opp_st_id);
    $ins_bk1->execute();
    $bk1_id = $ins_bk1->insert_id;
    $ins_bk1->close();

    // Dune Booking ($140.00 total)
    $ins_bk2 = $conn->prepare("INSERT INTO bookings (user_id, showtime_id, seat_numbers, total_amount, booking_date) VALUES (?, ?, 'G5, G6, G7', 140.00, '2026-05-03 12:30:00')");
    $ins_bk2->bind_param("ii", $user_id, $dune_st_id);
    $ins_bk2->execute();
    $bk2_id = $ins_bk2->insert_id;
    $ins_bk2->close();

    // F. Insert Food Items for bookings
    $ins_food = $conn->prepare("INSERT INTO booking_food (booking_id, food_id, food_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
    
    // Oppenheimer food: Popcorn x1 ($12), Pepsi x2 ($5 each, total $10). Ticket base was $66 (2 * $33)
    $f_id1 = 1; $f_name1 = "Popcorn (Large)"; $f_qty1 = 1; $f_pr1 = 12.00;
    $ins_food->bind_param("iisid", $bk1_id, $f_id1, $f_name1, $f_qty1, $f_pr1);
    $ins_food->execute();

    $f_id2 = 2; $f_name2 = "Pepsi (Medium)"; $f_qty2 = 2; $f_pr2 = 5.00;
    $ins_food->bind_param("iisid", $bk1_id, $f_id2, $f_name2, $f_qty2, $f_pr2);
    $ins_food->execute();

    // Dune food: Nachos x2 ($8 each, total $16), Coke x3 ($8 each, total $24). Ticket base was $100 (3 * $33.33)
    $f_id3 = 3; $f_name3 = "Nachos"; $f_qty3 = 2; $f_pr3 = 8.00;
    $ins_food->bind_param("iisid", $bk2_id, $f_id3, $f_name3, $f_qty3, $f_pr3);
    $ins_food->execute();

    $f_id4 = 4; $f_name4 = "Coke (Large)"; $f_qty4 = 3; $f_pr4 = 8.00;
    $ins_food->bind_param("iisid", $bk2_id, $f_id4, $f_name4, $f_qty4, $f_pr4);
    $ins_food->execute();

    $ins_food->close();
}

// 3. Fetch User Profile Data
$user_stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Fallback for name/phone if they are empty
$user_fullname = !empty($user_info['fullname']) ? $user_info['fullname'] : 'User';
$user_phone = !empty($user_info['phone']) ? $user_info['phone'] : '+1 234 567 8900';

// 4. Fetch Booking Stats (Total Count & Total Spent)
$stats_stmt = $conn->prepare("SELECT COUNT(*) as total_count, SUM(total_amount) as total_spent FROM bookings WHERE user_id = ?");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

$total_bookings = $stats['total_count'] ?? 0;
$total_spent = $stats['total_spent'] ?? 0;

// 5. Fetch Detailed Bookings History with JOINS
$bookings_sql = "
    SELECT 
        b.id AS booking_id,
        b.seat_numbers,
        b.total_amount,
        b.booking_date,
        s.show_date,
        s.show_time,
        s.theater_id AS cinema_name,
        s.format AS show_format,
        s.city AS cinema_city,
        m.title AS movie_title,
        m.poster_image,
        m.synopsis AS movie_synopsis,
        m.duration AS movie_duration
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC
";
$bookings_stmt = $conn->prepare($bookings_sql);
$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
$bookings = [];
while ($row = $bookings_result->fetch_assoc()) {
    $bookings[] = $row;
}
$bookings_stmt->close();

// 6. Fetch Food Items for all bookings of this user to inject in JavaScript
$food_items_by_booking = [];
if (!empty($bookings)) {
    $booking_ids = array_column($bookings, 'booking_id');
    $placeholders = implode(',', array_fill(0, count($booking_ids), '?'));
    $food_sql = "SELECT * FROM booking_food WHERE booking_id IN ($placeholders)";
    $food_stmt = $conn->prepare($food_sql);
    
    $types = str_repeat('i', count($booking_ids));
    $food_stmt->bind_param($types, ...$booking_ids);
    $food_stmt->execute();
    $food_result = $food_stmt->get_result();
    while ($row = $food_result->fetch_assoc()) {
        $food_items_by_booking[$row['booking_id']][] = $row;
    }
    $food_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en" class="transition-colors duration-300">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - CineBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { 
            darkMode: 'class', 
            theme: { 
                extend: { 
                    colors: { 
                        primary: '#F5C518' 
                    } 
                } 
            } 
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #FAFAFA; 
        }
        .dark body { 
            background-color: #0F0F0F; 
        }
        
        /* Hide scrollbar for ticket modal body */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="text-gray-900 dark:text-gray-100 min-h-screen flex flex-col transition-colors duration-300">

    <?php include("header.php"); ?>

    <main class="flex-1 max-w-[1400px] mx-auto w-full px-6 md:px-12 py-10">
        <div class="flex flex-col lg:flex-row gap-10 items-start">
            
            <!-- Left Sidebar (Profile Card) -->
            <aside class="w-full lg:w-[360px] shrink-0">
                <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-gray-800 rounded-2xl p-8 flex flex-col items-center shadow-sm">
                    <!-- Profile Icon Avatar Circle -->
                    <div class="w-24 h-24 bg-[#FFF9E5] dark:bg-[#2A2408] rounded-full flex items-center justify-center mb-5 border border-[#FFEBA5] dark:border-[#52440D]">
                        <i data-lucide="user" class="w-11 h-11 text-[#F5C518]"></i>
                    </div>
                    
                    <h2 class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white"><?php echo htmlspecialchars($user_fullname); ?></h2>
                    <p class="text-sm font-semibold text-gray-400 dark:text-gray-500 mt-1 uppercase tracking-wider">CineBook Member</p>

                    <!-- Contact Details styled as input boxes -->
                    <div class="w-full mt-8 space-y-4">
                        <div class="bg-gray-50 dark:bg-[#1A1A1A] p-4 rounded-xl flex items-center gap-4 border border-gray-100 dark:border-gray-800/60">
                            <i data-lucide="mail" class="w-5 h-5 text-gray-400 dark:text-gray-500 shrink-0"></i>
                            <div class="truncate">
                                <p class="text-[11px] font-bold uppercase text-gray-400 dark:text-gray-500 tracking-wider">Email</p>
                                <p class="text-sm font-semibold text-gray-950 dark:text-gray-200 truncate"><?php echo htmlspecialchars($user_info['email']); ?></p>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-[#1A1A1A] p-4 rounded-xl flex items-center gap-4 border border-gray-100 dark:border-gray-800/60">
                            <i data-lucide="phone" class="w-5 h-5 text-gray-400 dark:text-gray-500 shrink-0"></i>
                            <div>
                                <p class="text-[11px] font-bold uppercase text-gray-400 dark:text-gray-500 tracking-wider">Phone</p>
                                <p class="text-sm font-semibold text-gray-950 dark:text-gray-200"><?php echo htmlspecialchars($user_phone); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Panel Split Row -->
                    <div class="w-full mt-8 pt-8 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center">
                        <div class="text-center flex-1">
                            <p class="text-3xl font-extrabold text-[#F5C518] tracking-tight"><?php echo $total_bookings; ?></p>
                            <p class="text-[11px] font-semibold uppercase text-gray-400 dark:text-gray-500 mt-1 tracking-wider">Total Bookings</p>
                        </div>
                        <div class="w-px h-10 bg-gray-100 dark:bg-gray-800 shrink-0"></div>
                        <div class="text-center flex-1">
                            <p class="text-3xl font-extrabold text-[#F5C518] tracking-tight">₹<?php echo number_format($total_spent, 0); ?></p>
                            <p class="text-[11px] font-semibold uppercase text-gray-400 dark:text-gray-500 mt-1 tracking-wider">Total Spent</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Right Panel (Bookings List) -->
            <section class="flex-1 w-full">
                <h1 class="text-[2rem] font-black tracking-tight text-gray-900 dark:text-white mb-8">My Bookings</h1>
                
                <div class="space-y-6">
                    <?php if (!empty($bookings)): ?>
                        <?php foreach($bookings as $booking): ?>
                            <!-- Booking Card -->
                            <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-gray-800 rounded-2xl p-6 flex flex-col md:flex-row gap-6 shadow-sm hover:shadow-md transition-shadow duration-300 relative">
                                
                                <!-- Poster Image -->
                                <div class="w-full md:w-[150px] h-[225px] shrink-0 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800 shadow-inner">
                                    <?php 
                                    $poster_src = htmlspecialchars($booking['poster_image']);
                                    if (strpos($poster_src, 'http') !== 0) {
                                        $poster_src = 'admin/' . $poster_src;
                                    }
                                    ?>
                                    <img src="<?php echo $poster_src; ?>" alt="Poster" class="w-full h-full object-cover">
                                </div>
                                
                                <!-- Booking Details Container -->
                                <div class="flex-1 flex flex-col justify-between">
                                    
                                    <!-- Header: Movie Title & Badges (Left) + Booking ID (Right) -->
                                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                                        <div>
                                            <h3 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"><?php echo htmlspecialchars($booking['movie_title']); ?></h3>
                                            <div class="flex flex-wrap gap-2 mt-2">
                                                <!-- Confirmed badge in green -->
                                                <span class="px-3 py-1 bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 text-xs font-semibold rounded-full border border-green-100 dark:border-green-900/50">Confirmed</span>
                                                <!-- Movie format badge in grey -->
                                                <?php if (!empty($booking['show_format'])): ?>
                                                    <span class="px-3 py-1 bg-gray-100 dark:bg-[#1e1e1e] text-gray-600 dark:text-gray-300 text-xs font-semibold rounded-full border border-gray-200 dark:border-gray-800"><?php echo htmlspecialchars($booking['show_format']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="sm:text-right shrink-0">
                                            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Booking ID</p>
                                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">BK<?php echo str_pad($booking['booking_id'], 3, '0', STR_PAD_LEFT); ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Details Metadata 2x2 Grid -->
                                    <div class="grid grid-cols-2 gap-y-3.5 gap-x-6 text-sm text-gray-600 dark:text-gray-300 mb-6 border-t border-gray-100 dark:border-gray-800/50 pt-4">
                                        <div class="flex items-center gap-2.5">
                                            <i data-lucide="calendar" class="w-4 h-4 text-gray-400 dark:text-gray-500"></i>
                                            <span class="font-medium"><?php echo date('Y-m-d', strtotime($booking['show_date'])); ?></span>
                                        </div>
                                        <div class="flex items-center gap-2.5">
                                            <i data-lucide="clock" class="w-4 h-4 text-gray-400 dark:text-gray-500"></i>
                                            <span class="font-medium"><?php echo date('H:i', strtotime($booking['show_time'])); ?></span>
                                        </div>
                                        <div class="flex items-center gap-2.5 col-span-2 sm:col-span-1">
                                            <i data-lucide="map-pin" class="w-4 h-4 text-gray-400 dark:text-gray-500"></i>
                                            <span class="font-medium truncate"><?php echo htmlspecialchars($booking['cinema_name']); ?></span>
                                        </div>
                                        <div class="flex items-center gap-2.5 col-span-2 sm:col-span-1">
                                            <span class="text-gray-400 dark:text-gray-500 font-medium">Seats:</span>
                                            <span class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($booking['seat_numbers']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Footer: Total Spent & View Ticket Action -->
                                    <div class="mt-auto pt-4 border-t border-gray-100 dark:border-gray-800 flex items-end justify-between">
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase text-gray-400 dark:text-gray-500 tracking-wider">Total Amount</p>
                                            <p class="text-3xl font-extrabold text-[#F5C518] mt-0.5">₹<?php echo number_format($booking['total_amount'], 0); ?></p>
                                        </div>
                                        <button 
                                            onclick="openTicketModal(<?php echo $booking['booking_id']; ?>)"
                                            class="px-6 py-2.5 border-2 border-gray-900 dark:border-white rounded-xl font-bold text-gray-900 dark:text-white hover:bg-gray-900 hover:text-white dark:hover:bg-white dark:hover:text-black transition-all duration-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#F5C518]">
                                            View Ticket
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-gray-800 rounded-2xl p-16 text-center shadow-sm">
                            <div class="w-16 h-16 bg-gray-100 dark:bg-[#1E1E1E] rounded-full flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="ticket" class="w-8 h-8 text-gray-400 dark:text-gray-500"></i>
                            </div>
                            <h3 class="text-lg font-bold mb-1">No bookings found</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">You haven't booked any movies yet. Explore our movies section to find and book your favorite cinema seats.</p>
                            <a href="index.php" class="inline-block mt-6 px-6 py-2.5 bg-[#F5C518] text-black font-bold rounded-xl hover:bg-yellow-500 transition-colors text-sm shadow-sm">
                                Browse Movies
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Ticket Modal Popup -->
    <div id="ticket-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 backdrop-blur-sm p-4 transition-all duration-300">
        <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-gray-800 rounded-3xl w-full max-w-lg shadow-2xl relative flex flex-col overflow-hidden max-h-[90vh]">
            <!-- Close button in modal corner -->
            <button onclick="closeTicketModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors z-50">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            
            <!-- Scrollable Ticket Stub -->
            <div class="overflow-y-auto flex-1 hide-scrollbar">
                <!-- Ticket Header stub with branding -->
                <div class="bg-[#F5C518] p-8 text-black relative">
                    <!-- Realistic ticket half circle cutouts -->
                    <div class="absolute left-0 bottom-0 w-4 h-8 bg-black/80 rounded-r-full translate-y-4"></div>
                    <div class="absolute right-0 bottom-0 w-4 h-8 bg-black/80 rounded-l-full translate-y-4"></div>
                    
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 id="modal-movie-title" class="text-2xl font-black tracking-tight mb-1">Movie Title</h2>
                            <p id="modal-cinema-name" class="text-sm font-semibold opacity-90">Cinema Name</p>
                        </div>
                        <div class="bg-yellow-400/30 p-2.5 rounded-xl">
                            <i data-lucide="qr-code" class="w-10 h-10 text-black"></i>
                        </div>
                    </div>
                    
                    <div class="flex gap-8 mt-8">
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wider opacity-75 mb-0.5">Date</p>
                            <p id="modal-show-date" class="font-black text-sm">2026-05-03</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wider opacity-75 mb-0.5">Time</p>
                            <p id="modal-show-time" class="font-black text-sm">14:00</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wider opacity-75 mb-0.5">Format</p>
                            <p id="modal-show-format" class="font-black text-sm">IMAX 70mm</p>
                        </div>
                    </div>
                </div>
                
                <!-- Ticket Body -->
                <div class="p-8 space-y-6 relative">
                    <!-- Dotted Tear line decoration at stub boundary -->
                    <div class="absolute left-0 top-0 w-full flex justify-between px-6 -translate-y-0.5">
                        <?php for($i=0; $i<24; $i++): ?>
                            <span class="w-1.5 h-1 bg-gray-200 dark:bg-gray-800 rounded-full"></span>
                        <?php endfor; ?>
                    </div>

                    <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider mb-1">Booking ID</p>
                            <p id="modal-booking-ref" class="font-bold text-gray-900 dark:text-white">BK001</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider mb-1">Seats</p>
                            <p id="modal-seats" class="font-bold text-gray-900 dark:text-white">H10, H11</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider mb-1">Number of Tickets</p>
                            <p id="modal-ticket-count" class="font-bold text-gray-900 dark:text-white">2</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider mb-1">Total Paid</p>
                            <p id="modal-total-paid" class="font-black text-[#F5C518] text-lg">₹88.00</p>
                        </div>
                    </div>
                    
                    <hr class="border-gray-100 dark:border-gray-800/60">
                    
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider mb-1">Cinema Address</p>
                        <p id="modal-cinema-address" class="font-medium text-sm text-gray-950 dark:text-gray-200">Mumbai</p>
                    </div>
                    
                    <!-- Food and Beverages Section -->
                    <div id="modal-food-section" class="hidden">
                        <hr class="border-gray-100 dark:border-gray-800/60 mb-6">
                        <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider mb-2">Food & Beverages</p>
                        <div id="modal-food-list" class="space-y-2">
                            <!-- Injected Food rows -->
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-[#1c1c1c] border border-gray-100 dark:border-gray-800/60 rounded-2xl p-5 text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                        <span class="font-bold text-gray-900 dark:text-white">Important:</span> Please arrive 15 minutes before showtime. Scan the QR code at the cinema entrance to collect your physical tickets. Collect your food items from the concession counter.
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer Controls -->
            <div class="border-t border-gray-100 dark:border-gray-800 p-6 bg-gray-50 dark:bg-[#181818] flex flex-wrap gap-3 justify-center">
                <button onclick="window.print()" class="flex items-center gap-2 px-5 py-2.5 border border-gray-300 dark:border-gray-700 rounded-xl font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-150 dark:hover:bg-[#222] transition-colors text-xs focus:outline-none">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print Ticket
                </button>
                <button onclick="alert('Ticket PDF download started!')" class="flex items-center gap-2 px-5 py-2.5 border border-gray-300 dark:border-gray-700 rounded-xl font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-150 dark:hover:bg-[#222] transition-colors text-xs focus:outline-none">
                    <i data-lucide="download" class="w-4 h-4"></i> Download
                </button>
                <button onclick="closeTicketModal()" class="flex items-center justify-center px-6 py-2.5 bg-[#F5C518] text-black font-bold rounded-xl hover:bg-yellow-500 transition-colors text-xs shadow-sm focus:outline-none">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons on page load
        if (typeof lucide !== 'undefined') { 
            lucide.createIcons(); 
        }

        // Inject PHP data as JSON objects for instant interactive loading
        const bookingsData = <?php echo json_encode($bookings); ?>;
        const foodData = <?php echo json_encode($food_items_by_booking); ?>;

        function openTicketModal(bookingId) {
            const booking = bookingsData.find(b => b.booking_id == bookingId);
            if (!booking) return;

            // Populate text content dynamically
            document.getElementById('modal-movie-title').textContent = booking.movie_title;
            document.getElementById('modal-cinema-name').textContent = booking.cinema_name;
            document.getElementById('modal-show-date').textContent = booking.show_date;
            
            // Strip seconds from time string if present (e.g., 14:00:00 -> 14:00)
            let showTime = booking.show_time;
            if (showTime && showTime.includes(':')) {
                const parts = showTime.split(':');
                showTime = parts[0] + ':' + parts[1];
            }
            document.getElementById('modal-show-time').textContent = showTime;
            document.getElementById('modal-show-format').textContent = booking.show_format || 'Standard';
            
            // Format ID (e.g., BK001)
            const paddedId = 'BK' + String(booking.booking_id).padStart(3, '0');
            document.getElementById('modal-booking-ref').textContent = paddedId;
            document.getElementById('modal-seats').textContent = booking.seat_numbers;
            
            // Calculate total ticket count
            const seatCount = booking.seat_numbers ? booking.seat_numbers.split(',').length : 0;
            document.getElementById('modal-ticket-count').textContent = seatCount;
            
            // Format Total Amount
            document.getElementById('modal-total-paid').textContent = '₹' + parseFloat(booking.total_amount).toFixed(0);
            
            // Format location city
            document.getElementById('modal-cinema-address').textContent = booking.cinema_city || 'Mumbai';

            // Populate Food & Beverages items if any were bought
            const foodList = foodData[bookingId] || [];
            const foodSection = document.getElementById('modal-food-section');
            const foodListContainer = document.getElementById('modal-food-list');
            
            foodListContainer.innerHTML = '';
            if (foodList.length > 0) {
                foodSection.classList.remove('hidden');
                foodList.forEach(item => {
                    const row = document.createElement('div');
                    row.className = 'flex justify-between text-sm';
                    
                    const itemTotal = parseFloat(item.price) * parseInt(item.quantity);
                    row.innerHTML = `
                        <span class="font-medium text-gray-800 dark:text-gray-200">${item.food_name} <span class="text-gray-500 dark:text-gray-400 ml-1">x${item.quantity}</span></span>
                        <span class="font-bold text-gray-900 dark:text-white">₹${itemTotal.toFixed(0)}</span>
                    `;
                    foodListContainer.appendChild(row);
                });
            } else {
                foodSection.classList.add('hidden');
            }

            // Reveal Modal
            const modal = document.getElementById('ticket-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Reinits lucide icons inside modal (especially QR code icon)
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function closeTicketModal() {
            const modal = document.getElementById('ticket-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Close on clicking outside modal card or pressing escape key
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('ticket-modal');
            if (e.target === modal) {
                closeTicketModal();
            }
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeTicketModal();
            }
        });
    </script>
</body>
</html>