<?php
session_start();
require_once 'error_log.php';
custom_error_log("User logged out: " . ($_SESSION['username'] ?? 'Unknown'));

session_unset();
session_destroy();
header("Location: index.php");
exit();
?>