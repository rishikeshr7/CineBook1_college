<?php
session_start();
require_once 'dbconnect.php';

// 1. Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Retrieve POST data (Redirect if accessed directly without data)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['showtime_id']) || empty($_POST['selected_seats'])) {
    header("Location: index.php");
    exit();
}

$showtime_id = intval($_POST['showtime_id']);
$selected_seats = trim($_POST['selected_seats']);
$grand_total = floatval($_POST['grand_total']);
$food_data_json = isset($_POST['food_data']) ? $_POST['food_data'] : '[]';
$food_items = json_decode($food_data_json, true);

$seats_array = array_filter(array_map('trim', explode(',', $selected_seats)));
$ticket_count = count($seats_array);
$currency = "₹"; 

$booking_id = 0;
$movie_name = "Unknown Movie";
$date = "N/A";
$time = "N/A";
$theater = "Unknown Theater";
$format = "Standard";
$address = "Cinema Address Not Available";

// 3. Database Insertions & Fetching
if (isset($conn)) {
    // A. Insert the Booking into `bookings` table
    $insert_booking = $conn->prepare("INSERT INTO bookings (user_id, showtime_id, seat_numbers, total_amount) VALUES (?, ?, ?, ?)");
    if ($insert_booking) {
        $insert_booking->bind_param("iisd", $user_id, $showtime_id, $selected_seats, $grand_total);
        $insert_booking->execute();
        $booking_id = $conn->insert_id;
        $insert_booking->close();
    }

    // B. Insert Food Items into `booking_food` table
    if ($booking_id > 0 && !empty($food_items)) {
        $insert_food = $conn->prepare("INSERT INTO booking_food (booking_id, food_id, food_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
        if ($insert_food) {
            foreach ($food_items as $f_id => $item) {
                $name = $item['name'];
                $qty = intval($item['qty']);
                $price = floatval($item['price']);
                $insert_food->bind_param("iisid", $booking_id, $f_id, $name, $qty, $price);
                $insert_food->execute();
            }
            $insert_food->close();
        }
    }

    // C. Fetch Showtime & Movie Details for the Ticket UI
    $query = "
        SELECT m.title AS movie_name, s.show_date, s.show_time, s.theater_id AS theater_name, s.format, s.city 
        FROM showtimes s
        JOIN movies m ON s.movie_id = m.id
        WHERE s.id = ?
    ";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $showtime_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $movie_name = $row['movie_name'];
            $date = date('Y-m-d', strtotime($row['show_date'])); 
            $time = date('H:i', strtotime($row['show_time']));   
            $theater = !empty($row['theater_name']) ? $row['theater_name'] : "INOX Megaplex";
            $format = !empty($row['format']) ? $row['format'] : "Dolby Cinema";
            $address = !empty($row['city']) ? $row['city'] : "Mumbai";
        }
        $stmt->close();
    }
}

// Format a realistic looking Booking ID (e.g., CB00000123)
$booking_ref = "CB" . str_pad($booking_id, 8, "0", STR_PAD_LEFT);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - CineBook</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#F5C518',
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { background-color: #fcfcfc; }
    </style>
</head>
<body class="text-gray-900 font-sans flex flex-col min-h-screen">

    <?php 
        if(file_exists('header.php')) {
            include("header.php"); 
        } else {
            echo '<header class="border-b border-gray-200 py-4 px-6 flex justify-between items-center bg-white">
                    <div class="flex items-center gap-4">
                        <div class="text-xl font-bold flex items-center gap-2"><span class="text-brand text-2xl">🎬</span> CineBook</div>
                        <div class="text-sm text-gray-500 flex items-center gap-1"><i data-lucide="map-pin" class="w-4 h-4"></i> Mumbai <i data-lucide="chevron-down" class="w-4 h-4"></i></div>
                    </div>
                  </header>';
        }
    ?>

    <div class="max-w-[700px] mx-auto w-full px-4 py-12 flex-1">
        
        <div class="text-center mb-10">
            <div class="w-16 h-16 bg-green-50 border-2 border-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <i data-lucide="check-circle-2" class="w-8 h-8 text-green-500"></i>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 mb-2">Booking Confirmed!</h1>
            <p class="text-gray-500 text-sm">Your tickets have been successfully booked. Check your email for confirmation.</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-3xl overflow-hidden shadow-[0_8px_30px_rgb(0,0,0,0.04)] mb-8">
            
            <div class="bg-brand p-8 text-gray-900">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-bold mb-1"><?php echo htmlspecialchars($movie_name); ?></h2>
                        <p class="text-sm opacity-90"><?php echo htmlspecialchars($theater); ?></p>
                    </div>
                    <div class="bg-yellow-400/30 p-2 rounded-xl">
                        <i data-lucide="qr-code" class="w-10 h-10 opacity-80"></i>
                    </div>
                </div>
                
                <div class="flex gap-10 mt-8">
                    <div>
                        <p class="text-xs font-medium opacity-80 mb-0.5">Date</p>
                        <p class="font-bold text-sm"><?php echo htmlspecialchars($date); ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium opacity-80 mb-0.5">Time</p>
                        <p class="font-bold text-sm"><?php echo htmlspecialchars($time); ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium opacity-80 mb-0.5">Format</p>
                        <p class="font-bold text-sm"><?php echo htmlspecialchars($format); ?></p>
                    </div>
                </div>
            </div>

            <div class="p-8 space-y-6">
                
                <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Booking ID</p>
                        <p class="font-bold text-gray-900"><?php echo $booking_ref; ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Seats</p>
                        <p class="font-bold text-gray-900"><?php echo htmlspecialchars($selected_seats); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Number of Tickets</p>
                        <p class="font-bold text-gray-900"><?php echo $ticket_count; ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Total Paid</p>
                        <p class="font-bold text-brand text-lg"><?php echo $currency . number_format($grand_total, 2); ?></p>
                    </div>
                </div>

                <hr class="border-gray-100">

                <div>
                    <p class="text-xs text-gray-500 mb-1">Cinema Address</p>
                    <p class="font-medium text-sm text-gray-900"><?php echo htmlspecialchars($address); ?></p>
                </div>

                <?php if (!empty($food_items)): ?>
                <hr class="border-gray-100">
                <div>
                    <p class="text-xs text-gray-500 mb-2">Food & Beverages</p>
                    <div class="space-y-2">
                        <?php foreach($food_items as $item): ?>
                        <div class="flex justify-between text-sm">
                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($item['name']); ?> <span class="text-gray-500 ml-1">x<?php echo $item['qty']; ?></span></span>
                            <span class="font-bold text-gray-900"><?php echo $currency . number_format($item['price'] * $item['qty'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 mt-4 text-xs text-gray-600 leading-relaxed">
                    <span class="font-bold text-gray-900">Important:</span> Please arrive 15 minutes before showtime. Scan the QR code at the cinema entrance to collect your tickets. Collect your food items from the concession counter.
                </div>

            </div>
        </div>

        <div class="flex flex-wrap justify-center gap-4 mb-10">
            <button class="flex items-center gap-2 px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition-colors text-sm">
                <i data-lucide="download" class="w-4 h-4"></i> Download Ticket
            </button>
            <button class="flex items-center gap-2 px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition-colors text-sm">
                <i data-lucide="mail" class="w-4 h-4"></i> Email Ticket
            </button>
            <button class="flex items-center gap-2 px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition-colors text-sm">
                <i data-lucide="share-2" class="w-4 h-4"></i> Share
            </button>
            <a href="index.php" class="flex items-center justify-center px-8 py-3 bg-brand text-gray-900 font-bold rounded-xl hover:bg-yellow-500 transition-colors text-sm shadow-sm">
                Book Another Movie
            </a>
        </div>

        <p class="text-center text-xs text-gray-500">
            Thank you for choosing CineBook! Enjoy your movie! 🍿
        </p>

    </div>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>