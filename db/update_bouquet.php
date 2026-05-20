<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<?php

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
$new_image = $old_image; // default: keep existing

if ($remove_img && $old_image) {
  // Delete old file
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

  // Delete old file before replacing
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
  /* 1. Update bouquet row */
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

  /* 2. Delete existing bouquet_product rows for this bouquet */
  $del = mysqli_prepare($conn, "DELETE FROM bouquet_product WHERE bouquet_id=?");
  if (!$del) throw new Exception('Prepare delete failed: ' . mysqli_error($conn));
  mysqli_stmt_bind_param($del, 'i', $bouquet_id);
  if (!mysqli_stmt_execute($del))
    throw new Exception('Delete bouquet_product failed: ' . mysqli_stmt_error($del));
  mysqli_stmt_close($del);

  /* 3. Re-insert selected products */
  if (!empty($products)) {
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

  // Roll back uploaded image on failure
  if (!empty($new_image) && $new_image !== $old_image && file_exists('../images/' . $new_image)) {
    @unlink('../images/' . $new_image);
  }

  redirect_err('Database error: ' . $e->getMessage(), $bouquet_id);
}