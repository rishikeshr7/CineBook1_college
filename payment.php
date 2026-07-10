<?php
session_start();

// Connect to the database
require_once 'dbconnect.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Retrieve data passed from the Food & Beverage page
// If we have POST data, store it in session so we don't lose it if we need to log in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['showtime_id'])) {
    $_SESSION['checkout_data'] = $_POST;
}

// Retrieve data from POST, or fallback to session if redirected back here after login
$source_data = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['showtime_id'])) ? $_POST : (isset($_SESSION['checkout_data']) ? $_SESSION['checkout_data'] : []);

$showtime_id = isset($source_data['showtime_id']) ? intval($source_data['showtime_id']) : 0;
$selected_seats = isset($source_data['selected_seats']) ? trim($source_data['selected_seats']) : ''; 
$ticket_amount = isset($source_data['ticket_amount']) ? (float)$source_data['ticket_amount'] : 0;

// Prevent direct access if no valid data is posted
if ($showtime_id === 0 || empty($selected_seats)) {
    header("Location: index.php");
    exit();
}

// Retrieve food data
$food_data_json = isset($source_data['food_data']) ? $source_data['food_data'] : '[]';
$food_amount = isset($source_data['food_amount']) ? (float)$source_data['food_amount'] : 0;
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
            $date = date('d-m-y', strtotime($row['show_date'])); 
            $time = date('H:i', strtotime($row['show_time']));   
            $theater = !empty($row['theater_name']) ? $row['theater_name'] : "INOX Megaplex";
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
<html lang="en" class="dark">
<head>
    <link rel="icon" type="image/svg+xml" href="/CineBook/favicon.svg">
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
    </style>
</head>
<body class="bg-gray-50 dark:bg-bgMain text-gray-900 dark:text-gray-100 font-sans flex flex-col min-h-screen transition-colors duration-300">

    <?php 
        if(file_exists('header.php')) {
            include("header.php"); 
        } else {
            echo '<header class="border-b border-gray-200 dark:border-borderMain bg-white dark:bg-bgCard py-4 px-6 flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div class="text-xl font-bold flex items-center gap-2"><span class="text-brand text-2xl">🎬</span> CineBook</div>
                        <div class="text-sm text-gray-500 dark:text-textMuted flex items-center gap-1"><i data-lucide="map-pin" class="w-4 h-4"></i> Mumbai <i data-lucide="chevron-down" class="w-4 h-4"></i></div>
                    </div>
                  </header>';
        }
    ?>

    <div class="max-w-[1100px] mx-auto w-full px-6 py-10 flex flex-col lg:flex-row gap-10 items-start flex-1">
        
        <div class="flex-1 w-full">
            <div class="mb-8">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white mb-2">Payment</h1>
                <p class="text-gray-500 dark:text-textMuted">Complete your booking</p>
            </div>

            <div class="border border-gray-200 dark:border-borderMain bg-white dark:bg-bgCard rounded-2xl p-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Select Payment Method</h2>
                
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <button id="tab-card" onclick="switchTab('card')" class="payment-tab active flex flex-col items-center justify-center gap-2 py-6 border rounded-xl transition-colors border-brand bg-[#fffcf0] dark:bg-brand/10 text-gray-900 dark:text-white">
                        <i data-lucide="credit-card" class="w-8 h-8"></i>
                        <span class="font-semibold">Credit/Debit Card</span>
                    </button>
                    <button id="tab-upi" onclick="switchTab('upi')" class="payment-tab flex flex-col items-center justify-center gap-2 py-6 border border-gray-200 dark:border-borderMain rounded-xl transition-colors hover:bg-gray-50 dark:hover:bg-borderMain text-gray-500 dark:text-textMuted">
                        <i data-lucide="qr-code" class="w-8 h-8"></i>
                        <span class="font-semibold">UPI Payment</span>
                    </button>
                </div>

                <div id="form-card" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-gray-300 mb-2">Card Number</label>
                        <input type="text" id="card-number" placeholder="1234 5678 9012 3456" class="w-full bg-gray-50 dark:bg-[#1a1a1a] border border-gray-200 dark:border-borderMain rounded-lg px-4 py-3 text-gray-900 dark:text-white outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-shadow">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 dark:text-gray-300 mb-2">Expiry Date</label>
                            <input type="text" id="card-expiry" placeholder="MM/YY" class="w-full bg-gray-50 dark:bg-[#1a1a1a] border border-gray-200 dark:border-borderMain rounded-lg px-4 py-3 text-gray-900 dark:text-white outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 dark:text-gray-300 mb-2">CVV</label>
                            <input type="text" id="card-cvv" placeholder="123" class="w-full bg-gray-50 dark:bg-[#1a1a1a] border border-gray-200 dark:border-borderMain rounded-lg px-4 py-3 text-gray-900 dark:text-white outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-shadow">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-gray-300 mb-2">Cardholder Name</label>
                        <input type="text" id="card-name" placeholder="JOHN DOE" class="w-full bg-gray-50 dark:bg-[#1a1a1a] border border-gray-200 dark:border-borderMain rounded-lg px-4 py-3 text-gray-900 dark:text-white outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-shadow">
                    </div>
                </div>

                <div id="form-upi" class="space-y-6 hidden pt-2">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-gray-300 mb-2">UPI ID</label>
                        <input type="text" id="upi-id" placeholder="username@bank" class="w-full bg-gray-50 dark:bg-[#1a1a1a] border border-gray-200 dark:border-borderMain rounded-lg px-4 py-3 text-gray-900 dark:text-white outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-shadow">
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-[1px] bg-gray-200 dark:bg-borderMain"></div>
                        <span class="text-sm font-medium text-gray-500 dark:text-textMuted">OR SCAN QR</span>
                        <div class="flex-1 h-[1px] bg-gray-200 dark:bg-borderMain"></div>
                    </div>
                    <div class="flex justify-center py-4">
                        <div class="w-40 h-40 bg-gray-100 dark:bg-[#1a1a1a] border-2 border-dashed border-gray-300 dark:border-borderMain rounded-xl flex flex-col items-center justify-center text-gray-400">
                            <i data-lucide="qr-code" class="w-12 h-12 mb-2"></i>
                            <span class="text-xs font-semibold">Scan with any UPI app</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-textMuted mt-6">
                <i data-lucide="lock" class="w-4 h-4"></i>
                <p>Your payment information is encrypted and secure</p>
            </div>
        </div>

        <div class="w-full lg:w-[380px] shrink-0">
            <div class="border border-gray-200 dark:border-borderMain bg-white dark:bg-bgCard rounded-2xl p-6 shadow-sm">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Booking Summary</h2>
                
                <div class="space-y-4 mb-6 pb-6 border-b border-gray-200 dark:border-borderMain">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-textMuted mb-1">Movie</p>
                        <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($movie_name); ?></p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-textMuted mb-1">Date</p>
                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($date); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-textMuted mb-1">Time</p>
                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($time); ?></p>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 dark:text-textMuted mb-1">Theater</p>
                        <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($theater); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 dark:text-textMuted mb-1">Seats</p>
                        <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($selected_seats); ?></p>
                    </div>
                </div>

                <?php if(!empty($food_items)): ?>
                <div class="mb-6 pb-6 border-b border-gray-200 dark:border-borderMain">
                    <p class="text-sm font-bold text-gray-900 dark:text-white mb-3">Food & Beverages</p>
                    <div class="space-y-2">
                        <?php foreach($food_items as $item): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['qty']; ?></span>
                            <span class="font-semibold text-gray-900 dark:text-white"><?php echo $currency . number_format($item['price'] * $item['qty'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="space-y-3 mb-6 pb-6 border-b border-gray-200 dark:border-borderMain text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Tickets (<?php echo $ticket_count; ?>)</span>
                        <span class="font-semibold text-gray-900 dark:text-white"><?php echo $currency . number_format($ticket_amount, 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Food & Beverages</span>
                        <span class="font-semibold text-gray-900 dark:text-white"><?php echo $currency . number_format($food_amount, 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Convenience Fee</span>
                        <span class="font-semibold text-gray-900 dark:text-white"><?php echo $currency . number_format($convenience_fee, 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Tax (8%)</span>
                        <span class="font-semibold text-gray-900 dark:text-white"><?php echo $currency . number_format($tax_amount, 2); ?></span>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-8">
                    <span class="text-lg font-bold text-gray-900 dark:text-white">Grand Total</span>
                    <span class="text-3xl font-bold text-brand"><?php echo $currency . number_format($grand_total, 2); ?></span>
                </div>

                <!-- Auth Error Modal (Hidden by default) -->
                <div id="auth-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
                    <div class="bg-white dark:bg-[#121212] border border-gray-200 dark:border-[#262626] rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 text-center">
                        <div class="w-16 h-16 bg-red-100 dark:bg-red-950/30 text-red-600 dark:text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-transparent dark:border-red-900/50">
                            <i data-lucide="user-x" class="w-8 h-8"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Authentication Required</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">You must be signed in to your account to confirm a booking and complete the payment.</p>
                        <div class="flex gap-3">
                            <button type="button" onclick="document.getElementById('auth-modal').classList.add('hidden')" class="flex-1 py-3 bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#262626] text-gray-700 dark:text-white font-bold rounded-lg hover:bg-gray-200 dark:hover:bg-[#262626] transition-colors">
                                Cancel
                            </button>
                            <button type="button" onclick="document.getElementById('auth-modal').classList.add('hidden'); document.getElementById('signin-modal').classList.remove('hidden'); document.getElementById('signin-modal').classList.add('flex');" class="flex-1 py-3 bg-brand text-gray-900 dark:text-black font-bold rounded-lg hover:bg-yellow-500 transition-colors block">
                                Sign In
                            </button>
                        </div>
                    </div>
                </div>
                <div id="card-error" class="hidden mb-4 p-4 text-sm text-red-800 dark:text-red-400 rounded-lg border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-950/20" role="alert">
                    <span class="font-semibold">Action Required:</span> <span id="card-error-msg">Please fill in all credit/debit card details to proceed.</span>
                </div>
                <div id="upi-error" class="hidden mb-4 p-4 text-sm text-red-800 dark:text-red-400 rounded-lg border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-950/20" role="alert">
                    <span class="font-semibold">Action Required:</span> Please enter your UPI ID to proceed. 
                </div>

                <form action="success.php" method="POST" onsubmit="return validateCheckout(event)">
                    <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">
                    <input type="hidden" name="selected_seats" value="<?php echo htmlspecialchars($selected_seats); ?>">
                    <input type="hidden" name="grand_total" value="<?php echo $grand_total; ?>">
                    <input type="hidden" name="food_data" value='<?php echo htmlspecialchars($food_data_json, ENT_QUOTES, 'UTF-8'); ?>'>
                    
                    <button type="submit" class="w-full py-4 bg-brand text-gray-900 dark:text-black font-bold rounded-lg hover:bg-yellow-500 transition-colors shadow-sm text-lg">
                        Confirm Booking
                    </button>
                </form>
                
                <p class="text-center text-xs text-gray-500 dark:text-textMuted mt-4 px-4">
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
            if (document.getElementById('card-error')) document.getElementById('card-error').classList.add('hidden');
            if (document.getElementById('upi-error')) document.getElementById('upi-error').classList.add('hidden');

            // 1. Check if user is logged in
            if (!userIsLoggedIn) {
                event.preventDefault(); // Stop the form from submitting
                document.getElementById('auth-modal').classList.remove('hidden'); 
                return false;
            }

            // 2. If Card tab is selected, validate card fields
            if (activePaymentTab === 'card') {
                const cardNum = document.getElementById('card-number').value.trim();
                const cardExp = document.getElementById('card-expiry').value.trim();
                const cardCvv = document.getElementById('card-cvv').value.trim();
                const cardName = document.getElementById('card-name').value.trim();

                const errorDiv = document.getElementById('card-error');
                const errorMsg = document.getElementById('card-error-msg');

                if (!cardNum || !cardExp || !cardCvv || !cardName) {
                    event.preventDefault(); // Stop form submission
                    errorMsg.innerText = "Please fill in all credit/debit card details to proceed.";
                    errorDiv.classList.remove('hidden'); // Show error
                    return false;
                }

                // Expiry date validation
                let isExpValid = false;
                if (cardExp.length === 5 && cardExp.includes('/')) {
                    const [expMonth, expYear] = cardExp.split('/');
                    const month = parseInt(expMonth, 10);
                    const year = parseInt(expYear, 10);
                    if (month >= 1 && month <= 12) {
                        const now = new Date();
                        const currentYear = now.getFullYear() % 100;
                        const currentMonth = now.getMonth() + 1;
                        
                        if (year > currentYear || (year === currentYear && month >= currentMonth)) {
                            isExpValid = true;
                        }
                    }
                }

                if (!isExpValid) {
                    event.preventDefault();
                    errorMsg.innerText = "Please enter a valid expiry date in the future (MM/YY).";
                    errorDiv.classList.remove('hidden');
                    return false;
                }
            } else if (activePaymentTab === 'upi') {
                const upiId = document.getElementById('upi-id').value.trim();
                if (!upiId) {
                    event.preventDefault(); // Stop form submission
                    document.getElementById('upi-error').classList.remove('hidden'); // Show error
                    return false;
                }
            }

            return true; // Allow submission
        }

        // Tab Switching Logic
        function switchTab(tab) {
            activePaymentTab = tab; // Update state
            
            const cardTab = document.getElementById('tab-card');
            const upiTab = document.getElementById('tab-upi');
            const cardForm = document.getElementById('form-card');
            const upiForm = document.getElementById('form-upi');

            const activeClass = "payment-tab active flex flex-col items-center justify-center gap-2 py-6 border rounded-xl transition-colors border-brand bg-[#fffcf0] dark:bg-brand/10 text-gray-900 dark:text-white";
            const inactiveClass = "payment-tab flex flex-col items-center justify-center gap-2 py-6 border border-gray-200 dark:border-borderMain rounded-xl transition-colors hover:bg-gray-50 dark:hover:bg-borderMain text-gray-500 dark:text-textMuted";

            // Hide errors when switching tabs
            if (document.getElementById('card-error')) document.getElementById('card-error').classList.add('hidden');
            if (document.getElementById('upi-error')) document.getElementById('upi-error').classList.add('hidden');

            if (tab === 'card') {
                cardTab.className = activeClass;
                upiTab.className = inactiveClass;
                cardForm.classList.remove('hidden');
                upiForm.classList.add('hidden');
            } else {
                upiTab.className = activeClass;
                cardTab.className = inactiveClass;
                cardForm.classList.add('hidden');
                upiForm.classList.remove('hidden');
            }
        }

        // Automatic slash formatting for expiry date
        const expiryInput = document.getElementById('card-expiry');
        if (expiryInput) {
            expiryInput.addEventListener('input', function (e) {
                let val = e.target.value.replace(/\D/g, ''); // Remove all non-digits
                if (val.length > 2) {
                    val = val.substring(0, 2) + '/' + val.substring(2, 4);
                }
                e.target.value = val;
            });
        }
    </script>
</body>
</html>

