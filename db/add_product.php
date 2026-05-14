<?php
include 'connection_db.php';

$product_name = $_POST['product_name'] ?? '';
$price = $_POST['price'] ?? 0;
$status = $_POST['status'] ?? 'Active';
$product_type = $_POST['product_type'] ?? '';
$stock = $_POST['stock'] ?? 0;
$date_arrived = $_POST['date_arrived'] ?? '';
$best_before_date = $_POST['best_before_date'] ?? '';

$image_name = '';

if (!empty($_FILES['product_image']['name'])) {
  $image_name = basename($_FILES['product_image']['name']);
  move_uploaded_file($_FILES['product_image']['tmp_name'], '../images/' . $image_name);
}

$sql = "INSERT INTO product 
  (product_name, price, status, product_type, product_image, stock, date_arrived, best_before_date)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param(
  $stmt,
  "sdsssiss",
  $product_name,
  $price,
  $status,
  $product_type,
  $image_name,
  $stock,
  $date_arrived,
  $best_before_date
);

if (mysqli_stmt_execute($stmt)) {
  header("Location: ../inventory-admin.php");
  exit;
} else {
  echo "Error: " . mysqli_error($conn);
}
?>
