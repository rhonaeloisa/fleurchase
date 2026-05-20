<?php
include 'connection_db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
  header("Location: ../products-admin.php");
  exit;
}

mysqli_begin_transaction($conn);

try {
  /* 1. Fetch the bouquet stock and all its products before deleting */
  $fetchBouquet = mysqli_prepare($conn, "SELECT stock FROM bouquet WHERE bouquet_id = ?");
  mysqli_stmt_bind_param($fetchBouquet, "i", $id);
  mysqli_stmt_execute($fetchBouquet);
  $bouquetResult = mysqli_stmt_get_result($fetchBouquet);
  $bouquet = mysqli_fetch_assoc($bouquetResult);
  mysqli_stmt_close($fetchBouquet);

  if (!$bouquet) throw new Exception("Bouquet not found.");

  $bouquet_stock = intval($bouquet['stock']);

  /* 2. Fetch all products used in this bouquet */
  $fetchProducts = mysqli_prepare($conn,
    "SELECT product_id, quantity FROM bouquet_product WHERE bouquet_id = ?"
  );
  mysqli_stmt_bind_param($fetchProducts, "i", $id);
  mysqli_stmt_execute($fetchProducts);
  $productsResult = mysqli_stmt_get_result($fetchProducts);
  $products = mysqli_fetch_all($productsResult, MYSQLI_ASSOC);
  mysqli_stmt_close($fetchProducts);

  /* 3. Restore stock for each product (qty per bouquet × bouquet stock) */
  if (!empty($products)) {
    $restoreStmt = mysqli_prepare($conn,
      "UPDATE product SET stock = stock + ? WHERE product_id = ?"
    );
    if (!$restoreStmt) throw new Exception('Prepare failed (stock restore): ' . mysqli_error($conn));

    foreach ($products as $p) {
      $pid          = intval($p['product_id']);
      $total_restore = intval($p['quantity']) * $bouquet_stock;

      mysqli_stmt_bind_param($restoreStmt, "ii", $total_restore, $pid);
      if (!mysqli_stmt_execute($restoreStmt))
        throw new Exception("Stock restore failed for product ID $pid: " . mysqli_stmt_error($restoreStmt));
    }
    mysqli_stmt_close($restoreStmt);
  }

  /* 4. Delete child records */
  $delChild = mysqli_prepare($conn, "DELETE FROM bouquet_product WHERE bouquet_id = ?");
  mysqli_stmt_bind_param($delChild, "i", $id);
  mysqli_stmt_execute($delChild);
  mysqli_stmt_close($delChild);

  /* 5. Delete bouquet */
  $delBouquet = mysqli_prepare($conn, "DELETE FROM bouquet WHERE bouquet_id = ?");
  mysqli_stmt_bind_param($delBouquet, "i", $id);
  mysqli_stmt_execute($delBouquet);
  mysqli_stmt_close($delBouquet);

  mysqli_commit($conn);
  header("Location: ../products-admin.php");
  exit;

} catch (Throwable $e) {
  mysqli_rollback($conn);
  echo "Error deleting bouquet: " . $e->getMessage();
}
?>