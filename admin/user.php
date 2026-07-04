<?php
session_start();

// Include database connection
require_once 'dbconnect.php';
// include "session.php"; // Uncomment if using session checks

// Fetch users from the database
$sql = "SELECT id, fullname, email, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
$total_users = $result ? $result->num_rows : 0;

// Fetch total bookings and total revenue from the bookings table
$stats_result = $conn->query("SELECT COUNT(*) as total_bookings, COALESCE(SUM(total_amount), 0) as total_revenue FROM bookings");
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total_bookings' => 0, 'total_revenue' => 0];
$total_bookings = $stats['total_bookings'];
$total_revenue = $stats['total_revenue'];
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
    <title>CineBook Admin - User Management</title>
    
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

    <?php
    // Includes your dynamic sidebar.php
    include "sidebar.php";
    ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <div class="flex-1 overflow-y-auto p-8">
            
            <header class="mb-8">
                <h1 class="text-3xl font-bold mb-1 text-gray-900 dark:text-white">User Management</h1>
                <p class="text-gray-500 dark:text-textMuted text-sm">Manage registered users and their booking history</p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-textMuted mb-2">Total Users</p>
                    <h3 class="text-3xl font-bold tracking-tight text-brand mb-1"><?php echo $total_users; ?></h3>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium"><?php echo $total_users; ?> active</p>
                </div>
                
                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-textMuted mb-2">Total Bookings</p>
                    <h3 class="text-3xl font-bold tracking-tight text-brand mb-1"><?php echo number_format($total_bookings); ?></h3>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Across all users</p>
                </div>

                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-textMuted mb-2">Total Revenue</p>
                    <h3 class="text-3xl font-bold tracking-tight text-brand mb-1">₹<?php echo number_format($total_revenue, 2); ?></h3>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">From ticket sales</p>
                </div>
            </div>

            <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl flex flex-col shadow-sm">
                
                <div class="p-5 border-b border-gray-200 dark:border-borderMain flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="relative w-full sm:w-[400px]">
                        <i data-lucide="search" class="w-4 h-4 text-gray-400 dark:text-textMuted absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" id="search-input" placeholder="Search users by name or email..." class="w-full bg-gray-50 dark:bg-bgMain border border-gray-300 dark:border-borderMain text-sm rounded-lg pl-9 pr-4 py-2.5 focus:outline-none focus:border-brand transition-colors text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-textMuted">
                    </div>
                    
                    <a href="export_users.php" class="flex-shrink-0 flex items-center gap-2 bg-white dark:bg-inputBg border border-gray-200 dark:border-borderMain text-gray-700 dark:text-gray-200 px-4 py-2.5 rounded-lg font-semibold text-sm hover:bg-gray-50 dark:hover:bg-borderMain transition-colors shadow-sm">
    <i data-lucide="download" class="w-4 h-4"></i> Export Data
</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-borderMain bg-gray-50 dark:bg-bgMain/50 text-xs uppercase text-gray-500 dark:text-textMuted tracking-wider">
                                <th class="px-6 py-4 font-semibold">Name</th>
                                <th class="px-6 py-4 font-semibold">Email</th>
                                <th class="px-6 py-4 font-semibold">Registered</th>
                                <th class="px-6 py-4 font-semibold">Bookings</th>
                                <th class="px-6 py-4 font-semibold">Total Spent</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-borderMain text-sm">
                            
                            <?php if ($total_users > 0): ?>
                                <?php while($user = $result->fetch_assoc()): ?>
                                        <?php 
                                            // 1. Get first letter of fullname for avatar
                                            $initial = strtoupper(substr(trim($user['fullname']), 0, 1));
                                            
                                            // 2. Format the created_at timestamp to just show YYYY-MM-DD
                                            $registered_date = date('Y-m-d', strtotime($user['created_at']));
                                            
                                            // 3. Get REAL bookings and spent from database
                                            $user_stats_stmt = $conn->prepare("SELECT COUNT(*) as bookings, COALESCE(SUM(total_amount), 0) as spent FROM bookings WHERE user_id = ?");
                                            $user_stats_stmt->bind_param("i", $user['id']);
                                            $user_stats_stmt->execute();
                                            $user_stats = $user_stats_stmt->get_result()->fetch_assoc();
                                            $user_bookings = $user_stats['bookings'];
                                            $user_spent = '₹' . number_format($user_stats['spent'], 2);
                                            $user_stats_stmt->close();
                                        ?>
                                    <tr class="user-row hover:bg-gray-50 dark:hover:bg-bgMain/50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-brand/10 text-yellow-600 dark:text-brand flex items-center justify-center font-bold text-xs border border-brand/20">
                                                    <?php echo $initial; ?>
                                                </div>
                                                <span class="font-semibold text-gray-900 dark:text-gray-100">
                                                    <?php echo htmlspecialchars($user['fullname']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2 text-gray-500 dark:text-textMuted">
                                                <i data-lucide="mail" class="w-4 h-4"></i> 
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2 text-gray-500 dark:text-textMuted">
                                                <i data-lucide="calendar" class="w-4 h-4"></i> 
                                                <?php echo $registered_date; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                            <?php echo $user_bookings; ?>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-brand">
                                            <?php echo $user_spent; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 rounded-full text-xs font-medium">Active</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="view_user.php?id=<?php echo $user['id']; ?>" class="font-semibold text-gray-900 dark:text-gray-200 hover:text-brand dark:hover:text-brand transition-colors text-sm">View Details</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-textMuted">
                                        No registered users found in the database.
                                    </td>
                                </tr>
                            <?php endif; ?>

                        </tbody>
                    </table>
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

        // Search/Filter Functionality
        const searchInput = document.getElementById('search-input');
        if(searchInput) {
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('.user-row');

                rows.forEach(row => {
                    // Check if row text contains search query
                    const text = row.innerText.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>
</html>