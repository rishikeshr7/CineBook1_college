<?php
session_start();

// Include database connection
require_once 'dbconnect.php';
// include "session.php";

// Fetch movies from the database
$sql = "SELECT * FROM movies ORDER BY created_at DESC";
$result = $conn->query($sql);
$active_movies_count = $result->num_rows;

// Calculate Total Revenue (Total Amount - Refunded Amount)
$revenue_query = "SELECT SUM(total_amount - COALESCE(refund_amount, 0)) AS net_revenue FROM bookings";
$revenue_result = $conn->query($revenue_query);
$net_revenue = 0;
if ($revenue_result && $row = $revenue_result->fetch_assoc()) {
    $net_revenue = (float)$row['net_revenue'];
}

// Calculate Tickets Sold (excluding cancelled)
$tickets_query = "SELECT seat_numbers FROM bookings WHERE status != 'Cancelled' OR status IS NULL";
$tickets_result = $conn->query($tickets_query);
$tickets_sold = 0;
if ($tickets_result) {
    while ($row = $tickets_result->fetch_assoc()) {
        if (!empty(trim($row['seat_numbers']))) {
            $seats = explode(',', $row['seat_numbers']);
            $tickets_sold += count(array_filter($seats));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/svg+xml" href="/CineBook/favicon.svg">
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBook Admin - Dashboard</title>
    
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

        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-bgMain text-gray-900 dark:text-gray-100 font-sans flex h-screen overflow-hidden">

    <?php
    include "sidebar.php";
    ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <div class="flex-1 overflow-y-auto p-8">
            
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">Movie added successfully!</span>
                </div>
            <?php elseif (isset($_GET['deleted'])): ?>
                <div class="mb-6 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">Movie deleted successfully!</span>
                </div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">Error processing your request. Please try again.</span>
                </div>
            <?php endif; ?>

            <header class="mb-8">
                <h1 class="text-3xl font-bold mb-1 text-gray-900 dark:text-white">Dashboard</h1>
                <p class="text-gray-500 dark:text-textMuted text-sm">Overview of your cinema management system</p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-5 flex justify-between items-start shadow-sm">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-textMuted mb-2">Total Revenue</p>
                        <h3 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">₹ <?php echo number_format($net_revenue, 0); ?></h3>
                    </div>
                    <div class="flex flex-col items-end">
                        <i data-lucide="indian-rupee" class="w-5 h-5 text-emerald-500 mb-3"></i>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-5 flex justify-between items-start shadow-sm">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-textMuted mb-2">Tickets Sold</p>
                        <h3 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white"><?php echo number_format($tickets_sold, 0); ?></h3>
                    </div>
                    <div class="flex flex-col items-end">
                        <i data-lucide="ticket" class="w-5 h-5 text-blue-500 mb-3"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-5 flex justify-between items-start shadow-sm">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-textMuted mb-2">Active Movies</p>
                        <h3 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white"><?php echo $active_movies_count; ?></h3>
                    </div>
                    <div class="flex flex-col items-end">
                        <i data-lucide="film" class="w-5 h-5 text-yellow-500 dark:text-brand mb-3"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl flex flex-col shadow-sm">
                <div class="p-5 border-b border-gray-200 dark:border-borderMain flex flex-col sm:flex-row justify-between items-center gap-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Current Movies</h2>
                    
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="relative w-full sm:w-64">
                            <i data-lucide="search" class="w-4 h-4 text-gray-400 dark:text-textMuted absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="text" id="search-input" placeholder="Search movies..." class="w-full bg-gray-50 dark:bg-bgMain border border-gray-300 dark:border-borderMain text-sm rounded-lg pl-9 pr-4 py-2 focus:outline-none focus:border-brand transition-colors text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-textMuted">
                        </div>
                        
                        <a href="add_movie.php" class="flex-shrink-0 flex items-center gap-2 bg-brand text-black px-4 py-2 rounded-lg font-semibold text-sm hover:bg-yellow-500 transition-colors">
                            <i data-lucide="plus" class="w-4 h-4"></i> Add New Movie
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-borderMain bg-gray-50 dark:bg-bgMain/50 text-xs uppercase text-gray-500 dark:text-textMuted tracking-wider">
                                <th class="px-6 py-4 font-semibold w-16">Poster</th>
                                <th class="px-6 py-4 font-semibold">Title</th>
                                <th class="px-6 py-4 font-semibold">Genre</th>
                                <th class="px-6 py-4 font-semibold">Duration</th>
                                <th class="px-6 py-4 font-semibold">Rating</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="movie-table-body" class="divide-y divide-gray-200 dark:divide-borderMain text-sm">
                            
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($movie = $result->fetch_assoc()): ?>
                                    
                                    <?php 
                                        $rating10 = (float)$movie['rating'];
                                        $stars5 = round($rating10 / 2);
                                        $statusColor = ($movie['status'] === 'Now Showing') ? 'text-emerald-600 dark:text-emerald-500' : 'text-blue-600 dark:text-blue-500';
                                        
                                        $poster_src = !empty($movie['poster_image']) ? htmlspecialchars($movie['poster_image']) : 'https://via.placeholder.com/100x150?text=No+Poster';
                                    ?>
                                    
                                    <tr class="hover:bg-gray-50 dark:hover:bg-bgMain/50 transition-colors group movie-row">
                                        <td class="px-6 py-4">
                                            <img src="<?php echo $poster_src; ?>" alt="Poster" class="w-10 h-14 object-cover rounded shadow-sm border border-gray-200 dark:border-borderMain">
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($movie['title']); ?></div>
                                            <div class="text-xs text-gray-500 dark:text-textMuted mt-1"><?php echo htmlspecialchars($movie['language']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 dark:text-textMuted"><?php echo htmlspecialchars($movie['genre']); ?></td>
                                        <td class="px-6 py-4 text-gray-600 dark:text-textMuted"><?php echo htmlspecialchars($movie['duration']); ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex gap-1">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $stars5): ?>
                                                        <svg class="w-4 h-4 text-yellow-500 dark:text-brand fill-current" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                                    <?php else: ?>
                                                        <svg class="w-4 h-4 text-gray-300 dark:text-borderMain fill-current" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-medium <?php echo $statusColor; ?>"><?php echo htmlspecialchars($movie['status']); ?></td>
                                        <td class="px-6 py-4">
    <div class="flex items-center gap-3">
        <a href="edit_movie.php?id=<?php echo $movie['id']; ?>" class="text-gray-400 hover:text-blue-500 dark:text-textMuted dark:hover:text-blue-500 transition-colors" title="Edit">
            <i data-lucide="edit" class="w-4 h-4"></i>
        </a>
        
        <button onclick="confirmDelete(<?php echo $movie['id']; ?>)" class="text-gray-400 hover:text-red-500 dark:text-textMuted dark:hover:text-red-500 transition-colors" title="Delete">
            <i data-lucide="trash-2" class="w-4 h-4"></i>
        </button>
    </div>
</td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
                
                <div id="empty-state" class="<?php echo ($result->num_rows > 0) ? 'hidden' : ''; ?> py-12 text-center text-gray-500 dark:text-textMuted">
                    <i data-lucide="search-x" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                    <p>No movies found.</p>
                </div>
            </div>
        </div>
    
        <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Find all alert banners on the page
        const alerts = document.querySelectorAll('[role="alert"]');
        
        if (alerts.length > 0) {
            // Wait for 3 seconds (3000 milliseconds)
            setTimeout(() => {
                alerts.forEach(alert => {
                    // Add smooth fade-out transition
                    alert.style.transition = 'opacity 0.5s ease-out';
                    alert.style.opacity = '0';
                    
                    // Wait for the fade out to finish, then remove the element completely
                    setTimeout(() => {
                        alert.remove();
                    }, 500); 
                });

                // Clean up the URL to remove the query parameters (optional but recommended)
                if (window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('success');
                    url.searchParams.delete('deleted');
                    url.searchParams.delete('error');
                    window.history.replaceState(null, '', url.toString());
                }
            }, 3000); // Change 3000 to 5000 if you want it to stay for 5 seconds
        }
    });
</script>

    </main>

    

    <script>
        lucide.createIcons();

        // Update file name in the custom upload button
        function updateFileName(input) {
            const displaySpan = document.getElementById('file-name-display');
            if (input.files && input.files.length > 0) {
                displaySpan.textContent = input.files[0].name;
            } else {
                displaySpan.textContent = 'Upload Poster Image';
            }
        }

        const searchInput = document.getElementById('search-input');
        const emptyState = document.getElementById('empty-state');
        const themeToggle = document.getElementById('toggle-theme');
        // Search/Filter Functionality using the DOM
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.movie-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCount === 0) {
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
            }
        });

        // Theme Toggle
        themeToggle.addEventListener('click', () => {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        });

        // Delete Confirmation
        window.confirmDelete = function(id) {
            if (confirm('Are you sure you want to delete this movie? This will also remove all associated showtimes.')) {
                window.location.href = `delete_movie.php?id=${id}`;
            }
        }





</script>


</body>
</html>

