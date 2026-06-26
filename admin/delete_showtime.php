<?php
session_start();
require_once 'dbconnect.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM showtimes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: scheduling.php?deleted=true");
        } else {
            header("Location: scheduling.php?error=sqlfailed");
        }
        $stmt->close();
    }
} else {
    header("Location: scheduling.php");
}

$conn->close();
exit();
?>