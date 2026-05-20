<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<?php
header('Content-Type: application/json');
require 'connection_db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$discount = isset($_GET['discount']) ? intval($_GET['discount']) : 0;

if ($id <= 0 || $discount < 10 || $discount > 70) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid input'
    ]);
    exit;
}

/* Get current price */
$stmt = mysqli_prepare($conn, "SELECT price FROM bouquet WHERE bouquet_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo json_encode([
        'success' => false,
        'error' => 'Bouquet not found'
    ]);
    exit;
}

$currentPrice = $row['price'];
$newPrice = $currentPrice - ($currentPrice * ($discount / 100));

/* Update price */
$update = mysqli_prepare($conn, "UPDATE bouquet SET price = ? WHERE bouquet_id = ?");
mysqli_stmt_bind_param($update, "di", $newPrice, $id);

if (mysqli_stmt_execute($update)) {
    echo json_encode([
        'success' => true
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Update failed'
    ]);
}
?>