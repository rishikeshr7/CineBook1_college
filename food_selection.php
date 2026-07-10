<?php
session_start();
require_once 'dbconnect.php';

// Retrieve data passed from the Seat Selection page
$showtime_id = isset($_POST['showtime_id']) ? intval($_POST['showtime_id']) : 0;
$selected_seats = isset($_POST['selected_seats']) ? $_POST['selected_seats'] : '';
$ticket_amount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;

if ($showtime_id === 0 || empty($selected_seats)) {
    // If accessed directly without selecting seats, send back to home
    header("Location: index.php");
    exit();
}

// Mock Food Database (In a real app, you would fetch this from a 'food_items' table)
$food_items = [
    [
        'id' => 1, 'category' => 'Popcorn', 'name' => 'Classic Salted Popcorn (Small)', 
        'desc' => 'Freshly popped buttery salted popcorn', 'price' => 300, 'is_veg' => true, 
        'image' => 'assets/images/Classic Salted Popcorn (Small).png'
    ],
    [
        'id' => 2, 'category' => 'Popcorn', 'name' => 'Classic Salted Popcorn (Medium)', 
        'desc' => 'Freshly popped buttery salted popcorn', 'price' => 345, 'is_veg' => true, 
        'image' => 'assets/images/Classic Salted Popcorn (Medium).png'
    ],
    [
        'id' => 3, 'category' => 'Popcorn', 'name' => 'Classic Salted Popcorn (Large)', 
        'desc' => 'Freshly popped buttery salted popcorn', 'price' => 390, 'is_veg' => true, 
        'image' => 'assets/images/Classic Salted Popcorn (Large).png'
    ],
    [
        'id' => 4, 'category' => 'Popcorn', 'name' => 'Cheese Popcorn (Small)', 
        'desc' => 'Savory cheese flavored popcorn', 'price' => 300, 'is_veg' => true, 
        'image' => 'assets/images/Cheese Popcorn (Small).png'
    ],
    [
        'id' => 5, 'category' => 'Popcorn', 'name' => 'Cheese Popcorn (Medium)', 
        'desc' => 'Savory cheese flavored popcorn', 'price' => 345, 'is_veg' => true, 
        'image' => 'assets/images/Cheese Popcorn (Medium).png'
    ],
    [
        'id' => 6, 'category' => 'Popcorn', 'name' => 'Cheese Popcorn (Large)', 
        'desc' => 'Savory cheese flavored popcorn', 'price' => 390, 'is_veg' => true, 
        'image' => 'assets/images/Cheese Popcorn (Large).png'
    ],
    [
        'id' => 7, 'category' => 'Popcorn', 'name' => 'Caramel Popcorn (Small)', 
        'desc' => 'Sweet caramel flavored popcorn', 'price' => 300, 'is_veg' => true, 
        'image' => 'assets/images/Caramel Popcorn (Small).png'
    ],
    [
        'id' => 8, 'category' => 'Popcorn', 'name' => 'Caramel Popcorn (Medium)', 
        'desc' => 'Sweet caramel flavored popcorn', 'price' => 345, 'is_veg' => true, 
        'image' => 'assets/images/Caramel Popcorn (Medium).png'
    ],
    [
        'id' => 9, 'category' => 'Popcorn', 'name' => 'Caramel Popcorn (Large)', 
        'desc' => 'Sweet caramel flavored popcorn', 'price' => 390, 'is_veg' => true, 
        'image' => 'assets/images/Caramel Popcorn (Large).png'
    ],
    [
        'id' => 10, 'category' => 'Beverages', 'name' => 'Coca Cola (Small)', 
        'desc' => 'Chilled Coca Cola', 'price' => 100, 'is_veg' => true, 
        'image' => 'assets/images/Coca Cola (Small).png'
    ],
    [
        'id' => 11, 'category' => 'Beverages', 'name' => 'Coca Cola (Medium)', 
        'desc' => 'Chilled Coca Cola', 'price' => 150, 'is_veg' => true, 
        'image' => 'assets/images/Coca Cola (Medium).png'
    ],
    [
        'id' => 12, 'category' => 'Beverages', 'name' => 'Coca Cola (Large)', 
        'desc' => 'Chilled Coca Cola', 'price' => 200, 'is_veg' => true, 
        'image' => 'assets/images/Coca Cola (Large).png'
    ],
    [
        'id' => 13, 'category' => 'Beverages', 'name' => 'Pepsi (Small)', 
        'desc' => 'Chilled Pepsi', 'price' => 100, 'is_veg' => true, 
        'image' => 'assets/images/Pepsi (Small).png'
    ],
    [
        'id' => 14, 'category' => 'Beverages', 'name' => 'Pepsi (Medium)', 
        'desc' => 'Chilled Pepsi', 'price' => 150, 'is_veg' => true, 
        'image' => 'assets/images/Pepsi (Medium).png'
    ],
    [
        'id' => 15, 'category' => 'Beverages', 'name' => 'Pepsi (Large)', 
        'desc' => 'Chilled Pepsi', 'price' => 200, 'is_veg' => true, 
        'image' => 'assets/images/Pepsi (Large).png'
    ],
    [
        'id' => 16, 'category' => 'Beverages', 'name' => 'Mountain Dew (Small)', 
        'desc' => 'Refreshing Mountain Dew', 'price' => 100, 'is_veg' => true, 
        'image' => 'assets/images/Mountain Dew (Small).png'
    ],
    [
        'id' => 17, 'category' => 'Beverages', 'name' => 'Mountain Dew (Medium)', 
        'desc' => 'Refreshing Mountain Dew', 'price' => 150, 'is_veg' => true, 
        'image' => 'assets/images/Mountain Dew (Medium).png'
    ],
    [
        'id' => 18, 'category' => 'Beverages', 'name' => 'Mountain Dew (Large)', 
        'desc' => 'Refreshing Mountain Dew', 'price' => 200, 'is_veg' => true, 
        'image' => 'assets/images/Mountain Dew (Large).png'
    ],
    [
        'id' => 19, 'category' => 'Snacks', 'name' => 'Nachos with Cheese', 
        'desc' => 'Crispy nachos with cheese dip', 'price' => 300, 'is_veg' => true, 
        'image' => 'https://images.unsplash.com/photo-1513456852971-30c0b8199d4d?auto=format&fit=crop&w=400&q=80'
    ],
    [
        'id' => 20, 'category' => 'Snacks', 'name' => 'Hot Dog', 
        'desc' => 'Classic hot dog with ketchup & mustard', 'price' => 250, 'is_veg' => false, 
        'image' => 'assets/images/Hot Dog.png'
    ],
    [
        'id' => 21, 'category' => 'Snacks', 'name' => 'Chicken Nuggets', 
        'desc' => '6 pieces crispy chicken nuggets', 'price' => 280, 'is_veg' => false, 
        'image' => 'https://images.unsplash.com/photo-1562967914-608f82629710?auto=format&fit=crop&w=400&q=80'
    ],
    [
        'id' => 22, 'category' => 'Combos', 'name' => 'Premium Combo', 
        'desc' => '2 Large Popcorn + 2 Large Drinks + Nachos', 'price' => 950, 'is_veg' => true, 
        'image' => 'assets/images/Premium Combo.png'
    ],
    [
        'id' => 23, 'category' => 'Combos', 'name' => 'Classic Combo', 
        'desc' => '1 Large Popcorn + 2 Medium Drinks', 'price' => 650, 'is_veg' => true, 
        'image' => 'assets/images/Classic Combo.png'
    ]
];
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <link rel="icon" type="image/svg+xml" href="/CineBook/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grab a Bite - CineBook</title>
    
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
    </style>
</head>
<body class="bg-gray-50 dark:bg-bgMain text-gray-900 dark:text-gray-100 font-sans flex flex-col min-h-screen transition-colors duration-300">

    <?php include("header.php"); ?>

    <div class="max-w-[1400px] mx-auto w-full px-6 py-10 flex flex-col lg:flex-row gap-8 items-start flex-1">
        
        <div class="flex-1 w-full">
            
            <div class="mb-8">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white mb-2 transition-colors">Grab a Bite!</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Add food & beverages to enhance your movie experience</p>
            </div>

            <div class="flex overflow-x-auto hide-scrollbar gap-3 mb-8 pb-2">
                <button onclick="filterMenu('All', this)" class="filter-btn active px-5 py-2 rounded-full border border-brand text-brand font-semibold text-sm transition-colors whitespace-nowrap">
                    All
                </button>
                <button onclick="filterMenu('Combos', this)" class="filter-btn px-5 py-2 rounded-full border border-gray-300 dark:border-borderMain text-gray-600 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-500 font-semibold text-sm transition-colors whitespace-nowrap">
                    Combos
                </button>
                <button onclick="filterMenu('Popcorn', this)" class="filter-btn px-5 py-2 rounded-full border border-gray-300 dark:border-borderMain text-gray-600 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-500 font-semibold text-sm transition-colors whitespace-nowrap">
                    Popcorn
                </button>
                <button onclick="filterMenu('Beverages', this)" class="filter-btn px-5 py-2 rounded-full border border-gray-300 dark:border-borderMain text-gray-600 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-500 font-semibold text-sm transition-colors whitespace-nowrap">
                    Beverages
                </button>
                <button onclick="filterMenu('Snacks', this)" class="filter-btn px-5 py-2 rounded-full border border-gray-300 dark:border-borderMain text-gray-600 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-500 font-semibold text-sm transition-colors whitespace-nowrap">
                    Snacks
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6" id="food-grid">
                <?php foreach($food_items as $item): ?>
                    <div class="food-card bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col" data-category="<?php echo htmlspecialchars($item['category']); ?>">
                        
                        <div class="h-44 overflow-hidden relative bg-gray-100 dark:bg-gray-800">
                            <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-full object-cover transition-transform hover:scale-105 duration-500">
                        </div>
                        
                        <div class="p-5 flex flex-col flex-1">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <h3 class="font-bold text-gray-900 dark:text-white leading-tight">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </h3>
                                <?php if($item['is_veg']): ?>
                                    <div class="shrink-0 w-4 h-4 border border-green-600 flex items-center justify-center rounded-sm">
                                        <div class="w-2 h-2 bg-green-600 rounded-full"></div>
                                    </div>
                                <?php else: ?>
                                    <div class="shrink-0 w-4 h-4 border border-red-600 flex items-center justify-center rounded-sm">
                                        <div class="w-2 h-2 bg-red-600 rounded-full"></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 flex-1">
                                <?php echo htmlspecialchars($item['desc']); ?>
                            </p>
                            
                            <div class="flex items-center justify-between mt-auto">
                                <span class="font-bold text-brand text-lg">₹<?php echo number_format($item['price']); ?></span>
                                
                                <div id="btn-container-<?php echo $item['id']; ?>">
                                    <button onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)" 
                                            class="px-5 py-1.5 bg-brand/10 dark:bg-brand text-yellow-700 dark:text-black text-sm font-bold rounded-lg border border-brand hover:bg-brand hover:text-black transition-colors">
                                        Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="w-full lg:w-[350px] shrink-0">
            <div class="sticky top-24 bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-2xl shadow-sm">
                
                <!-- Ticket(s) Price -->
                <div class="px-6 py-5 flex justify-between items-center border-b border-gray-100 dark:border-borderMain">
                    <span class="text-[15px] font-medium text-gray-600 dark:text-gray-400">Ticket(s) price</span>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">₹<?php echo number_format($ticket_amount, 2); ?></span>
                </div>

                <!-- Your Cart Header -->
                <div class="px-6 py-5 border-b border-gray-100 dark:border-borderMain">
                    <h2 class="text-[17px] font-bold text-gray-900 dark:text-white">Your Cart</h2>
                </div>

                <div id="cart-items" class="space-y-4 max-h-[300px] overflow-y-auto hide-scrollbar">
                    <div id="empty-cart-msg" class="flex flex-col items-center justify-center py-10 px-6">
                        <svg class="w-20 h-20 text-gray-300 dark:text-gray-600 mb-4" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Bucket background -->
                            <path d="M25 40 L 35 90 L 65 90 L 75 40 Z" fill="#ffffff" stroke="currentColor" stroke-width="2"/>
                            <!-- Red stripes -->
                            <path d="M35 40 L 41 90 L 47 90 L 45 40 Z" fill="#EF4444" stroke="currentColor" stroke-width="2"/>
                            <path d="M55 40 L 53 90 L 59 90 L 65 40 Z" fill="#EF4444" stroke="currentColor" stroke-width="2"/>
                            <!-- Popcorn -->
                            <path d="M30 35 C 25 35, 25 25, 35 25 C 35 15, 45 15, 45 25 C 50 10, 60 15, 60 25 C 65 15, 75 15, 75 25 C 80 25, 80 35, 70 35 C 75 45, 25 45, 30 35 Z" fill="#F5C518" stroke="currentColor" stroke-width="2"/>
                            <!-- Cute Face -->
                            <path d="M42 65 C 45 70, 55 70, 58 65" stroke="#121212" stroke-width="2" fill="none" stroke-linecap="round"/>
                            <circle cx="40" cy="58" r="2.5" fill="#121212"/>
                            <circle cx="60" cy="58" r="2.5" fill="#121212"/>
                        </svg>
                        <p class="text-[15px] font-medium text-gray-600 dark:text-gray-400 text-center">Fill this cart with your favorite food combos!</p>
                    </div>
                </div>

                <div id="cart-summary" class="hidden px-6 py-5 border-t border-gray-100 dark:border-borderMain flex justify-between items-center">
                    <span class="text-[15px] font-medium text-gray-600 dark:text-gray-400">Current Total</span>
                    <span id="cart-grand-total" class="font-bold text-xl text-gray-900 dark:text-white">₹<?php echo number_format($ticket_amount, 2); ?></span>
                </div>
                
                <div class="px-6 pb-6 pt-2">
                    <form id="checkout-form" action="payment.php" method="POST" class="flex flex-col gap-3">
                        <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">
                        <input type="hidden" name="selected_seats" value="<?php echo htmlspecialchars($selected_seats); ?>">
                        <input type="hidden" name="ticket_amount" value="<?php echo $ticket_amount; ?>">
                        
<input type="hidden" name="food_data" id="food_data_input" value="[]">
                    <input type="hidden" name="food_amount" id="food_amount_input" value="0">
                    
                    <button type="submit" id="checkout-btn" disabled class="w-full py-3 bg-brand/50 text-black/50 dark:bg-[#333] dark:text-gray-500 text-sm font-bold rounded-xl transition-colors cursor-not-allowed">
                        Continue to Payment
                    </button>
                    
                    <button type="button" onclick="skipAndContinue()" class="w-full py-3 bg-transparent text-gray-600 dark:text-gray-300 text-sm font-bold hover:text-gray-900 dark:hover:text-white transition-colors">
                        Skip & Continue
                    </button>
                </form>

            </div>
        </div>

    </div>

    <script>
        const ticketAmount = <?php echo $ticket_amount; ?>;
        let cart = {};

        function addToCart(id, name, price) {
            cart[id] = { name: name, price: price, qty: 1 };
            updateItemButton(id);
            renderCart();
        }

        function updateQuantity(id, delta) {
            if (cart[id]) {
                cart[id].qty += delta;
                if (cart[id].qty <= 0) {
                    delete cart[id];
                }
            }
            updateItemButton(id);
            renderCart();
        }

        // Changes "Add" to "- 1 +"
        function updateItemButton(id) {
            const container = document.getElementById(`btn-container-${id}`);
            if (!container) return;

            if (cart[id] && cart[id].qty > 0) {
                container.innerHTML = `
                    <div class="flex items-center border border-brand rounded-lg bg-brand/10 dark:bg-transparent overflow-hidden h-8">
                        <button type="button" onclick="updateQuantity(${id}, -1)" class="w-8 flex items-center justify-center text-yellow-700 dark:text-brand hover:bg-brand hover:text-black transition-colors">-</button>
                        <span class="w-6 text-center text-sm font-bold text-gray-900 dark:text-white">${cart[id].qty}</span>
                        <button type="button" onclick="updateQuantity(${id}, 1)" class="w-8 flex items-center justify-center text-yellow-700 dark:text-brand hover:bg-brand hover:text-black transition-colors">+</button>
                    </div>
                `;
            } else {
                // Restore original Add button
                const card = container.closest('.food-card');
                const name = card.querySelector('h3').innerText.trim().replace(/'/g, "\\'");
                const priceText = card.querySelector('.text-brand').innerText.trim().replace('₹', '').replace(/,/g, '');
                const price = parseFloat(priceText);
                
                container.innerHTML = `
                    <button type="button" onclick="addToCart(${id}, '${name}', ${price})" 
                            class="px-5 py-1.5 bg-brand/10 dark:bg-brand text-yellow-700 dark:text-black text-sm font-bold rounded-lg border border-brand hover:bg-brand hover:text-black transition-colors">
                        Add
                    </button>
                `;
            }
        }

        function renderCart() {
            const cartContainer = document.getElementById('cart-items');
            const summary = document.getElementById('cart-summary');
            const checkoutBtn = document.getElementById('checkout-btn');
            
            const foodDataInput = document.getElementById('food_data_input');
            const foodAmountInput = document.getElementById('food_amount_input');
            
            const fbTotalDisplay = document.getElementById('cart-fb-total');
            const grandTotalDisplay = document.getElementById('cart-grand-total');

            // Clear existing cart rows
            cartContainer.innerHTML = '';
            
            let foodTotal = 0;
            let itemsCount = 0;

            for (const [id, item] of Object.entries(cart)) {
                itemsCount++;
                const itemTotal = item.price * item.qty;
                foodTotal += itemTotal;

                cartContainer.innerHTML += `
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-tight mb-1">${item.name}</h4>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-500">₹${item.price.toLocaleString('en-IN')}</span>
                                <div class="flex items-center bg-gray-100 dark:bg-[#1a1a1a] rounded text-xs">
                                    <button type="button" onclick="updateQuantity(${id}, -1)" class="px-2 py-0.5 hover:text-brand transition-colors">-</button>
                                    <span class="font-bold w-3 text-center">${item.qty}</span>
                                    <button type="button" onclick="updateQuantity(${id}, 1)" class="px-2 py-0.5 hover:text-brand transition-colors">+</button>
                                </div>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">₹${itemTotal.toLocaleString('en-IN')}</span>
                    </div>
                `;
            }

            if (itemsCount === 0) {
                cartContainer.innerHTML = `
                    <div id="empty-cart-msg" class="flex flex-col items-center justify-center py-10 px-6">
                        <svg class="w-20 h-20 text-gray-300 dark:text-gray-600 mb-4" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Bucket background -->
                            <path d="M25 40 L 35 90 L 65 90 L 75 40 Z" fill="#ffffff" stroke="currentColor" stroke-width="2"/>
                            <!-- Red stripes -->
                            <path d="M35 40 L 41 90 L 47 90 L 45 40 Z" fill="#EF4444" stroke="currentColor" stroke-width="2"/>
                            <path d="M55 40 L 53 90 L 59 90 L 65 40 Z" fill="#EF4444" stroke="currentColor" stroke-width="2"/>
                            <!-- Popcorn -->
                            <path d="M30 35 C 25 35, 25 25, 35 25 C 35 15, 45 15, 45 25 C 50 10, 60 15, 60 25 C 65 15, 75 15, 75 25 C 80 25, 80 35, 70 35 C 75 45, 25 45, 30 35 Z" fill="#F5C518" stroke="currentColor" stroke-width="2"/>
                            <!-- Cute Face -->
                            <path d="M42 65 C 45 70, 55 70, 58 65" stroke="#121212" stroke-width="2" fill="none" stroke-linecap="round"/>
                            <circle cx="40" cy="58" r="2.5" fill="#121212"/>
                            <circle cx="60" cy="58" r="2.5" fill="#121212"/>
                        </svg>
                        <p class="text-[15px] font-medium text-gray-600 dark:text-gray-400 text-center">Fill this cart with your favorite food combos!</p>
                    </div>
                `;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                
                summary.classList.add('hidden');
                
                // Disable checkout
                checkoutBtn.disabled = true;
                checkoutBtn.classList.add('bg-brand/50', 'text-black/50', 'dark:bg-[#333]', 'dark:text-gray-500', 'cursor-not-allowed');
                checkoutBtn.classList.remove('bg-brand', 'text-black', 'hover:bg-yellow-500', 'cursor-pointer');
                
                foodDataInput.value = '[]';
                foodAmountInput.value = '0';
                
                // Extra security: force totals to visually reset just in case the UI unhides
                if (fbTotalDisplay) fbTotalDisplay.textContent = '₹0';
                if (grandTotalDisplay) grandTotalDisplay.textContent = '₹' + ticketAmount.toLocaleString('en-IN');
            } else {
                summary.classList.remove('hidden');
                
                const grandTotal = ticketAmount + foodTotal;
                
                if (fbTotalDisplay) fbTotalDisplay.textContent = '₹' + foodTotal.toLocaleString('en-IN');
                if (grandTotalDisplay) grandTotalDisplay.textContent = '₹' + grandTotal.toLocaleString('en-IN');
                
                // Enable checkout
                checkoutBtn.disabled = false;
                checkoutBtn.classList.remove('bg-brand/50', 'text-black/50', 'dark:bg-[#333]', 'dark:text-gray-500', 'cursor-not-allowed');
                checkoutBtn.classList.add('bg-brand', 'text-black', 'hover:bg-yellow-500', 'cursor-pointer');
                
                // Update hidden inputs for PHP
                foodDataInput.value = JSON.stringify(cart);
                foodAmountInput.value = foodTotal;
            }
        }

        function filterMenu(category, btn) {
            // Update active state of buttons
            const allBtns = document.querySelectorAll('.filter-btn');
            const activeClasses = ['active', 'border-brand', 'text-brand'];
            const inactiveClasses = ['border-gray-300', 'dark:border-borderMain', 'text-gray-600', 'dark:text-gray-300', 'hover:border-gray-400', 'dark:hover:border-gray-500'];
            
            allBtns.forEach(b => {
                b.classList.remove(...activeClasses);
                b.classList.add(...inactiveClasses);
            });
            
            btn.classList.remove(...inactiveClasses);
            btn.classList.add(...activeClasses);

            // Show/Hide food cards
            const allCards = document.querySelectorAll('.food-card');
            allCards.forEach(card => {
                if (category === 'All' || card.getAttribute('data-category') === category) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // --- Handle Skip & Continue Safely ---
        function skipAndContinue() {
            // Forcefully clear the hidden inputs before submitting
            document.getElementById('food_data_input').value = '[]';
            document.getElementById('food_amount_input').value = '0';
            document.getElementById('checkout-form').submit();
        }

        // --- Prevent Back-Button Ghost Data ---
        document.addEventListener('DOMContentLoaded', () => {
            // Sync the form inputs with the empty JS cart on page load
            renderCart();
        });

    </script>
</body>
</html>



