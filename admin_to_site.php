<?php
/**
 * admin_to_site.php
 * Bridge script: When an admin clicks "Back to Site", this sets the
 * front-end user session variables so they appear logged in as "CineBook Admin"
 * across all user-facing pages (booking tickets, browsing, etc.)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow this if the admin is actually logged in on the admin side
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Not an admin — redirect to home without logging in
    header("Location: index.php");
    exit();
}

// Get admin email from session
$admin_email = $_SESSION['admin_email'] ?? 'admin@cinebook.com';

// Connect to the database to find or create a matching user record
require_once 'admin/dbconnect.php';

$user_id = null;

// Look for an existing user with the admin email
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("s", $admin_email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];
    }
    $stmt->close();
}

// If no user record found, create one automatically for the admin
if (!$user_id) {
    $admin_name = 'CineBook Admin';
    // Use a random-ish password hash (admin won't use it to log in via user form)
    $placeholder_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $placeholder_phone = '0000000000';

    $ins = $conn->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)");
    if ($ins) {
        $ins->bind_param("ssss", $admin_name, $admin_email, $placeholder_phone, $placeholder_hash);
        $ins->execute();
        $user_id = $conn->insert_id;
        $ins->close();
    }
}

// Set the user-side session variables so the front-end header recognises them
$_SESSION['logged_in']      = true;
$_SESSION['user_id']        = $user_id;
$_SESSION['user_email']     = $admin_email;
$_SESSION['user_name']      = 'CineBook Admin';
$_SESSION['is_admin_visit'] = true; // Flag so we can show the "Back to Admin" link in the header

// Redirect to the home page
header("Location: index.php");
exit();
?>
