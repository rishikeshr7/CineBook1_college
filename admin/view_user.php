<?php
session_start();
require_once 'dbconnect.php';
// include "session.php"; // Uncomment if using session checks

// Check if an ID was provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user.php?error=invalid_id");
    exit();
}

$user_id = $_GET['id'];

// Fetch user details from the database
$sql = "SELECT id, fullname, email, phone, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        // User not found
        header("Location: user.php?error=not_found");
        exit();
    }
    $stmt->close();
} else {
    header("Location: user.php?error=sql_error");
    exit();
}

// 1. Get first letter of fullname for avatar
$initial = strtoupper(substr(trim($user['fullname']), 0, 1));

// 2. Format dates
$registered_date = date('F j, Y, g:i a', strtotime($user['created_at']));
$member_since = date('M Y', strtotime($user['created_at']));

// 3. MOCK DATA for user stats (Replace these later with real DB queries)
$mock_total_bookings = rand(5, 42);
$mock_total_spent = "$" . ($mock_total_bookings * rand(12, 18));
$mock_last_active = date('M j, Y', strtotime('-' . rand(1, 14) . ' days'));
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBook Admin - View User</title>
    
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
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }
        
        body, aside, div, header, input, button, table, tr, td, th {
            transition: background-color 0.2s, border-color 0.2s, color 0.2s;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-bgMain text-gray-900 dark:text-gray-100 font-sans flex h-screen overflow-hidden">

    <?php include "sidebar.php"; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <div class="flex-1 overflow-y-auto p-8">
            
            <div class="mb-6 flex items-center gap-4">
                <a href="user.php" class="flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors shadow-sm">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">User Profile</h1>
                    <p class="text-gray-500 dark:text-textMuted text-sm">Detailed view and history</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-1 space-y-6">
                    
                    <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-6 shadow-sm flex flex-col items-center text-center">
                        <div class="w-24 h-24 rounded-full bg-brand/10 text-yellow-600 dark:text-brand flex items-center justify-center font-bold text-3xl border border-brand/20 mb-4">
                            <?php echo $initial; ?>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                            <?php echo htmlspecialchars($user['fullname']); ?>
                        </h2>
                        <p class="text-gray-500 dark:text-textMuted text-sm mb-4">
                            Member since <?php echo $member_since; ?>
                        </p>
                        <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 rounded-full text-xs font-bold uppercase tracking-wider mb-6">
                            Active Account
                        </span>

                        <div class="w-full space-y-3 text-left border-t border-gray-200 dark:border-borderMain pt-5 mt-2">
                            <div class="flex items-center gap-3 text-sm">
                                <i data-lucide="mail" class="w-4 h-4 text-gray-400 dark:text-textMuted"></i>
                                <span class="font-medium text-gray-900 dark:text-gray-200"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <i data-lucide="phone" class="w-4 h-4 text-gray-400 dark:text-textMuted"></i>
                                <span class="font-medium text-gray-900 dark:text-gray-200">
                                    <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'No phone provided'; ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <i data-lucide="clock" class="w-4 h-4 text-gray-400 dark:text-textMuted"></i>
                                <span class="font-medium text-gray-900 dark:text-gray-200">ID: #<?php echo str_pad($user['id'], 5, "0", STR_PAD_LEFT); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-5 shadow-sm">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-textMuted mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <button class="w-full flex items-center justify-center gap-2 bg-gray-100 dark:bg-inputBg hover:bg-gray-200 dark:hover:bg-borderMain text-gray-900 dark:text-white py-2.5 rounded-lg text-sm font-semibold transition-colors">
                                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit Details
                            </button>
                            <button class="w-full flex items-center justify-center gap-2 bg-gray-100 dark:bg-inputBg hover:bg-gray-200 dark:hover:bg-borderMain text-gray-900 dark:text-white py-2.5 rounded-lg text-sm font-semibold transition-colors">
                                <i data-lucide="lock" class="w-4 h-4"></i> Send Password Reset
                            </button>
                            <div class="pt-2 border-t border-gray-200 dark:border-borderMain mt-2">
                                <button class="w-full flex items-center justify-center gap-2 bg-red-50 dark:bg-red-500/10 hover:bg-red-100 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 py-2.5 rounded-lg text-sm font-semibold transition-colors border border-red-200 dark:border-red-500/20">
                                    <i data-lucide="ban" class="w-4 h-4"></i> Suspend User
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-5 shadow-sm flex flex-col justify-center">
                            <p class="text-sm font-medium text-gray-500 dark:text-textMuted mb-1 flex items-center gap-2">
                                <i data-lucide="ticket" class="w-4 h-4"></i> Total Bookings
                            </p>
                            <h3 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"><?php echo $mock_total_bookings; ?></h3>
                        </div>
                        <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-5 shadow-sm flex flex-col justify-center">
                            <p class="text-sm font-medium text-gray-500 dark:text-textMuted mb-1 flex items-center gap-2">
                                <i data-lucide="dollar-sign" class="w-4 h-4"></i> Total Spent
                            </p>
                            <h3 class="text-2xl font-bold tracking-tight text-brand"><?php echo $mock_total_spent; ?></h3>
                        </div>
                        <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-5 shadow-sm flex flex-col justify-center">
                            <p class="text-sm font-medium text-gray-500 dark:text-textMuted mb-1 flex items-center gap-2">
                                <i data-lucide="activity" class="w-4 h-4"></i> Last Active
                            </p>
                            <h3 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white mt-1"><?php echo $mock_last_active; ?></h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl flex flex-col shadow-sm">
                        <div class="p-5 border-b border-gray-200 dark:border-borderMain flex justify-between items-center">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Recent Bookings</h2>
                            <button class="text-sm font-semibold text-brand hover:text-yellow-400 transition-colors">View All</button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse whitespace-nowrap">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-bgMain/50 text-xs uppercase text-gray-500 dark:text-textMuted tracking-wider border-b border-gray-200 dark:border-borderMain">
                                        <th class="px-6 py-4 font-semibold">Movie</th>
                                        <th class="px-6 py-4 font-semibold">Date & Time</th>
                                        <th class="px-6 py-4 font-semibold">Seats</th>
                                        <th class="px-6 py-4 font-semibold">Amount</th>
                                        <th class="px-6 py-4 font-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-borderMain text-sm">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-bgMain/50 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100">Dune: Part Two</td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-textMuted">Tomorrow, 7:30 PM</td>
                                        <td class="px-6 py-4 text-gray-900 dark:text-gray-200 font-medium">F12, F13</td>
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">$32.00</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 rounded text-xs font-semibold">Upcoming</span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-bgMain/50 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100">Oppenheimer</td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-textMuted">Oct 12, 2023, 6:00 PM</td>
                                        <td class="px-6 py-4 text-gray-900 dark:text-gray-200 font-medium">B04, B05, B06</td>
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">$45.00</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-gray-100 dark:bg-borderMain text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-inputBorder rounded text-xs font-semibold">Completed</span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-bgMain/50 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100">The Batman</td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-textMuted">Aug 05, 2023, 9:15 PM</td>
                                        <td class="px-6 py-4 text-gray-900 dark:text-gray-200 font-medium">H08, H09</td>
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">$28.00</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-gray-100 dark:bg-borderMain text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-inputBorder rounded text-xs font-semibold">Completed</span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-bgMain/50 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100">Spider-Man: No Way Home</td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-textMuted">Dec 20, 2021, 4:00 PM</td>
                                        <td class="px-6 py-4 text-gray-900 dark:text-gray-200 font-medium">J15</td>
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">$14.00</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/20 rounded text-xs font-semibold">Cancelled</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Theme Toggle
        const themeToggle = document.getElementById('toggle-theme');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
            });
        }
    </script>
</body>
</html>