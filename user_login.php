<?php
// 1. Start the session
session_start();

// 2. Include your database connection file
require_once 'dbconnect.php'; 

// 3. Check if the form was actually submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Grab and sanitize input data
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($email) || empty($password)) {
        header("Location: login.php?error=emptyfields");
        exit();
    }

    // 4. Secure SQL Query using Prepared Statements
    $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
    
    // IMPORTANT: Make sure your dbconnect.php file uses '$conn' as the connection variable.
    // If it uses something else like '$mysqli' or '$db', change '$conn' below to match it.
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // 5. Verify the password
            if (password_verify($password, $user['password'])) {
                
                // Password correct: Regenerate session ID
                session_regenerate_id(true);

                // Store session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['full_name'] = $user['fullname'];
                $_SESSION['logged_in'] = true;
                // print_r($_SESSION);
                // exit();

                // Redirect to dashboard
                header("Location: index.php");
                // exit();
                
            } else {
                header("Location: login.php?error=wrongpassword");
                exit();
            }
        } else {
            header("Location: login.php?error=nouser");
            exit();
        }

        $stmt->close();
    } else {
        header("Location: login.php?error=sqlerror");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}

$conn->close();
?>