<?php
// Get the name of the current file (e.g., "admin_dashboard.php", "user.php")
$current_page = basename($_SERVER['PHP_SELF']);

// Define our active and inactive CSS classes
$active_class = "bg-brand/10 text-yellow-600 dark:text-brand border border-brand/20";
$inactive_class = "text-gray-500 dark:text-textMuted hover:text-gray-900 hover:bg-gray-100 dark:hover:text-gray-100 dark:hover:bg-bgCard border border-transparent";
?>

<aside class="w-64 bg-white dark:bg-bgMain border-r border-gray-200 dark:border-borderMain flex flex-col justify-between flex-shrink-0">
    <div>
        <div class="h-20 flex items-center px-6 border-b border-gray-200 dark:border-borderMain">
            <div class="flex items-center gap-2">
                <svg class="w-6 h-6 text-brand" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <mask id="film-cutout">
                        <rect width="24" height="24" fill="white"/>
                        <rect x="7.5" y="4" width="9" height="6.5" fill="black" rx="0.5"/>
                        <rect x="7.5" y="13.5" width="9" height="6.5" fill="black" rx="0.5"/>
                        <rect x="3.5" y="4.5" width="2" height="2.5" fill="black" rx="0.5"/>
                        <rect x="3.5" y="10.75" width="2" height="2.5" fill="black" rx="0.5"/>
                        <rect x="3.5" y="17" width="2" height="2.5" fill="black" rx="0.5"/>
                        <rect x="18.5" y="4.5" width="2" height="2.5" fill="black" rx="0.5"/>
                        <rect x="18.5" y="10.75" width="2" height="2.5" fill="black" rx="0.5"/>
                        <rect x="18.5" y="17" width="2" height="2.5" fill="black" rx="0.5"/>
                    </mask>
                    <rect x="1" y="1.5" width="22" height="21" rx="3.5" fill="currentColor" mask="url(#film-cutout)"/>
                </svg>
                <span class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">CineBook Admin</span>
            </div>
        </div>

        <nav class="p-4 space-y-1">
            <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition-colors <?php echo ($current_page == 'admin_dashboard.php') ? $active_class : $inactive_class; ?>">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </a>
            
            <a href="user.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition-colors <?php echo ($current_page == 'user.php') ? $active_class : $inactive_class; ?>">
                <i data-lucide="users" class="w-5 h-5"></i> Users
            </a>
            
            <a href="scheduling.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition-colors <?php echo ($current_page == 'scheduling.php') ? $active_class : $inactive_class; ?>">
                <i data-lucide="calendar" class="w-5 h-5"></i> Scheduling
            </a>
        </nav>
    </div>

    <div class="p-4 border-t border-gray-200 dark:border-borderMain space-y-1">
        <button id="toggle-theme" class="w-full flex items-center gap-3 px-3 py-2.5 text-gray-500 dark:text-textMuted hover:text-gray-900 hover:bg-gray-100 dark:hover:text-gray-100 dark:hover:bg-bgCard rounded-lg font-medium transition-colors">
            <i data-lucide="moon" class="w-5 h-5 block dark:hidden"></i>
            <i data-lucide="sun" class="w-5 h-5 hidden dark:block"></i> Toggle Theme
        </button>
        <a href="../index.php" class="flex items-center gap-3 px-3 py-2.5 text-gray-500 dark:text-textMuted hover:text-gray-900 hover:bg-gray-100 dark:hover:text-gray-100 dark:hover:bg-bgCard rounded-lg font-medium transition-colors">
            <i data-lucide="external-link" class="w-5 h-5"></i> Back to Site
        </a>
        <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-400/10 rounded-lg font-medium transition-colors">
            <i data-lucide="log-out" class="w-5 h-5"></i> Logout
        </a>
    </div>
</aside>