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

    // Basic Validation
    if (empty($fullname) || empty($email) || empty($phone) || empty($raw_password)) {
        die("Error: All fields are required.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Error: Invalid email format.");
    }

    // Securely hash the password
    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

    // 3. Check if the email is already registered using a prepared statement
    $check_query = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    
    if ($stmt) {
        // "s" indicates the variable type is string
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            die("Error: This email is already registered. Please log in.");
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Database error: Failed to prepare the check query.");
    }

    // 4. Insert the new user into the database
    $insert_query = "INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    
    if ($stmt) {
        // "ssss" indicates four string parameters
        mysqli_stmt_bind_param($stmt, "ssss", $fullname, $email, $phone, $hashed_password);
        
        if (mysqli_stmt_execute($stmt)) {
            // Success! 
            echo "<script>
                alert('Account successfully created!');
                window.location.href = 'index.php'; // Change to your actual login page
            </script>";
            // exit();
        } else {
            echo "Error: Something went wrong while creating your account. " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Database error: Failed to prepare the insert query.");
    }

    // Close the database connection
    mysqli_close($conn);

} else {
    // If someone tries to access this file directly without submitting the form
    die("Error: Invalid request method.");
}
?>