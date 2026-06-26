<?php
session_start();

// Connect to the database
require_once 'dbconnect.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Retrieve data passed from the Food & Beverage page
$showtime_id = isset($_POST['showtime_id']) ? intval($_POST['showtime_id']) : 0;
$selected_seats = isset($_POST['selected_seats']) ? trim($_POST['selected_seats']) : ''; 
$ticket_amount = isset($_POST['ticket_amount']) ? (float)$_POST['ticket_amount'] : 0;

// Prevent direct access if no valid data is posted
if ($showtime_id === 0 || empty($selected_seats)) {
    header("Location: index.php");
    exit();
}

// Retrieve food data
$food_data_json = isset($_POST['food_data']) ? $_POST['food_data'] : '[]';
$food_amount = isset($_POST['food_amount']) ? (float)$_POST['food_amount'] : 0;
$food_items = json_decode($food_data_json, true);

// Initialize variables with fallbacks
$movie_name = "Unknown Movie";
$date = "N/A";
$time = "N/A";
$theater = "Unknown Theater"; 

// --- FETCH DYNAMIC SHOWTIME DETAILS ---
if (isset($conn)) {
    $query = "
        SELECT m.title AS movie_name, s.show_date, s.show_time, s.theater_id AS theater_name 
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
            $theater = !empty($row['theater_name']) ? $row['theater_name'] : "Unknown Theater";
        }
        $stmt->close();
    }
}

// Calculations
$seats_array = array_filter(array_map('trim', explode(',', $selected_seats)));
$ticket_count = count($seats_array);

$convenience_fee = 4;
$tax_rate = 0.08; // 8% tax
$tax_amount = round(($ticket_amount + $food_amount + $convenience_fee) * $tax_rate, 2);

$grand_total = $ticket_amount + $food_amount + $convenience_fee + $tax_amount;
$currency = "₹";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - CineBook</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bgMain: '#ffffff',
                        bgCard: '#f9fafb',
                        brand: '#F5C518',
                        textMuted: '#6b7280',
                        borderMain: '#e5e7eb'
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
        body { background-color: #ffffff; }
    </style>
</head>
<body class="text-gray-900 font-sans flex flex-col min-h-screen">

    <?php 
        if(file_exists('header.php')) {
            include("header.php"); 
        } else {
            echo '<header class="border-b border-gray-200 py-4 px-6 flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div class="text-xl font-bold flex items-center gap-2"><span class="text-brand text-2xl">🎬</span> CineBook</div>
                        <div class="text-sm text-gray-500 flex items-center gap-1"><i data-lucide="map-pin" class="w-4 h-4"></i> Mumbai <i data-lucide="chevron-down" class="w-4 h-4"></i></div>
                    </div>
                  </header>';
        }
    ?>

    <div class="max-w-[1100px] mx-auto w-full px-6 py-10 flex flex-col lg:flex-row gap-10 items-start flex-1">
        
        <div class="flex-1 w-full">
            <div class="mb-8">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 mb-2">Payment</h1>
                <p class="text-gray-500">Complete your booking</p>
            </div>

            <div class="border border-gray-200 rounded-2xl p-8">
                <h2 class="text-xl font-semibold mb-6">Select Payment Method</h2>
                
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <button id="tab-card" onclick="switchTab('card')" class="payment-tab active flex flex-col items-center justify-center gap-2 py-6 border rounded-xl transition-colors border-brand bg-[#fffcf0] text-gray-900">
                        <i data-lucide="credit-card" class="w-8 h-8"></i>
                        <span class="font-semibold">Credit/Debit Card</span>
                    </button>
                    <button id="tab-wallet" onclick="switchTab('wallet')" class="payment-tab flex flex-col items-center justify-center gap-2 py-6 border border-gray-200 rounded-xl transition-colors hover:bg-gray-50 text-gray-500">
                        <i data-lucide="smartphone" class="w-8 h-8"></i>
                        <span class="font-semibold">Digital Wallet</span>
                    </button>
                </div>

                <div id="form-card" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Card Number</label>
                        <input type="text" id="card-number" placeholder="1234 5678 9012 3456" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-700 outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-shadow">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Expiry Date</label>
                            <input type="text" id="card-expiry" placeholder="MM/YY" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-700 outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">CVV</label>
                            <input type="text" id="card-cvv" placeholder="123" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-700 outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-shadow">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Cardholder Name</label>
                        <input type="text" id="card-name" placeholder="JOHN DOE" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-700 outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-shadow">
                    </div>
                </div>

                <div id="form-wallet" class="space-y-4 hidden pt-2">
                    <button class="w-full bg-black text-white py-4 rounded-lg font-bold hover:bg-gray-900 transition-colors">
                        Pay with Apple Pay
                    </button>
                    <button class="w-full bg-white border border-gray-200 text-gray-900 py-4 rounded-lg font-bold hover:bg-gray-50 transition-colors shadow-sm">
                        Pay with Google Pay
                    </button>
                    <button class="w-full bg-[#0070ba] text-white py-4 rounded-lg font-bold hover:bg-[#005ea6] transition-colors">
                        Pay with PayPal
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2 text-sm text-gray-500 mt-6">
                <i data-lucide="lock" class="w-4 h-4"></i>
                <p>Your payment information is encrypted and secure</p>
            </div>
        </div>

        <div class="w-full lg:w-[380px] shrink-0">
            <div class="border border-gray-200 rounded-2xl p-6 shadow-sm">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Booking Summary</h2>
                
                <div class="space-y-4 mb-6 pb-6 border-b border-gray-200">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Movie</p>
                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($movie_name); ?></p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Date</p>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($date); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Time</p>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($time); ?></p>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Theater</p>
                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($theater); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Seats</p>
                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($selected_seats); ?></p>
                    </div>
                </div>

                <?php if(!empty($food_items)): ?>
                <div class="mb-6 pb-6 border-b border-gray-200">
                    <p class="text-sm font-bold text-gray-900 mb-3">Food & Beverages</p>
                    <div class="space-y-2">
                        <?php foreach($food_items as $item): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600"><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['qty']; ?></span>
                            <span class="font-semibold text-gray-900"><?php echo $currency . number_format($item['price'] * $item['qty'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="space-y-3 mb-6 pb-6 border-b border-gray-200 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tickets (<?php echo $ticket_count; ?>)</span>
                        <span class="font-semibold text-gray-900"><?php echo $currency . number_format($ticket_amount, 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Food & Beverages</span>
                        <span class="font-semibold text-gray-900"><?php echo $currency . number_format($food_amount, 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Convenience Fee</span>
                        <span class="font-semibold text-gray-900"><?php echo $currency . number_format($convenience_fee, 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax (8%)</span>
                        <span class="font-semibold text-gray-900"><?php echo $currency . number_format($tax_amount, 2); ?></span>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-8">
                    <span class="text-lg font-bold text-gray-900">Grand Total</span>
                    <span class="text-3xl font-bold text-brand"><?php echo $currency . number_format($grand_total, 2); ?></span>
                </div>

                <div id="auth-error" class="hidden mb-4 p-4 text-sm text-red-800 rounded-lg border border-red-200 bg-red-50" role="alert">
                    <span class="font-semibold">Action Required:</span> You must be signed in to confirm a booking. 
                </div>
                <div id="card-error" class="hidden mb-4 p-4 text-sm text-red-800 rounded-lg border border-red-200 bg-red-50" role="alert">
                    <span class="font-semibold">Action Required:</span> Please fill in all credit/debit card details to proceed. 
                </div>

                <form action="success.php" method="POST" onsubmit="return validateCheckout(event)">
                    <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">
                    <input type="hidden" name="selected_seats" value="<?php echo htmlspecialchars($selected_seats); ?>">
                    <input type="hidden" name="grand_total" value="<?php echo $grand_total; ?>">
                    <input type="hidden" name="food_data" value='<?php echo htmlspecialchars($food_data_json, ENT_QUOTES, 'UTF-8'); ?>'>
                    
                    <button type="submit" class="w-full py-4 bg-brand text-gray-900 font-bold rounded-lg hover:bg-yellow-500 transition-colors shadow-sm text-lg">
                        Confirm Booking
                    </button>
                </form>
                
                <p class="text-center text-xs text-gray-500 mt-4 px-4">
                    By confirming, you agree to our Terms & Conditions
                </p>

            </div>
        </div>

    </div>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Pass PHP login state to JavaScript
        const userIsLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        
        // Track the active payment tab
        let activePaymentTab = 'card';

        // Validation logic for the checkout form
        function validateCheckout(event) {
            // Hide previous errors
            document.getElementById('auth-error').classList.add('hidden');
            document.getElementById('card-error').classList.add('hidden');

            // 1. Check if user is logged in
            if (!userIsLoggedIn) {
                event.preventDefault(); // Stop the form from submitting
                document.getElementById('auth-error').classList.remove('hidden'); 
                return false;
            }

            // 2. If Card tab is selected, validate card fields
            if (activePaymentTab === 'card') {
                const cardNum = document.getElementById('card-number').value.trim();
                const cardExp = document.getElementById('card-expiry').value.trim();
                const cardCvv = document.getElementById('card-cvv').value.trim();
                const cardName = document.getElementById('card-name').value.trim();

                if (!cardNum || !cardExp || !cardCvv || !cardName) {
                    event.preventDefault(); // Stop form submission
                    document.getElementById('card-error').classList.remove('hidden'); // Show error
                    return false;
                }
            }

            return true; // Allow submission
        }

        // Tab Switching Logic
        function switchTab(tab) {
            activePaymentTab = tab; // Update state
            
            const cardTab = document.getElementById('tab-card');
            const walletTab = document.getElementById('tab-wallet');
            const cardForm = document.getElementById('form-card');
            const walletForm = document.getElementById('form-wallet');

            const activeClass = "payment-tab active flex flex-col items-center justify-center gap-2 py-6 border rounded-xl transition-colors border-brand bg-[#fffcf0] text-gray-900";
            const inactiveClass = "payment-tab flex flex-col items-center justify-center gap-2 py-6 border border-gray-200 rounded-xl transition-colors hover:bg-gray-50 text-gray-500";

            // Hide card error if switching away from the card tab
            if (tab !== 'card') {
                document.getElementById('card-error').classList.add('hidden');
            }

            if (tab === 'card') {
                cardTab.className = activeClass;
                walletTab.className = inactiveClass;
                cardForm.classList.remove('hidden');
                walletForm.classList.add('hidden');
            } else {
                walletTab.className = activeClass;
                cardTab.className = inactiveClass;
                cardForm.classList.add('hidden');
                walletForm.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>