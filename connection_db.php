<?php
$servername = "localhost";
$username   = "root";       // or your DB user
$password   = "";           // set if you added one
$database   = "fleurchase_db";
$port       = 3306;         // change to 3307 if you reconfigured XAMPP

$conn = new mysqli($servername, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
