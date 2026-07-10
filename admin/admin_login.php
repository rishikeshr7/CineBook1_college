<?php
// admin_login.php

// 1. Include Session Logic
require_once 'session.php';

// If the admin is already logged in, send them straight to the dashboard
redirect_if_logged_in();

// 2. Include Database Connection
require_once 'dbconnect.php';

$error_message = '';

// 3. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } else {
        // Fetch the admin from the database using mysqli ($conn)
        $stmt = $conn->prepare("SELECT id, email, password_hash FROM admins WHERE email = ? LIMIT 1");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();

            // Verify password using PHP's built-in password_verify()
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Login Successful - Set Session Variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'];
                
                // Redirect to dashboard
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error_message = 'Invalid email or password.';
            }
            $stmt->close();
        } else {
            $error_message = 'Database error: Could not prepare statement.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <link rel="icon" type="image/svg+xml" href="/CineBook/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBook Admin - Login</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: '#0a0a0a',
                        card: '#121212',
                        input: '#1a1a1a',
                        border: '#262626',
                        brand: '#F5C518',
                        brandHover: '#eab308'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', "Liberation Mono", "Courier New", 'monospace'],
                    }
                }
            }
        }
    </script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-background text-gray-900 dark:text-gray-100 min-h-screen flex flex-col items-center justify-center p-4 relative font-sans">

    <button type="button" id="theme-toggle" class="absolute top-6 right-6 p-2.5 rounded-full bg-white dark:bg-card border border-gray-200 dark:border-border text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-input transition-colors shadow-sm" aria-label="Toggle Dark Mode">
        <i data-lucide="moon" id="theme-icon-moon" class="w-5 h-5 block dark:hidden"></i>
        <i data-lucide="sun" id="theme-icon-sun" class="w-5 h-5 hidden dark:block"></i>
    </button>

    <div class="w-full max-w-[420px] space-y-8">
        
        <div class="flex flex-col items-center justify-center text-center space-y-4">
            <div class="w-16 h-16 rounded-full border-2 border-brand flex items-center justify-center">
                <svg class="w-8 h-8 text-brand" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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
            </div>
            
            <div class="space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">CineBook Admin</h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Sign in to access the admin panel</p>
            </div>
        </div>

        <div class="bg-white dark:bg-card border border-gray-200 dark:border-border rounded-xl p-6 shadow-xl">
            
            <!-- PHP Error Display -->
            <?php if (!empty($error_message)): ?>
                <div class="mb-5 p-3 rounded-md bg-red-50 border border-red-200 text-red-600 dark:bg-red-900/20 dark:border-red-900/50 dark:text-red-400 text-sm font-medium text-center transition-colors">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Form setup with PHP Action -->
            <form id="admin-login-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-5">
                
                <div class="space-y-2">
                    <label for="email" class="block text-sm font-semibold text-gray-900 dark:text-gray-200">Admin Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="mail" class="h-5 w-5 text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <input type="email" id="email" name="email" required placeholder="admin@cinebook.com" 
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-border rounded-lg bg-gray-50 dark:bg-input text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="password" class="block text-sm font-semibold text-gray-900 dark:text-gray-200">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="h-5 w-5 text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <input type="password" id="password" name="password" required placeholder="Enter your password" 
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-border rounded-lg bg-gray-50 dark:bg-input text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all sm:text-sm">
                    </div>
                </div>

                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-black bg-brand hover:bg-brandHover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand transition-colors mt-2">
                    Sign In
                </button>

            </form>
            
        </div>

        <div class="text-center">
            <a href="../index.php" class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Back to Home
            </a>
        </div>

    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // --- Theme Toggle Logic ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;

        // Check for saved user preference or system preference on load
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            htmlElement.classList.add('dark');
        } else {
            htmlElement.classList.remove('dark');
        }

        // Toggle on click
        themeToggleBtn.addEventListener('click', () => {
            if (htmlElement.classList.contains('dark')) {
                htmlElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                htmlElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        });
    </script>
</body>
</html>

