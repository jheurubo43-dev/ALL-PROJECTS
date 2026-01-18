<?php
// We are using 127.0.0.1 because that is what your phpMyAdmin shows.
$connection = mysqli_connect("127.0.0.1", "root", "", "lms_system");

if (!$connection) {
    // This will tell us EXACTLY why it failed
    die("Connection failed: " . mysqli_connect_error());
}
?>