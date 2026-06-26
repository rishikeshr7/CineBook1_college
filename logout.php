<?php
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session completely
session_destroy();

// Redirect back to the homepage (or wherever you want them to go)
header("Location: index.php");
exit();
?>