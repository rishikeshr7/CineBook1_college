<?php
session_start();
require_once 'dbconnect.php';

// 1. Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Fetch User Profile Data
$user_stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// 3. Fetch Booking Stats (Total Count & Total Spent)
$stats_stmt = $conn->prepare("SELECT COUNT(*) as total_count, SUM(total_amount) as total_spent FROM bookings WHERE user_id = ?");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// 4. Fetch Detailed Bookings History
// Assuming a 'bookings' table exists with columns: id, movie_title, poster_image, status, format, booking_date, show_time, cinema_name, seats, total_amount
$bookings_sql = "SELECT * FROM bookings WHERE user_id = ? ORDER BY booking_date DESC";
$bookings_stmt = $conn->prepare($bookings_sql);
$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
$bookings = [];
while ($row = $bookings_result->fetch_assoc()) {
    $bookings[] = $row;
}
$bookings_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - CineBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { primary: '#F5C518' } } } }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #FAFAFA; }
        .dark body { background-color: #0F0F0F; }
    </style>
</head>
<body class="text-gray-900 dark:text-gray-100 min-h-screen flex flex-col transition-colors duration-300">

    <?php include("header.php"); ?>

    <main class="flex-1 max-w-[1400px] mx-auto w-full px-6 md:px-12 py-10">
        <div class="flex flex-col lg:flex-row gap-8 items-start">
            
            <aside class="w-full lg:w-[350px] shrink-0">
                <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-gray-800 rounded-2xl p-8 flex flex-col items-center shadow-sm">
                    <div class="w-28 h-28 bg-[#FFF9E5] dark:bg-[#332A00] rounded-full flex items-center justify-center mb-5">
                        <i data-lucide="user" class="w-12 h-12 text-[#F5C518]"></i>
                    </div>
                    
                    <h2 class="text-2xl font-extrabold tracking-tight"><?php echo htmlspecialchars($user_info['fullname']); ?></h2>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">CineBook Member</p>

                    <div class="w-full mt-8 space-y-4">
                        <div class="bg-gray-50 dark:bg-[#1A1A1A] p-4 rounded-xl flex items-center gap-4 border border-gray-100 dark:border-[#222]">
                            <i data-lucide="mail" class="w-5 h-5 text-gray-400 shrink-0"></i>
                            <div>
                                <p class="text-[11px] font-medium uppercase text-gray-500">Email</p>
                                <p class="text-sm font-semibold"><?php echo htmlspecialchars($user_info['email']); ?></p>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-[#1A1A1A] p-4 rounded-xl flex items-center gap-4 border border-gray-100 dark:border-[#222]">
                            <i data-lucide="phone" class="w-5 h-5 text-gray-400 shrink-0"></i>
                            <div>
                                <p class="text-[11px] font-medium uppercase text-gray-500">Phone</p>
                                <p class="text-sm font-semibold"><?php echo htmlspecialchars($user_info['phone']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="w-full mt-8 pt-8 border-t border-gray-100 dark:border-gray-800 flex justify-between">
                        <div class="text-center flex-1">
                            <p class="text-3xl font-bold text-[#F5C518]"><?php echo $stats['total_count'] ?? 0; ?></p>
                            <p class="text-xs text-gray-500">Total Bookings</p>
                        </div>
                        <div class="w-px bg-gray-100 dark:bg-gray-800"></div>
                        <div class="text-center flex-1">
                            <p class="text-3xl font-bold text-[#F5C518]">$<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></p>
                            <p class="text-xs text-gray-500">Total Spent</p>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="flex-1 w-full">
                <h1 class="text-[2rem] font-bold mb-8">My Bookings</h1>
                <div class="space-y-6">
                    <?php if (!empty($bookings)): ?>
                        <?php foreach($bookings as $booking): ?>
                            <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-gray-800 rounded-2xl p-5 flex flex-col sm:flex-row gap-6 shadow-sm">
                                <div class="w-full sm:w-[160px] h-[240px] shrink-0 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800">
                                    <img src="admin/<?php echo htmlspecialchars($booking['poster_image']); ?>" alt="Poster" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 flex flex-col">
                                    <h3 class="text-[22px] font-bold mb-2"><?php echo htmlspecialchars($booking['movie_title']); ?></h3>
                                    <div class="flex gap-2 mb-4">
                                        <span class="px-3 py-1 bg-green-50 text-green-600 text-xs font-semibold rounded-full border border-green-100"><?php echo htmlspecialchars($booking['status']); ?></span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-300">
                                        <p><i data-lucide="calendar" class="inline w-4 h-4 mr-2"></i> <?php echo htmlspecialchars($booking['booking_date']); ?></p>
                                        <p><i data-lucide="clock" class="inline w-4 h-4 mr-2"></i> <?php echo htmlspecialchars($booking['show_time']); ?></p>
                                        <p><i data-lucide="map-pin" class="inline w-4 h-4 mr-2"></i> <?php echo htmlspecialchars($booking['cinema_name']); ?></p>
                                        <p>Seats: <strong><?php echo htmlspecialchars($booking['seats']); ?></strong></p>
                                    </div>
                                    <div class="mt-auto pt-4 border-t border-gray-100 flex justify-between items-end">
                                        <p class="text-3xl font-bold text-[#F5C518]">$<?php echo htmlspecialchars($booking['total_amount']); ?></p>
                                        <button class="px-6 py-2.5 border-2 border-gray-900 dark:border-white rounded-lg font-bold hover:bg-black hover:text-white transition-colors">View Ticket</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center py-10 text-gray-500">No bookings found.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
    <script>lucide.createIcons();</script>
</body>
</html>