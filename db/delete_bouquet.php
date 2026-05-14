<?php
include 'connection_db.php';

$id = $_GET['id'] ?? '';

if (!$id) {
  header("Location: ../products-admin.php");
  exit;
}

$sql = "DELETE FROM bouquet WHERE bouquet_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
  header("Location: ../products-admin.php");
  exit;
} else {
  echo "Error deleting bouquet: " . mysqli_error($conn);
}
?>
