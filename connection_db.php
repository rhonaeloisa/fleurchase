<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$db   = getenv('DB_NAME') ?: 'fleurchase_db1';
$port = (int)(getenv('DB_PORT') ?: 3306);
$sslCa = getenv('DB_SSL_CA') ?: '';

$conn = mysqli_init();

if ($sslCa !== '') {
    $conn->ssl_set(null, null, $sslCa, null, null);
}

$conn->real_connect(
    $host,
    $user,
    $pass,
    $db,
    $port,
    null,
    $sslCa !== '' ? MYSQLI_CLIENT_SSL : 0
);

if ($conn->connect_errno) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
