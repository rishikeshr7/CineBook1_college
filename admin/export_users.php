<?php
session_start();

// Include database connection
require_once 'dbconnect.php';

// (Optional) Check if the user is an admin before allowing download
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: admin_login.php");
//     exit();
// }

// 1. Set the headers to force a CSV file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users_export_' . date('d-m-y') . '.csv');

// 2. Open the output stream
$output = fopen('php://output', 'w');

// 3. Output the column headings
fputcsv($output, array('ID', 'Full Name', 'Email', 'Registration Date'));

// 4. Fetch the users from the database
$sql = "SELECT id, fullname, email, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    // 5. Loop through the rows and output them to the CSV
    while ($row = $result->fetch_assoc()) {
        // Format the date if desired, or output exactly as it is in the DB
        $formatted_date = date('d-m-y H:i:s', strtotime($row['created_at']));
        
        $lineData = array(
            $row['id'], 
            $row['fullname'], 
            $row['email'], 
            $formatted_date
        );
        fputcsv($output, $lineData);
    }
}

// 6. Close the output stream and the database connection
fclose($output);
$conn->close();
exit();
?>