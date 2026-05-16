<?php
include 'connection_db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
  header("Location: ../products-admin.php");
  exit;
}

mysqli_begin_transaction($conn);

try {
  // Delete child records first
  $sqlChild = "DELETE FROM bouquet_product WHERE bouquet_id = ?";
  $stmtChild = mysqli_prepare($conn, $sqlChild);
  mysqli_stmt_bind_param($stmtChild, "i", $id);
  mysqli_stmt_execute($stmtChild);

  // Then delete bouquet
  $sql = "DELETE FROM bouquet WHERE bouquet_id = ?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $id);
  mysqli_stmt_execute($stmt);

  mysqli_commit($conn);

  header("Location: ../products-admin.php");
  exit;
} catch (Throwable $e) {
  mysqli_rollback($conn);
  echo "Error deleting bouquet: " . $e->getMessage();
}
?>
