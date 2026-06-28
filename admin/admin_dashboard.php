<?php
session_start();

// Include database connection
require_once 'dbconnect.php';
// include "session.php";

// Fetch movies from the database
$sql = "SELECT * FROM movies ORDER BY created_at DESC";
$result = $conn->query($sql);
$active_movies_count = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
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
                        <h3 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">₹ 124,580</h3>
                    </div>
                    <div class="flex flex-col items-end">
                        <i data-lucide="indian-rupee" class="w-5 h-5 text-emerald-500 mb-3"></i>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-5 flex justify-between items-start shadow-sm">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-textMuted mb-2">Tickets Sold</p>
                        <h3 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">8,432</h3>
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
                        
                        <button onclick="openModal()" class="flex-shrink-0 flex items-center gap-2 bg-brand text-black px-4 py-2 rounded-lg font-semibold text-sm hover:bg-yellow-500 transition-colors">
                            <i data-lucide="plus" class="w-4 h-4"></i> Add New Movie
                        </button>
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

    <div id="add-modal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex justify-center items-center p-4">
        <div class="bg-white dark:bg-bgCard w-full max-w-2xl rounded-xl shadow-2xl border border-gray-200 dark:border-borderMain flex flex-col max-h-[90vh]">
            
            <div class="p-6 flex justify-between items-center border-b border-gray-200 dark:border-borderMain shrink-0">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Add New Movie</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto custom-scrollbar">
<form id="add-movie-form" action="add_movie.php" method="POST" enctype="multipart/form-data" class="space-y-6">
    
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Title <span class="text-red-500">*</span></label>
            <input type="text" id="m-title" name="title" required placeholder="Movie title" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
        </div>
        <div>
            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Duration <span class="text-red-500">*</span></label>
            <input type="text" id="m-duration" name="duration" required placeholder="e.g., 180 min" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
        </div>
    </div>

    <div>
        <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Genre <span class="text-red-500">*</span></label>
        <input type="text" id="m-genre" name="genre" required placeholder="Action, Drama, Sci-Fi (comma separated)" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Language</label>
            <input type="text" id="m-lang" name="language" placeholder="English, Hindi (comma separated)" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
        </div>
        <div>
            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Certification</label>
            <select id="m-cert" name="certification" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                <option value="UA">UA</option>
                <option value="U">U</option>
                <option value="A">A</option>
                <option value="S">S</option>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Synopsis</label>
        <textarea id="m-synopsis" name="synopsis" rows="4" placeholder="Movie synopsis..." class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors resize-none"></textarea>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Director</label>
            <input type="text" id="m-director" name="director" placeholder="Director name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
        </div>
        <div>
            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Release Date</label>
            <input type="text" id="m-release" name="release_date" placeholder="e.g., July 21, 2023" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Rating(out of 10)</label>
            <input type="text" id="m-rating" name="rating" placeholder="e.g. 8.5" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
        </div>
        <div>
            <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Status</label>
            <select id="m-status" name="status" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                <option value="Now Showing">Now Showing</option>
                <option value="Coming Soon">Coming Soon</option>
            </select>
        </div>
    </div>

    <div class="border border-gray-200 dark:border-inputBorder bg-white dark:bg-inputBg rounded-lg p-4 flex justify-between items-center transition-colors">
        <div>
            <label for="m-rerelease" class="text-sm font-bold text-gray-900 dark:text-white cursor-pointer">Re-Release</label>
            <p class="text-xs text-gray-500 dark:text-textMuted mt-0.5">Mark this movie as a re-release of an older title</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer shrink-0">
            <input type="checkbox" id="m-rerelease" name="is_rerelease" value="1" class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-[#333333] peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand"></div>
        </label>
    </div>

    <div>
        <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Formats</label>
        <input type="text" id="m-formats" name="formats" placeholder="IMAX, Dolby Cinema, Standard (comma separated)" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
    </div>

    <div>
        <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Poster Image</label>
        <div class="relative">
            <input type="file" id="m-poster" name="poster_image" accept="image/*" class="hidden" onchange="updateFileName(this)">
            <label for="m-poster" class="w-full flex items-center justify-center gap-2 cursor-pointer bg-yellow-50 dark:bg-[#F5C518] hover:bg-yellow-100 dark:hover:bg-yellow-500 text-yellow-700 dark:text-black font-semibold py-3 px-4 border border-yellow-200 dark:border-transparent rounded-lg transition-colors text-sm">
                <i data-lucide="upload" class="w-4 h-4"></i>
                <span id="file-name-display">Upload Poster Image</span>
            </label>
        </div>
    </div>

    <div>
        <label class="block text-sm font-bold mb-2 text-gray-900 dark:text-white">Trailer URL</label>
        <input type="url" id="m-trailer" name="trailer_url" placeholder="https://www.youtube.com/embed/..." class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
    </div>

    <!-- Additional Multi-language Trailers -->
    <div class="border border-gray-200 dark:border-borderMain rounded-xl p-5 space-y-4">
        <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i data-lucide="video" class="w-4 h-4 text-brand"></i> Additional Trailers (Multi-Language)
        </h4>
        <div id="trailer-fields-container" class="space-y-3">
            <!-- Dynamic rows go here -->
        </div>
        <button type="button" onclick="addTrailerRow()" class="w-full mt-2 py-2.5 rounded-lg bg-transparent text-yellow-600 dark:text-brand border border-yellow-600 dark:border-brand text-xs font-bold hover:bg-yellow-50 dark:hover:bg-brand/10 transition-colors flex justify-center items-center gap-1.5">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Trailer Language
        </button>
    </div>

    <div class="border border-gray-200 dark:border-borderMain rounded-xl p-5 space-y-4">
        <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i data-lucide="user-plus" class="w-4 h-4 text-brand"></i> Cast Members
        </h4>
        
        <div class="border border-dashed border-gray-300 dark:border-borderMain rounded-lg p-5">
            <p class="text-xs font-bold text-gray-500 dark:text-textMuted mb-3 uppercase tracking-wider">Add Cast Member</p>
            
            <div id="cast-fields-container" class="space-y-4">
                <div class="member-row flex flex-col sm:flex-row gap-4 items-start sm:items-center border-b border-gray-100 dark:border-borderMain sm:border-none pb-4 sm:pb-0">
                    <div class="shrink-0">
                        <input type="file" id="cast-img-1" name="cast_images[]" class="hidden" accept="image/*" onchange="updateCastCrewImageName(this)">
                        <label for="cast-img-1" class="w-14 h-14 rounded-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder flex items-center justify-center cursor-pointer hover:bg-gray-100 dark:hover:bg-borderMain transition-colors shadow-sm" title="Upload Cast Profile Image">
                            <i data-lucide="upload" class="w-4 h-4 text-gray-400"></i>
                        </label>
                    </div>
                    <div class="flex-1 space-y-2 w-full">
                        <input type="text" name="cast_names[]" placeholder="Actor/Actress name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-2.5 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
                        <input type="text" name="cast_characters[]" placeholder="Character name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-2.5 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
                    </div>
                    <div class="w-9 hidden sm:block"></div>
                </div>
            </div>

            <button type="button" onclick="addCastMember()" class="w-full mt-4 py-2.5 rounded-lg bg-yellow-50 dark:bg-brand/10 text-yellow-600 dark:text-brand border border-yellow-200 dark:border-brand/20 text-sm font-bold hover:bg-yellow-100 dark:hover:bg-brand/20 transition-colors flex justify-center items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i> Add More Cast
            </button>
        </div>
    </div>

    <div class="border border-gray-200 dark:border-borderMain rounded-xl p-5 space-y-4">
        <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i data-lucide="users" class="w-4 h-4 text-brand"></i> Crew Members
        </h4>
        
        <div class="border border-dashed border-gray-300 dark:border-borderMain rounded-lg p-5">
            <p class="text-xs font-bold text-gray-500 dark:text-textMuted mb-3 uppercase tracking-wider">Add Crew Member</p>
            
            <div id="crew-fields-container" class="space-y-4">
                <div class="member-row flex flex-col sm:flex-row gap-4 items-start sm:items-center border-b border-gray-100 dark:border-borderMain sm:border-none pb-4 sm:pb-0">
                    <div class="shrink-0">
                        <input type="file" id="crew-img-1" name="crew_images[]" class="hidden" accept="image/*" onchange="updateCastCrewImageName(this)">
                        <label for="crew-img-1" class="w-14 h-14 rounded-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder flex items-center justify-center cursor-pointer hover:bg-gray-100 dark:hover:bg-borderMain transition-colors shadow-sm" title="Upload Crew Profile Image">
                            <i data-lucide="upload" class="w-4 h-4 text-gray-400"></i>
                        </label>
                    </div>
                    <div class="flex-1 space-y-2 w-full">
                        <input type="text" name="crew_names[]" placeholder="Crew member name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-2.5 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
                        <input type="text" name="crew_roles[]" placeholder="Role (e.g. Cinematographer, Editor)" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-2.5 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
                    </div>
                    <div class="w-9 hidden sm:block"></div>
                </div>
            </div>

            <button type="button" onclick="addCrewMember()" class="w-full mt-4 py-2.5 rounded-lg bg-yellow-50 dark:bg-brand/10 text-yellow-600 dark:text-brand border border-yellow-200 dark:border-brand/20 text-sm font-bold hover:bg-yellow-100 dark:hover:bg-brand/20 transition-colors flex justify-center items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i> Add More Crew
            </button>
        </div>
    </div>

    <div class="pt-6 pb-2 flex justify-between items-center border-t border-gray-200 dark:border-borderMain mt-4">
        <button type="button" onclick="closeModal()" class="text-gray-900 dark:text-white text-sm font-bold hover:text-gray-500 dark:hover:text-gray-300 transition-colors px-4 py-2">
            Cancel
        </button>
        <button type="submit" class="w-[200px] py-3 rounded-lg bg-[#F5C518] text-black text-sm font-bold hover:bg-yellow-500 transition-colors">
            Add Movie
        </button>
    </div>
</form>
            </div>
        </div>
    </div>

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
        const addModal = document.getElementById('add-modal');

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
            document.documentElement.classList.toggle('dark');
        });

        // Modal Controls
        window.openModal = function() {
            addModal.classList.remove('hidden');
        }

        window.closeModal = function() {
            addModal.classList.add('hidden');
        }

        // Delete Confirmation
        window.confirmDelete = function(id) {
            if (confirm('Are you sure you want to delete this movie? This will also remove all associated showtimes.')) {
                window.location.href = `delete_movie.php?id=${id}`;
            }
        }




    // Counters to ensure every dynamic file input gets a uniquely linked label ID
    let castCount = 1;
    let crewCount = 1;

    function addCastMember() {
        castCount++;
        const container = document.getElementById('cast-fields-container');
        
        const row = document.createElement('div');
        row.className = "member-row flex flex-col sm:flex-row gap-4 items-start sm:items-center border-b border-gray-100 dark:border-borderMain sm:border-none pb-4 sm:pb-0 animation-fade-in";
        
        row.innerHTML = `
            <div class="shrink-0">
                <input type="file" id="cast-img-${castCount}" name="cast_images[]" class="hidden" accept="image/*" onchange="updateCastCrewImageName(this)">
                <label for="cast-img-${castCount}" class="w-14 h-14 rounded-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder flex items-center justify-center cursor-pointer hover:bg-gray-100 dark:hover:bg-borderMain transition-colors shadow-sm">
                    <i data-lucide="upload" class="w-4 h-4 text-gray-400"></i>
                </label>
            </div>
            <div class="flex-1 space-y-2 w-full">
                <input type="text" name="cast_names[]" placeholder="Actor/Actress name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-2.5 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
                <input type="text" name="cast_characters[]" placeholder="Character name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-2.5 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
            </div>
            <div class="w-full sm:w-auto flex justify-end">
                <button type="button" onclick="this.closest('.member-row').remove()" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors" title="Remove Member">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
            </div>
        `;
        
        container.appendChild(row);
        if (typeof lucide !== 'undefined') lucide.createIcons(); // Refresh Lucide icons inside the new row
    }

    function addCrewMember() {
        crewCount++;
        const container = document.getElementById('crew-fields-container');
        
        const row = document.createElement('div');
        row.className = "member-row flex flex-col sm:flex-row gap-4 items-start sm:items-center border-b border-gray-100 dark:border-borderMain sm:border-none pb-4 sm:pb-0 animation-fade-in";
        
        row.innerHTML = `
            <div class="shrink-0">
                <input type="file" id="crew-img-${crewCount}" name="crew_images[]" class="hidden" accept="image/*" onchange="updateCastCrewImageName(this)">
                <label for="crew-img-${crewCount}" class="w-14 h-14 rounded-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder flex items-center justify-center cursor-pointer hover:bg-gray-100 dark:hover:bg-borderMain transition-colors shadow-sm">
                    <i data-lucide="upload" class="w-4 h-4 text-gray-400"></i>
                </label>
            </div>
            <div class="flex-1 space-y-2 w-full">
                <input type="text" name="crew_names[]" placeholder="Crew member name" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-2.5 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
                <input type="text" name="crew_roles[]" placeholder="Role (e.g. Cinematographer, Editor)" class="w-full bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-2.5 text-sm focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
            </div>
            <div class="w-full sm:w-auto flex justify-end">
                <button type="button" onclick="this.closest('.member-row').remove()" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors" title="Remove Member">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
            </div>
        `;
        
        container.appendChild(row);
        if (typeof lucide !== 'undefined') lucide.createIcons(); // Refresh Lucide icons inside the new row
    }

    // Utility function to turn avatar label green/check on successful selection
    function updateCastCrewImageName(input) {
        const label = input.nextElementSibling;
        if (input.files && input.files[0]) {
            label.classList.remove('bg-gray-50', 'dark:bg-inputBg', 'border-gray-200');
            label.classList.add('bg-green-50', 'dark:bg-green-950/30', 'border-green-500');
            label.innerHTML = `<i data-lucide="check" class="w-4 h-4 text-green-500"></i>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function updateFileName(input) {
        const display = document.getElementById('file-name-display');
        if (input.files && input.files[0]) {
            display.textContent = input.files[0].name;
        } else {
            display.textContent = "Upload Poster Image";
        }
    }

    let trailerCount = 0;
    function addTrailerRow(lang = '', url = '') {
        trailerCount++;
        const container = document.getElementById('trailer-fields-container');
        const row = document.createElement('div');
        row.className = "trailer-row flex items-center gap-3 border-b border-gray-100 dark:border-borderMain pb-3 last:border-none last:pb-0";
        row.innerHTML = `
            <input type="text" name="trailer_languages[]" value="${lang}" placeholder="Language (e.g. Hindi, Tamil)" class="w-1/3 bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-2.5 text-xs focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
            <input type="url" name="trailer_urls[]" value="${url}" placeholder="Trailer Embed URL (https://www.youtube.com/embed/...)" class="flex-1 bg-gray-50 dark:bg-inputBg border border-gray-200 dark:border-inputBorder text-gray-900 dark:text-white rounded-lg p-2.5 text-xs focus:border-brand focus:outline-none placeholder-gray-400 transition-colors">
            <button type="button" onclick="this.closest('.trailer-row').remove()" class="text-gray-400 hover:text-red-500 transition-colors p-1" title="Remove Trailer">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
        `;
        container.appendChild(row);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
</script>


</body>
</html>