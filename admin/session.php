<?php
// session.php

// Safely start the session if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper Function: Redirect to login if the admin is NOT logged in
// Use this at the top of protected pages (e.g., admin_dashboard.php)
function require_login() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: admin_login.php");
        exit();
    }
}

// Helper Function: Redirect to dashboard if the admin IS already logged in
// Use this at the top of the login page so logged-in users don't see the form again
function redirect_if_logged_in() {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        header("Location: admin_dashboard.php");
        exit();
    }
}
?>