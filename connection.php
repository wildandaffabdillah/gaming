<?php
$host = "localhost";
$username = "root";
$password = ""; // Default password XAMPP adalah kosong
$database = "gaming_store";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>