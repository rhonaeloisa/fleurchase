<?php
// db/save_bouquet.php
// Receives the submitted form, inserts into bouquet + bouquet_product

session_start();
include 'connection_db.php';

/* ── Helpers ── */
function redirect_err(string $msg): never {
  $_SESSION['flash_err'] = $msg;
  header('Location: ../add_bouquet.php');
  exit;
}

function redirect_ok(string $msg, string $to = '../products-admin.php'): never {
  $_SESSION['flash_ok'] = $msg;
  header('Location: ' . $to);
  exit;
}

/* ── Only accept POST ── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect_err('Invalid request method.');
}

/* ── Sanitise scalar fields ── */
$category    = trim($_POST['category']    ?? '');
$variation   = trim($_POST['variation']   ?? '');
$name        = trim($_POST['name']        ?? '');
$description = trim($_POST['description'] ?? '');
$price       = floatval($_POST['price']   ?? 0);
$stock       = intval($_POST['stock']     ?? 0);
$status      = trim($_POST['status']      ?? 'Active');
$bouquet_type= trim($_POST['bouquet_type'] ?? 'bouquet');
$is_custom   = intval($_POST['is_custom'] ?? 0);
$date_arrived= trim($_POST['date_arrived'] ?? '');
$best_before = trim($_POST['best_before']  ?? '');
$wrapper     = trim($_POST['wrapper']      ?? '');

// created_by_user_id — pull from session if you store user id there
$created_by  = intval($_SESSION['user_id'] ?? 0);

/* ── Basic server-side validation ── */
if (!$category || !$variation || !$name || !$date_arrived || !$best_before) {
  redirect_err('Missing required fields.');
}
if ($price < 0)   redirect_err('Price cannot be negative.');
if ($stock < 0)   redirect_err('Stock cannot be negative.');
if ($best_before < $date_arrived) redirect_err('Best Before cannot be earlier than Date Arrived.');

/* ── Products JSON ── */
$products_raw = trim($_POST['products_json'] ?? '[]');
$products     = json_decode($products_raw, true);
if (!is_array($products)) redirect_err('Invalid product selection data.');

/* ── Handle image upload ── */
$image_filename = '';
if (!empty($_FILES['image']['name'])) {
  $allowed = ['image/jpeg', 'image/png', 'image/webp'];
  $mime    = mime_content_type($_FILES['image']['tmp_name']);

  if (!in_array($mime, $allowed)) redirect_err('Image must be JPG, PNG, or WEBP.');
  if ($_FILES['image']['size'] > 5 * 1024 * 1024) redirect_err('Image must be under 5 MB.');

  $ext             = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
  $image_filename  = 'bouquet_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
  $upload_path     = '../images/' . $image_filename;   // adjust path to your images folder

  if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
    redirect_err('Failed to save image. Check folder permissions.');
  }
}

/* ── BEGIN TRANSACTION ── */
mysqli_begin_transaction($conn);

try {
  /* 1. Insert into bouquet */
  $sql = "INSERT INTO bouquet
            (created_by_user_id, category, variation, name, description,
             price, is_custom, image, status, stock,
             date_arrived, best_before, bouquet_type, wrapper)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $stmt = mysqli_prepare($conn, $sql);
  if (!$stmt) throw new Exception('Prepare failed: ' . mysqli_error($conn));

  mysqli_stmt_bind_param(
    $stmt, 'issssdisisssss',
    /* i */ $created_by,
    /* s */ $category,
    /* s */ $variation,
    /* s */ $name,
    /* s */ $description,
    /* d */ $price,
    /* i */ $is_custom,
    /* s */ $image_filename,
    /* s */ $status,
    /* i */ $stock,
    /* s */ $date_arrived,
    /* s */ $best_before,
    /* s */ $bouquet_type,
    /* s */ $wrapper
  );

  if (!mysqli_stmt_execute($stmt)) throw new Exception('Insert bouquet failed: ' . mysqli_stmt_error($stmt));
  $bouquet_id = mysqli_insert_id($conn);
  mysqli_stmt_close($stmt);

  /* 2. Insert each product into bouquet_product + deduct stock */
  if (!empty($products)) {
    $sql2 = "INSERT INTO bouquet_product
               (bouquet_id, product_id, quantity, is_addons)
             VALUES (?, ?, ?, ?)";
    $stmt2 = mysqli_prepare($conn, $sql2);
    if (!$stmt2) throw new Exception('Prepare failed (bouquet_product): ' . mysqli_error($conn));

    // Deduct stock safely — WHERE stock >= ? prevents going negative
    $sql3 = "UPDATE product
             SET stock = stock - ?
             WHERE product_id = ? AND stock >= ?";
    $stmt3 = mysqli_prepare($conn, $sql3);
    if (!$stmt3) throw new Exception('Prepare failed (stock deduction): ' . mysqli_error($conn));

    foreach ($products as $p) {
      $pid      = intval($p['product_id']);
      $qty      = intval($p['quantity']);
      $is_addon = intval($p['is_addons'] ?? 0);

      if ($pid <= 0 || $qty <= 0) continue; // skip bad rows

      $total_deduct = $qty * $stock;
      
      /* Insert bouquet_product row */
      mysqli_stmt_bind_param($stmt2, 'iiii', $bouquet_id, $pid, $qty, $is_addon);
      if (!mysqli_stmt_execute($stmt2)) throw new Exception('Insert bouquet_product failed: ' . mysqli_stmt_error($stmt2));

      /* Deduct stock from product */
      mysqli_stmt_bind_param($stmt3, 'iii', $total_deduct, $pid, $total_deduct);
      if (!mysqli_stmt_execute($stmt3)) throw new Exception('Stock deduction failed: ' . mysqli_stmt_error($stmt3));

      /* If no row was updated, the product had insufficient stock */
      if (mysqli_stmt_affected_rows($stmt3) === 0) {
        throw new Exception("Insufficient stock for product ID $pid. Please check available quantities.");
      }
    }

    mysqli_stmt_close($stmt2);
    mysqli_stmt_close($stmt3);
  }

  mysqli_commit($conn);
  redirect_ok('Bouquet "' . htmlspecialchars($name) . '" added successfully!');

} catch (Exception $e) {
  mysqli_rollback($conn);

  /* Delete uploaded image if transaction failed */
  if ($image_filename && file_exists('../images/' . $image_filename)) {
    unlink('../images/' . $image_filename);
  }

  redirect_err('Database error: ' . $e->getMessage());
}