<?php
session_start();
require_once 'dbconnect.php';

$error_message = '';

// --- 1. HANDLE FORM SUBMISSION (UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $movie_id      = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
    $theater_id    = trim($_POST['theater_id'] ?? '');
    $screen_id     = trim($_POST['screen_id'] ?? '');
    $format        = trim($_POST['format'] ?? '');
    $language      = trim($_POST['language'] ?? '');
    $show_date     = trim($_POST['show_date'] ?? '');
    $show_time     = trim($_POST['show_time'] ?? '');
    $total_seats   = isset($_POST['total_seats']) ? (int)$_POST['total_seats'] : 0;
    $price_regular = isset($_POST['price_regular']) ? (float)$_POST['price_regular'] : 0.00;
    $price_premium = isset($_POST['price_premium']) ? (float)$_POST['price_premium'] : 0.00;
    $price_vip     = isset($_POST['price_vip']) ? (float)$_POST['price_vip'] : 0.00;

    if ($id === 0 || $movie_id === 0 || empty($theater_id) || empty($show_date) || empty($show_time)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "UPDATE showtimes SET movie_id=?, theater_id=?, screen_id=?, format=?, language=?, show_date=?, show_time=?, total_seats=?, price_regular=?, price_premium=?, price_vip=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("issssssidddi", 
                $movie_id, $theater_id, $screen_id, $format, $language, 
                $show_date, $show_time, $total_seats, 
                $price_regular, $price_premium, $price_vip, $id
            );

            if ($stmt->execute()) {
                $m = date('n', strtotime($show_date));
                $y = date('Y', strtotime($show_date));
                header("Location: scheduling.php?success=showtimeupdated&m=$m&y=$y");
                exit();
            } else {
                $error_message = "Failed to update the showtime. Please try again.";
            }
            $stmt->close();
        } else {
            $error_message = "Database error: Could not prepare statement.";
        }
    }
}

// --- 2. FETCH EXISTING DATA FOR THE FORM (GET) ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    if (empty($error_message)) {
        header("Location: scheduling.php?error=invalidid");
        exit();
    }
}

$showtime_id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM showtimes WHERE id = ?");
$stmt->bind_param("i", $showtime_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: scheduling.php?error=notfound");
    exit();
}

$showtime = $result->fetch_assoc();
$stmt->close();

$movies_result = $conn->query("SELECT id, title FROM movies ORDER BY title ASC");
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBook Admin - Edit Showtime</title>
    
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
                    fontFamily: { sans: ['Inter', 'sans-serif'], }
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
        body, aside, div, header, input, button, table, tr, td, th { transition: background-color 0.2s, border-color 0.2s, color 0.2s; }
        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }
        ::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-bgMain text-gray-900 dark:text-gray-100 font-sans flex h-screen overflow-hidden">

    <?php include "sidebar.php"; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <div class="flex-1 overflow-y-auto p-8">
            
            <div class="mb-6 flex items-center gap-4">
                <a href="scheduling.php" class="flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors shadow-sm">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Showtime</h1>
                    <p class="text-gray-500 dark:text-textMuted text-sm">Update details for this specific screening</p>
                </div>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <div class="bg-white dark:bg-bgCard border border-gray-200 dark:border-borderMain rounded-xl p-6 shadow-sm max-w-3xl">
                
                <form action="edit_showtime.php?id=<?php echo $showtime_id; ?>" method="POST" class="space-y-5">
                    
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($showtime['id']); ?>">
                    
                    <div>
                        <label class="block text-sm font-bold mb-2 text-white">Movie <span class="text-red-500">*</span></label>
                        <select name="movie_id" required class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            <option value="" disabled>Select Movie</option>
                            <?php 
                                if ($movies_result && $movies_result->num_rows > 0) {
                                    while($m_row = $movies_result->fetch_assoc()) {
                                        $selected = ($m_row['id'] == $showtime['movie_id']) ? 'selected' : '';
                                        echo '<option value="' . $m_row['id'] . '" ' . $selected . '>' . htmlspecialchars($m_row['title']) . '</option>';
                                    }
                                }
                            ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold mb-2 text-white">Theater <span class="text-red-500">*</span></label>
                            <select name="theater_id" required class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                <option <?php echo ($showtime['theater_id'] == 'PVR Cinemas') ? 'selected' : ''; ?>>PVR Cinemas</option>
                                <option <?php echo ($showtime['theater_id'] == 'INOX') ? 'selected' : ''; ?>>INOX</option>
                                <option <?php echo ($showtime['theater_id'] == 'Cinepolis') ? 'selected' : ''; ?>>Cinepolis</option>
                                <option <?php echo ($showtime['theater_id'] == 'Carnival Cinemas') ? 'selected' : ''; ?>>Carnival Cinemas</option>
                                <option <?php echo ($showtime['theater_id'] == 'PVR IMAX') ? 'selected' : ''; ?>>PVR IMAX</option>
                                <option <?php echo ($showtime['theater_id'] == 'PVR Gold') ? 'selected' : ''; ?>>PVR Gold</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2 text-white">Screen</label>
                            <select name="screen_id" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                <option <?php echo ($showtime['screen_id'] == 'Audi 1') ? 'selected' : ''; ?>>Audi 1</option>
                                <option <?php echo ($showtime['screen_id'] == 'Audi 2') ? 'selected' : ''; ?>>Audi 2</option>
                                <option <?php echo ($showtime['screen_id'] == 'Audi 3') ? 'selected' : ''; ?>>Audi 3</option>
                                <option <?php echo ($showtime['screen_id'] == 'Audi 4') ? 'selected' : ''; ?>>Audi 4</option>
                                <option <?php echo ($showtime['screen_id'] == 'Audi 5') ? 'selected' : ''; ?>>Audi 5</option>
                                <option <?php echo ($showtime['screen_id'] == 'Gold Class') ? 'selected' : ''; ?>>Gold Class</option>
                                <option <?php echo ($showtime['screen_id'] == 'Platinum Lounge') ? 'selected' : ''; ?>>Platinum Lounge</option>
                                <option <?php echo ($showtime['screen_id'] == "Director's Cut") ? 'selected' : ''; ?>>Director's Cut</option>
                                <option <?php echo ($showtime['screen_id'] == 'Royal Recliner') ? 'selected' : ''; ?>>Royal Recliner</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold mb-2 text-white">Format</label>
                            <select name="format" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                <option <?php echo ($showtime['format'] == '2D') ? 'selected' : ''; ?>>2D</option>
                                <option <?php echo ($showtime['format'] == '3D') ? 'selected' : ''; ?>>3D</option>
                                <option <?php echo ($showtime['format'] == 'IMAX 2D') ? 'selected' : ''; ?>>IMAX 2D</option>
                                <option <?php echo ($showtime['format'] == 'IMAX 3D') ? 'selected' : ''; ?>>IMAX 3D</option>
                                <option <?php echo ($showtime['format'] == '4DX') ? 'selected' : ''; ?>>4DX</option>
                                <option <?php echo ($showtime['format'] == 'IMAX Laser') ? 'selected' : ''; ?>>IMAX Laser</option>
                                <option <?php echo ($showtime['format'] == 'Dolby Atmos') ? 'selected' : ''; ?>>Dolby Atmos</option>
                                <option <?php echo ($showtime['format'] == 'ICE') ? 'selected' : ''; ?>>ICE</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2 text-white">Language</label>
                            <select name="language" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                                <option <?php echo ($showtime['language'] == 'English') ? 'selected' : ''; ?>>English</option>
                                <option <?php echo ($showtime['language'] == 'Hindi') ? 'selected' : ''; ?>>Hindi</option>
                                <option <?php echo ($showtime['language'] == 'Bengali') ? 'selected' : ''; ?>>Bengali</option>
                                <option <?php echo ($showtime['language'] == 'Kannada') ? 'selected' : ''; ?>>Kannada</option>
                                <option <?php echo ($showtime['language'] == 'Tamil') ? 'selected' : ''; ?>>Tamil</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold mb-2 text-white">Date <span class="text-red-500">*</span></label>
                            <input type="date" name="show_date" value="<?php echo htmlspecialchars($showtime['show_date']); ?>" required class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2 text-white">Time <span class="text-red-500">*</span></label>
                            <input type="time" name="show_time" value="<?php echo date('H:i', strtotime($showtime['show_time'])); ?>" required class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold mb-2 text-white">Total Seats</label>
                        <input type="number" name="total_seats" value="<?php echo htmlspecialchars($showtime['total_seats']); ?>" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-white mt-2 mb-3">Pricing</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold mb-2 text-gray-300">Regular</label>
                                <input type="number" name="price_regular" value="<?php echo htmlspecialchars($showtime['price_regular']); ?>" min="0" step="0.5" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-2 text-gray-300">Premium</label>
                                <input type="number" name="price_premium" value="<?php echo htmlspecialchars($showtime['price_premium']); ?>" min="0" step="0.5" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-2 text-gray-300">VIP</label>
                                <input type="number" name="price_vip" value="<?php echo htmlspecialchars($showtime['price_vip']); ?>" min="0" step="0.5" class="w-full bg-inputBg border border-inputBorder text-white rounded-lg p-3 text-sm focus:border-brand focus:outline-none transition-colors">
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 flex justify-between items-center border-t border-borderMain mt-6">
                        <a href="scheduling.php" class="text-white text-sm font-bold hover:text-gray-300 transition-colors px-4 py-2">
                            Cancel
                        </a>
                        <button type="submit" class="w-[200px] py-3 rounded-lg bg-brand text-black text-sm font-bold hover:bg-yellow-500 transition-colors shadow-md">
                            Save Changes
                        </button>
                    </div>
                    
                </form>

            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        const themeToggle = document.getElementById('toggle-theme');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
            });
        }
    </script>
</body>
</html>