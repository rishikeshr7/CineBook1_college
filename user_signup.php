<?php
// 1. Include the database connection file
require_once 'dbconnect.php';

// 2. Process Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve and sanitize inputs
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $raw_password = $_POST['password'];

    // Get the redirect path
    $redirect_to = isset($_POST['redirect_to']) ? trim($_POST['redirect_to']) : 'index.php';
    // Clean redirect path of existing errors
    $redirect_to = preg_replace('/[?&]error=[^&]+/', '', $redirect_to);
    $redirect_to = rtrim($redirect_to, '?&');
    $separator = (strpos($redirect_to, '?') === false) ? '?' : '&';

    // Basic Validation
    if (empty($fullname) || empty($email) || empty($phone) || empty($raw_password)) {
        header("Location: " . $redirect_to . $separator . "error=signup_emptyfields");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: " . $redirect_to . $separator . "error=invalidemail");
        exit();
    }

    // Securely hash the password
    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

    // 3. Check if the email is already registered using a prepared statement
    $check_query = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            header("Location: " . $redirect_to . $separator . "error=emailregistered");
            exit();
        }
        mysqli_stmt_close($stmt);
    } else {
        header("Location: " . $redirect_to . $separator . "error=signup_sqlerror");
        exit();
    }

    // 4. Insert the new user into the database
    $insert_query = "INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $fullname, $email, $phone, $hashed_password);
        
        if (mysqli_stmt_execute($stmt)) {
            // Success! Get the newly inserted user's ID
            $new_user_id = mysqli_insert_id($conn);
            
            // Automatically log them in by setting session variables
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['user_email'] = $email;
            $_SESSION['full_name'] = $fullname;
            $_SESSION['logged_in'] = true;

            // Redirect back to the referrer page
            header("Location: " . $redirect_to);
            exit();
        } else {
            header("Location: " . $redirect_to . $separator . "error=signup_sqlerror");
            exit();
        }
        mysqli_stmt_close($stmt);
    } else {
        header("Location: " . $redirect_to . $separator . "error=signup_sqlerror");
        exit();
    }

    // Close the database connection
    mysqli_close($conn);

} else {
    header("Location: index.php");
    exit();
}
?>