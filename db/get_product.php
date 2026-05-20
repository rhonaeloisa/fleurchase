<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<?php
include 'connection_db.php';

$id = $_GET['id'] ?? '';

$sql = "SELECT * FROM product WHERE product_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

header('Content-Type: application/json');

if ($row) {
  echo json_encode($row);
} else {
  echo json_encode(["error" => "Product not found."]);
}
?>
