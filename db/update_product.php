<?php
// db/update_product.php
// Updates a filler or addon row in the product table

session_start();
include 'connection_db.php';

function back(string $msg, bool $ok = false): never {
  $_SESSION[$ok ? 'flash_ok' : 'flash_err'] = $msg;
  header('Location: ../inventory-admin.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../inventory-admin.php');
  exit;
}

$product_id   = intval($_POST['product_id']   ?? 0);
$old_image    = trim($_POST['old_image']       ?? '');
$product_name = trim($_POST['product_name']    ?? '');
$product_type = trim($_POST['product_type']    ?? '');
$stock        = intval($_POST['stock']         ?? 0);
$price        = round(floatval($_POST['price'] ?? 0), 2);
$date_arrived = trim($_POST['date_arrived']    ?? '');
$best_before  = trim($_POST['best_before_date']?? '');
$status       = trim($_POST['status']          ?? 'Active');

if (!$product_id || !$product_name || !$product_type || !$date_arrived || !$best_before)
  back('Missing required fields.');

/* Only allow valid types in this handler */
if (!in_array($product_type, ['filler','addon']))
  back('Invalid product type for this handler.');

if ($best_before < $date_arrived)
  back('Best Before cannot be earlier than Date Arrived.');

/* ── Image handling ── */
$image_filename = $old_image;

if (!empty($_FILES['product_image']['name'])) {
  $allowed = ['image/jpeg','image/png','image/webp'];
  $mime    = mime_content_type($_FILES['product_image']['tmp_name']);
  if (!in_array($mime, $allowed)) back('Image must be JPG, PNG, or WEBP.');
  if ($_FILES['product_image']['size'] > 5 * 1024 * 1024) back('Image must be under 5 MB.');

  if ($old_image && file_exists('../images/' . $old_image)) @unlink('../images/' . $old_image);

  $ext            = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
  $image_filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  if (!move_uploaded_file($_FILES['product_image']['tmp_name'], '../images/' . $image_filename))
    back('Failed to save image.');
}

/* ── Update ── */
$stmt = mysqli_prepare($conn,
  "UPDATE product
   SET product_name=?, product_type=?, stock=?, price=?,
       date_arrived=?, best_before_date=?, status=?, product_image=?
   WHERE product_id=?"
);

if (!$stmt) back('DB error: ' . mysqli_error($conn));

mysqli_stmt_bind_param($stmt, 'ssiidsssi',
  $product_name, $product_type, $stock, $price,
  $date_arrived, $best_before, $status, $image_filename,
  $product_id
);

if (!mysqli_stmt_execute($stmt)) back('Update failed: ' . mysqli_stmt_error($stmt));
mysqli_stmt_close($stmt);

back('"' . htmlspecialchars($product_name) . '" updated successfully.', true);