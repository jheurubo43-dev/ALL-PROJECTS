<?php
session_start();
session_destroy(); // This clears your login info
header("Location: login.php"); // Sends you back to the start
exit();
?>