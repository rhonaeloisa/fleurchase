<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<?php
include 'connection_db.php';

$id = $_GET['id'] ?? '';

if (!$id) {
  header("Location: ../inventory-admin.php");
  exit;
}

$sql = "DELETE FROM product WHERE product_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
  header("Location: ../inventory-admin.php");
  exit;
} else {
  echo "Error deleting product: " . mysqli_error($conn);
}
?>
