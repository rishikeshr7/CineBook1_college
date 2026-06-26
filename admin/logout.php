<?php
// logout.php

// 1. Start the session so we can access it to destroy it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Unset all of the session variables
$_SESSION = array();

// 3. If it's desired to kill the session completely, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finally, destroy the session
session_destroy();

// 5. Redirect back to the login page
header("Location: admin_login.php");
exit();
?>