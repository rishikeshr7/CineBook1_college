<?php
session_start();
require_once 'dbconnect.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM movies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: admin_dashboard.php?deleted=true");
        } else {
            header("Location: admin_dashboard.php?error=sqlfailed");
        }
        $stmt->close();
    }
} else {
    header("Location: admin_dashboard.php");
}
$conn->close();
exit();
?>