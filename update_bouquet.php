<?php //edit bouquet

session_start();
include 'connection_db.php';

function redirect_err(string $msg, int $id): never {
  $_SESSION['flash_err'] = $msg;
  header('Location: ../edit-bouquet.php?id=' . $id);
  exit;
}
function redirect_ok(string $msg): never {
  $_SESSION['flash_ok'] = $msg;
  header('Location: ../products-admin.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../products-admin.php');
  exit;
}

/* ── Collect & sanitise ── */
$bouquet_id  = intval($_POST['bouquet_id']  ?? 0);
$old_image   = trim($_POST['old_image']     ?? '');
$category    = trim($_POST['category']      ?? '');
$variation   = trim($_POST['variation']     ?? '');
$name        = trim($_POST['name']          ?? '');
$description = trim($_POST['description']   ?? '');
$price       = round(floatval($_POST['price'] ?? 0), 2);
$stock       = intval($_POST['stock']       ?? 0);
$status      = trim($_POST['status']        ?? 'Active');
$bouquet_type= trim($_POST['bouquet_type']  ?? 'bouquet');
$is_custom   = intval($_POST['is_custom']   ?? 0);
$date_arrived= trim($_POST['date_arrived']  ?? '');
$best_before = trim($_POST['best_before']   ?? '');
$wrapper     = trim($_POST['wrapper']       ?? '');
$remove_img  = intval($_POST['remove_image']?? 0);

if (!$bouquet_id) redirect_err('Invalid bouquet ID.', 0);
if (!$category || !$variation || !$name || !$date_arrived || !$best_before)
  redirect_err('Missing required fields.', $bouquet_id);
if ($best_before < $date_arrived)
  redirect_err('Best Before cannot be earlier than Date Arrived.', $bouquet_id);

/* ── Products JSON ── */
$products_raw = trim($_POST['products_json'] ?? '[]');
$products     = json_decode($products_raw, true);
if (!is_array($products)) redirect_err('Invalid product data.', $bouquet_id);

/* ── Image handling ── */
$new_image = $old_image;

if ($remove_img && $old_image) {
  $old_path = '../images/' . $old_image;
  if (file_exists($old_path)) @unlink($old_path);
  $new_image = '';
}

if (!empty($_FILES['image']['name'])) {
  $allowed = ['image/jpeg','image/png','image/webp'];
  $mime    = mime_content_type($_FILES['image']['tmp_name']);
  if (!in_array($mime, $allowed))
    redirect_err('Image must be JPG, PNG, or WEBP.', $bouquet_id);
  if ($_FILES['image']['size'] > 5 * 1024 * 1024)
    redirect_err('Image must be under 5 MB.', $bouquet_id);

  if ($old_image && file_exists('../images/' . $old_image)) @unlink('../images/' . $old_image);

  $ext       = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
  $new_image = 'bouquet_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  if (!move_uploaded_file($_FILES['image']['tmp_name'], '../images/' . $new_image)) {
    redirect_err('Failed to save image. Check folder permissions.', $bouquet_id);
  }
}

/* ── Transaction ── */
mysqli_begin_transaction($conn);

try {
  /* 1. Fetch the OLD bouquet stock before updating */
  $fetchOld = mysqli_prepare($conn, "SELECT stock FROM bouquet WHERE bouquet_id = ?");
  if (!$fetchOld) throw new Exception('Prepare failed (fetch old stock): ' . mysqli_error($conn));
  mysqli_stmt_bind_param($fetchOld, 'i', $bouquet_id);
  mysqli_stmt_execute($fetchOld);
  $oldResult   = mysqli_stmt_get_result($fetchOld);
  $oldBouquet  = mysqli_fetch_assoc($oldResult);
  mysqli_stmt_close($fetchOld);

  if (!$oldBouquet) throw new Exception('Bouquet not found.');
  $old_stock = intval($oldBouquet['stock']);

  /* 2. Fetch OLD products (product_id => quantity) */
  $fetchOldProds = mysqli_prepare($conn,
    "SELECT product_id, quantity FROM bouquet_product WHERE bouquet_id = ?"
  );
  if (!$fetchOldProds) throw new Exception('Prepare failed (fetch old products): ' . mysqli_error($conn));
  mysqli_stmt_bind_param($fetchOldProds, 'i', $bouquet_id);
  mysqli_stmt_execute($fetchOldProds);
  $oldProdsResult = mysqli_stmt_get_result($fetchOldProds);
  $old_products_map = []; // [product_id => quantity]
  while ($row = mysqli_fetch_assoc($oldProdsResult)) {
    $old_products_map[intval($row['product_id'])] = intval($row['quantity']);
  }
  mysqli_stmt_close($fetchOldProds);

  /* 3. Build new products map from submitted data */
  $new_products_map = []; // [product_id => quantity]
  foreach ($products as $p) {
    $pid = intval($p['product_id']);
    $qty = intval($p['quantity']);
    if ($pid > 0 && $qty > 0) {
      $new_products_map[$pid] = $qty;
    }
  }

  /* 4. Calculate stock adjustments per product
   *
   *  For every product that appears in old or new (or both):
   *    old_total = old_qty_per_bouquet × old_bouquet_stock   (what was deducted before)
   *    new_total = new_qty_per_bouquet × new_bouquet_stock   (what should be deducted now)
   *    adjustment = old_total - new_total
   *      positive → restore stock back to product
   *      negative → deduct more stock from product
   *      zero     → no change needed
   */
  $all_pids = array_unique(array_merge(
    array_keys($old_products_map),
    array_keys($new_products_map)
  ));

  $adjustStmt = mysqli_prepare($conn,
    "UPDATE product SET stock = stock + ? WHERE product_id = ?"
  );
  if (!$adjustStmt) throw new Exception('Prepare failed (stock adjust): ' . mysqli_error($conn));

  $checkStmt = mysqli_prepare($conn,
    "SELECT stock FROM product WHERE product_id = ?"
  );
  if (!$checkStmt) throw new Exception('Prepare failed (stock check): ' . mysqli_error($conn));

  foreach ($all_pids as $pid) {
    $old_qty   = $old_products_map[$pid] ?? 0;
    $new_qty   = $new_products_map[$pid] ?? 0;
    $old_total = $old_qty * $old_stock;
    $new_total = $new_qty * $stock;
    $adjustment = $old_total - $new_total; // positive = restore, negative = deduct more

    if ($adjustment === 0) continue; // nothing to do

    if ($adjustment < 0) {
      // Need to deduct more — check current stock first
      $deduct_amount = abs($adjustment);

      mysqli_stmt_bind_param($checkStmt, 'i', $pid);
      mysqli_stmt_execute($checkStmt);
      $checkResult   = mysqli_stmt_get_result($checkStmt);
      $productRow    = mysqli_fetch_assoc($checkResult);

      if (!$productRow || intval($productRow['stock']) < $deduct_amount) {
        $new_qty_label = $new_products_map[$pid] ?? 0;
        throw new Exception(
          "Insufficient stock for product ID $pid. " .
          "Need $deduct_amount more units ($new_qty_label per bouquet × $stock bouquets)."
        );
      }
    }

    // Apply adjustment (positive restores, negative deducts via negative addition)
    mysqli_stmt_bind_param($adjustStmt, 'ii', $adjustment, $pid);
    if (!mysqli_stmt_execute($adjustStmt))
      throw new Exception("Stock adjustment failed for product ID $pid: " . mysqli_stmt_error($adjustStmt));
  }

  mysqli_stmt_close($adjustStmt);
  mysqli_stmt_close($checkStmt);

  /* 5. Update bouquet row */
  $sql = "UPDATE bouquet SET
            category=?, variation=?, name=?, description=?, price=?,
            is_custom=?, image=?, status=?, stock=?,
            date_arrived=?, best_before=?, bouquet_type=?, wrapper=?
          WHERE bouquet_id=?";

  $stmt = mysqli_prepare($conn, $sql);
  if (!$stmt) throw new Exception('Prepare failed: ' . mysqli_error($conn));

  mysqli_stmt_bind_param($stmt, 'ssssdisssssssi',
    $category, $variation, $name, $description, $price,
    $is_custom, $new_image, $status, $stock,
    $date_arrived, $best_before, $bouquet_type, $wrapper,
    $bouquet_id
  );

  if (!mysqli_stmt_execute($stmt))
    throw new Exception('Update bouquet failed: ' . mysqli_stmt_error($stmt));
  mysqli_stmt_close($stmt);

  /* 6. Delete old bouquet_product rows */
  $del = mysqli_prepare($conn, "DELETE FROM bouquet_product WHERE bouquet_id=?");
  if (!$del) throw new Exception('Prepare delete failed: ' . mysqli_error($conn));
  mysqli_stmt_bind_param($del, 'i', $bouquet_id);
  if (!mysqli_stmt_execute($del))
    throw new Exception('Delete bouquet_product failed: ' . mysqli_stmt_error($del));
  mysqli_stmt_close($del);

  /* 7. Re-insert selected products */
  if (!empty($new_products_map)) {
    $ins = mysqli_prepare($conn,
      "INSERT INTO bouquet_product (bouquet_id, product_id, quantity, is_addons)
       VALUES (?, ?, ?, ?)"
    );
    if (!$ins) throw new Exception('Prepare insert failed: ' . mysqli_error($conn));

    foreach ($products as $p) {
      $pid      = intval($p['product_id']);
      $qty      = intval($p['quantity']);
      $is_addon = intval($p['is_addons'] ?? 0);
      if ($pid <= 0 || $qty <= 0) continue;
      mysqli_stmt_bind_param($ins, 'iiii', $bouquet_id, $pid, $qty, $is_addon);
      if (!mysqli_stmt_execute($ins))
        throw new Exception('Insert bouquet_product failed: ' . mysqli_stmt_error($ins));
    }
    mysqli_stmt_close($ins);
  }

  mysqli_commit($conn);
  redirect_ok('"' . htmlspecialchars($name) . '" updated successfully.');

} catch (Exception $e) {
  mysqli_rollback($conn);

  if (!empty($new_image) && $new_image !== $old_image && file_exists('../images/' . $new_image)) {
    @unlink('../images/' . $new_image);
  }

  redirect_err('Database error: ' . $e->getMessage(), $bouquet_id);
}