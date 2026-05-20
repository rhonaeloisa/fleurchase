<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<?php
include 'connection_db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
  header("Location: ../products-admin.php");
  exit;
}

mysqli_begin_transaction($conn);

try {
  // Remove from active carts first
  $sqlCart = "DELETE FROM cart_item WHERE bouquet_id = ?";
  $stmtCart = mysqli_prepare($conn, $sqlCart);
  mysqli_stmt_bind_param($stmtCart, "i", $id);
  mysqli_stmt_execute($stmtCart);

  // Remove bouquet product relations
  $sqlChild = "DELETE FROM bouquet_product WHERE bouquet_id = ?";
  $stmtChild = mysqli_prepare($conn, $sqlChild);
  mysqli_stmt_bind_param($stmtChild, "i", $id);
  mysqli_stmt_execute($stmtChild);

  // Delete bouquet
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