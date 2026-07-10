<?php
session_start();
require_once 'dbconnect.php';

// Check if a showtime ID was passed
if (!isset($_GET['showtime_id']) || empty($_GET['showtime_id'])) {
    header("Location: index.php");
    exit();
}

$showtime_id = intval($_GET['showtime_id']);
$max_seats = isset($_GET['num_seats']) ? intval($_GET['num_seats']) : 2;

// 1. Fetch Showtime and Movie Details
$sql = "SELECT s.*, m.title as movie_title, m.poster_image 
        FROM showtimes s 
        JOIN movies m ON s.movie_id = m.id 
        WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $showtime_id);
$stmt->execute();
$result = $stmt->get_result();
$showtime = $result->fetch_assoc();
$stmt->close();

if (!$showtime) {
    header("Location: index.php");
    exit();
}

// 2. Fetch Booked Seats Dynamically (With Safety Net)
$booked_seats = [];
try {
    $b_sql = "SELECT seat_numbers FROM bookings WHERE showtime_id = ? AND status = 'Confirmed'";
    $b_stmt = $conn->prepare($b_sql);
    if ($b_stmt) {
        $b_stmt->bind_param("i", $showtime_id);
        $b_stmt->execute();
        $b_res = $b_stmt->get_result();
        while($row = $b_res->fetch_assoc()) {
            $seats_array = explode(',', $row['seat_numbers']);
            foreach($seats_array as $s) {
                $booked_seats[] = trim($s);
            }
        }
        $b_stmt->close();
    }
} catch (mysqli_sql_exception $e) {
    // If the 'bookings' table doesn't exist yet, catch the error quietly.
}

// Formatting Details
$display_date = date('d-m-y', strtotime($showtime['show_date']));
$display_time = date('H:i', strtotime($showtime['show_time']));

// Pricing Tiers from Database
$price_regular = (float)$showtime['price_regular'];
$price_premium = (float)$showtime['price_premium'];
$price_vip = (float)$showtime['price_vip'];

// Define Row Structure (Tier => Array of Row Letters)
$seat_layout = [
    'VIP' => ['A', 'B'],             // Price: VIP (Upper most, farthest from screen)
    'Premium' => ['C', 'D', 'E', 'F'], // Price: Premium (Middle)
    'Regular' => ['G', 'H', 'I', 'J', 'K', 'L'] // Price: Regular (Lower most, closest to screen)
];

// Define Aisles (Empty spaces after these columns)
$aisles = [4, 12];
$total_columns = 16;
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <link rel="icon" type="image/svg+xml" href="/CineBook/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Seats - <?php echo htmlspecialchars($showtime['movie_title']); ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bgMain: '#0a0a0a',
                        bgCard: '#121212',
                        brand: '#F5C518',
                        textMuted: '#a3a3a3',
                        borderMain: '#262626'
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        /* Enforce theme icon visibility globally */
        html:not(.dark) #theme-icon-moon { display: block !important; }
        html:not(.dark) #theme-icon-sun { display: none !important; }
        html.dark #theme-icon-moon { display: none !important; }
        html.dark #theme-icon-sun { display: block !important; }

        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Seat Base Styling (Arched shape) */
        .seat-btn {
            border-width: 1.5px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            border-bottom-left-radius: 3px;
            border-bottom-right-radius: 3px;
            background-color: transparent;
            transition: all 0.2s ease-in-out;
        }

        /* Icon Colors - Ensure symbols remain black when selected */
        .seat-icon { color: #000000; }
        html.dark .seat-icon { color: #a3a3a3; }
        .seat-btn.selected .seat-icon { color: #000000 !important; }

        /* VIP Tier (Purple borders, Lighter Purple fill on hover) */
        .seat-vip.available { border-color: #a855f7; }
        .seat-vip.available:not(.selected):hover { 
            background-color: rgba(168, 85, 247, 0.2); 
            border-color: #a855f7; 
        }
        .seat-vip.selected { 
            background-color: #a855f7; 
            border-color: #a855f7; 
        }

        /* Premium Tier (Blue borders, Lighter Blue fill on hover) */
        .seat-premium.available { border-color: #3b82f6; }
        .seat-premium.available:not(.selected):hover { 
            background-color: rgba(59, 130, 246, 0.2); 
            border-color: #3b82f6; 
        }
        .seat-premium.selected { 
            background-color: #3b82f6; 
            border-color: #3b82f6; 
        }

        /* Regular Tier (Yellow borders, Lighter Yellow fill on hover) */
        .seat-regular.available { border-color: #eab308; }
        .seat-regular.available:not(.selected):hover { 
            background-color: rgba(234, 179, 8, 0.2); 
            border-color: #eab308; 
        }
        .seat-regular.selected { 
            background-color: #eab308; 
            border-color: #eab308; 
        }

        /* Booked/Disabled State */
        .seat-btn.booked {
            background-color: #f3f4f6; 
            border-color: #e5e7eb;
            color: #d1d5db;
            cursor: not-allowed;
        }
        html.dark .seat-btn.booked {
            background-color: #1a1a1a;
            border-color: #262626;
            color: #404040;
        }
    </style>
</head>
<body class="bg-white dark:bg-bgMain text-gray-900 dark:text-gray-100 font-sans flex flex-col min-h-screen transition-colors duration-300 pb-28">

    <?php include("header.php"); ?>

    <div class="bg-gray-50 dark:bg-[#111111] border-b border-gray-200 dark:border-borderMain py-3 px-6 transition-colors">
        <div class="max-w-[1400px] mx-auto text-sm text-gray-500 dark:text-gray-400 font-medium flex flex-wrap gap-2 items-center">
            <span class="text-gray-900 dark:text-white font-bold"><?php echo htmlspecialchars($showtime['movie_title']); ?></span>
            <span>•</span>
            <span><?php echo htmlspecialchars($showtime['format']); ?></span>
            <span>•</span>
            <span><?php echo htmlspecialchars($showtime['language']); ?></span>
            <span>•</span>
            <span><?php echo $display_date; ?> at <?php echo $display_time; ?></span>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-8 w-full flex-1 flex flex-col items-center">

        <div class="overflow-x-auto w-full hide-scrollbar flex justify-center pb-2">
            <div class="inline-flex flex-col gap-2.5 items-center">
                
                <div class="flex items-center gap-1.5 mb-2 ml-6">
                    <?php for($col = 1; $col <= $total_columns; $col++): ?>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-[10px] font-bold text-gray-400">
                            <?php echo $col; ?>
                        </div>
                        <?php if (in_array($col, $aisles)) echo '<div class="w-4 sm:w-6"></div>'; // Aisle gap ?>
                    <?php endfor; ?>
                </div>

                <?php foreach($seat_layout as $tier => $rows): ?>
                    <div class="w-full flex flex-col gap-2.5 mb-2 relative">
                        <?php foreach($rows as $row_label): ?>
                            <div class="flex items-center gap-1.5">
                                <div class="w-6 flex items-center justify-center text-xs font-bold text-gray-400 shrink-0">
                                    <?php echo $row_label; ?>
                                </div>
                                
                                <?php for($col = 1; $col <= $total_columns; $col++): 
                                    $seat_id = $row_label . $col;
                                    
                                    // Determine Price, Icon, and Tier Class
                                    $seat_price = 0;
                                    $icon_name = "";
                                    $tier_class = "";

                                    if ($tier === 'VIP') {
                                        $seat_price = $price_vip;
                                        $icon_name = "crown";
                                        $tier_class = "seat-vip";
                                    } elseif ($tier === 'Premium') {
                                        $seat_price = $price_premium;
                                        $icon_name = "star";
                                        $tier_class = "seat-premium";
                                    } else {
                                        $seat_price = $price_regular;
                                        $icon_name = "circle";
                                        $tier_class = "seat-regular";
                                    }

                                    // Check if this specific seat is in the $booked_seats array
                                    $is_booked = in_array($seat_id, $booked_seats); 
                                    
                                    $btn_class = $is_booked ? "booked" : "available " . $tier_class;
                                ?>
                                    <button 
                                        type="button" 
                                        id="seat-<?php echo $seat_id; ?>"
                                        class="seat-btn w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center shrink-0 <?php echo $btn_class; ?>"
                                        <?php if(!$is_booked) echo "onclick=\"toggleSeat(this, '$seat_id', $seat_price)\""; ?>
                                        title="<?php echo $seat_id . ' - ₹' . $seat_price; ?>"
                                        <?php if($is_booked) echo 'disabled'; ?>
                                    >
                                        <?php if($is_booked): ?>
                                            <i data-lucide="x" class="w-3 h-3 opacity-50"></i>
                                        <?php else: ?>
                                            <i data-lucide="<?php echo $icon_name; ?>" class="w-3 h-3 seat-icon transition-colors"></i>
                                        <?php endif; ?>
                                    </button>

                                    <?php if (in_array($col, $aisles)) echo '<div class="w-4 sm:w-6 shrink-0"></div>'; // Aisle gap ?>
                                <?php endfor; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                
            </div>
        </div>

        <div class="w-full max-w-2xl mt-8 mb-6 flex flex-col items-center">
            <p class="text-gray-400 dark:text-gray-500 tracking-[0.2em] text-[11px] font-bold uppercase mb-4">All eyes this way please!</p>
            <div class="w-full h-1.5 rounded-b-[100%] bg-gradient-to-t from-brand/50 to-transparent shadow-[0_15px_30px_rgba(245,197,24,0.15)] dark:shadow-[0_15px_30px_rgba(245,197,24,0.1)]"></div>
        </div>

        <div class="flex items-center justify-center gap-6 mt-4">
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1">
                    <div class="w-5 h-5 flex items-center justify-center rounded border border-[#a855f7] bg-white dark:bg-transparent">
                        <i data-lucide="crown" class="w-3 h-3 seat-icon"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center rounded border border-[#3b82f6] bg-white dark:bg-transparent">
                        <i data-lucide="star" class="w-3 h-3 seat-icon"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center rounded border border-[#eab308] bg-white dark:bg-transparent">
                        <i data-lucide="circle" class="w-3 h-3 seat-icon"></i>
                    </div>
                </div>
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">Available</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1">
                    <div class="w-5 h-5 flex items-center justify-center rounded bg-[#a855f7] border border-[#a855f7]">
                        <i data-lucide="crown" class="w-3 h-3 text-black"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center rounded bg-[#3b82f6] border border-[#3b82f6]">
                        <i data-lucide="star" class="w-3 h-3 text-black"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center rounded bg-[#eab308] border border-[#eab308]">
                        <i data-lucide="circle" class="w-3 h-3 text-black"></i>
                    </div>
                </div>
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">Selected</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-5 h-5 rounded bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#262626] flex items-center justify-center">
                    <i data-lucide="x" class="w-3 h-3 text-gray-300 dark:text-gray-600"></i>
                </div>
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">Booked</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-6 sm:gap-10 mt-6 pt-6 border-t border-gray-200 dark:border-borderMain w-full max-w-2xl">
            <div class="flex items-center gap-2.5">
                <div class="seat-btn w-6 h-6 flex items-center justify-center border-[#eab308] bg-transparent">
                    <i data-lucide="circle" class="w-3 h-3 seat-icon"></i>
                </div>
                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Regular - ₹<?php echo number_format($price_regular, 0); ?></span>
            </div>
            <div class="flex items-center gap-2.5">
                <div class="seat-btn w-6 h-6 flex items-center justify-center border-[#3b82f6] bg-transparent">
                    <i data-lucide="star" class="w-3 h-3 seat-icon"></i>
                </div>
                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Premium - ₹<?php echo number_format($price_premium, 0); ?></span>
            </div>
            <div class="flex items-center gap-2.5">
                <div class="seat-btn w-6 h-6 flex items-center justify-center border-[#a855f7] bg-transparent">
                    <i data-lucide="crown" class="w-3 h-3 seat-icon"></i>
                </div>
                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">VIP - ₹<?php echo number_format($price_vip, 0); ?></span>
            </div>
        </div>

    </div>

    <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-[#121212] border-t border-gray-200 dark:border-[#262626] shadow-[0_-4px_20px_rgba(0,0,0,0.05)] dark:shadow-[0_-4px_20px_rgba(0,0,0,0.5)] z-50 transition-colors">
        <div class="max-w-[1400px] mx-auto px-6 py-4 flex flex-row items-center justify-between">
            
            <div class="flex flex-col">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-0.5">Selected Seats</span>
                <span id="selected-seats-display" class="text-sm md:text-base font-bold text-gray-900 dark:text-white">
                    None
                </span>
                <span id="total-price-display" class="text-xs font-semibold text-brand hidden mt-0.5">
                    Total: ₹<span id="price-amount">0</span>
                </span>
            </div>
            
            <div class="flex items-center gap-2 md:gap-4">
                <button type="button" onclick="history.back()" class="text-sm font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors px-2 md:px-4 py-2">
                    Back
                </button>
                
                <form id="booking-form" action="food_selection.php" method="POST" class="m-0 p-0">
                    <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">
                    <input type="hidden" name="selected_seats" id="hidden-selected-seats" value="">
                    <input type="hidden" name="total_amount" id="hidden-total-amount" value="0">
                    
                    <button type="submit" id="continue-btn" disabled class="px-6 md:px-10 py-3 bg-brand/50 text-black/50 dark:bg-[#333] dark:text-gray-500 text-sm font-bold rounded-lg transition-colors shadow-sm cursor-not-allowed">
                        Continue
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-24 right-6 z-[100] flex flex-col gap-3 pointer-events-none"></div>

    <script>
        // Render Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        const MAX_SEATS = <?php echo $max_seats; ?>;
        let selectedSeats = [];
        let totalPrice = 0;

        function showToast(message) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            // Design matching the UI
            toast.className = 'bg-white dark:bg-[#181818] border border-gray-200 dark:border-borderMain text-gray-900 dark:text-white px-5 py-4 rounded-xl shadow-xl flex items-center gap-3 transform transition-all duration-300 translate-x-[120%] opacity-0 pointer-events-auto';
            
            toast.innerHTML = `
                <div class="bg-red-50 dark:bg-red-900/20 text-red-500 p-1.5 rounded-lg border border-red-100 dark:border-red-900/30">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                </div>
                <p class="text-sm font-bold pr-2">${message}</p>
            `;
            
            container.appendChild(toast);
            if (typeof lucide !== 'undefined') lucide.createIcons({root: toast});
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-[120%]', 'opacity-0');
            }, 10);
            
            // Animate out and remove after 3s
            setTimeout(() => {
                toast.classList.add('translate-x-[120%]', 'opacity-0');
                setTimeout(() => {
                    if (container.contains(toast)) {
                        container.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        }

        function toggleSeat(btn, seatId, price) {
            const index = selectedSeats.indexOf(seatId);

            if (index > -1) {
                // Deselect seat
                selectedSeats.splice(index, 1);
                totalPrice -= price;
                btn.classList.remove('selected');
            } else {
                // Select seat 
                if (selectedSeats.length >= MAX_SEATS) {
                    showToast(`You can only select ${MAX_SEATS} seat(s) based on your previous selection.`);
                    return;
                }
                
                const row = seatId.charAt(0);
                const startCol = parseInt(seatId.slice(1));
                const seatsNeeded = MAX_SEATS - selectedSeats.length;

                // Check if contiguous selection is possible
                let canSelectAllContiguously = true;
                for (let i = 0; i < seatsNeeded; i++) {
                    const currentCol = startCol + i;
                    const currentSeatId = row + currentCol;
                    const currentBtn = document.getElementById('seat-' + currentSeatId);
                    
                    if (!currentBtn || currentBtn.disabled || currentBtn.classList.contains('booked') || selectedSeats.includes(currentSeatId)) {
                        canSelectAllContiguously = false;
                        break;
                    }
                }

                // If contiguous is not possible, only select the clicked seat
                const seatsToSelect = canSelectAllContiguously ? seatsNeeded : 1;

                for (let i = 0; i < seatsToSelect; i++) {
                    const currentCol = startCol + i;
                    const currentSeatId = row + currentCol;
                    const currentBtn = document.getElementById('seat-' + currentSeatId);
                    
                    if (!selectedSeats.includes(currentSeatId)) {
                        selectedSeats.push(currentSeatId);
                        totalPrice += price;
                        currentBtn.classList.add('selected');
                    }
                }
            }

            updateBottomBar();
        }

        function updateBottomBar() {
            const seatDisplay = document.getElementById('selected-seats-display');
            const priceDisplay = document.getElementById('total-price-display');
            const priceAmount = document.getElementById('price-amount');
            const continueBtn = document.getElementById('continue-btn');
            
            // Hidden Form Inputs
            const hiddenSeatsInput = document.getElementById('hidden-selected-seats');
            const hiddenTotalInput = document.getElementById('hidden-total-amount');

            if (selectedSeats.length > 0) {
                // Sort seats logically (e.g. A1, A2, B1)
                selectedSeats.sort((a, b) => {
                    const rowA = a.charAt(0);
                    const rowB = b.charAt(0);
                    if (rowA !== rowB) return rowA.localeCompare(rowB);
                    return parseInt(a.slice(1)) - parseInt(b.slice(1));
                });

                const seatString = selectedSeats.join(',');
                seatDisplay.textContent = selectedSeats.join(', ');
                priceAmount.textContent = totalPrice.toLocaleString('en-IN');
                priceDisplay.classList.remove('hidden');

                // Update hidden inputs for POST form
                hiddenSeatsInput.value = seatString;
                hiddenTotalInput.value = totalPrice;

                // Enable button
                continueBtn.disabled = false;
                continueBtn.classList.remove('bg-brand/50', 'text-black/50', 'dark:bg-[#333]', 'dark:text-gray-500', 'cursor-not-allowed');
                continueBtn.classList.add('bg-brand', 'text-black', 'hover:bg-yellow-500', 'cursor-pointer');
            } else {
                seatDisplay.textContent = 'None';
                priceDisplay.classList.add('hidden');

                // Clear hidden inputs
                hiddenSeatsInput.value = '';
                hiddenTotalInput.value = '0';

                // Disable button
                continueBtn.disabled = true;
                continueBtn.classList.add('bg-brand/50', 'text-black/50', 'dark:bg-[#333]', 'dark:text-gray-500', 'cursor-not-allowed');
                continueBtn.classList.remove('bg-brand', 'text-black', 'hover:bg-yellow-500', 'cursor-pointer');
            }
        }
    </script>
</body>
</html>

