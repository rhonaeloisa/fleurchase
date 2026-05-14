<?php
include 'connection_db.php';

$bouquet_id = $_POST['bouquet_id'];
$category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
$variation = $_POST['variation'] ?? '';
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? 0;
$is_custom = $_POST['is_custom'] ?? 0;
$status = $_POST['status'] ?? 'Active';
$category = $_POST['category'] ?? '';
$stock = $_POST['stock'] ?? 0;
$old_image = $_POST['old_image'] ?? '';

$image_name = $old_image;

if (!empty($_FILES['image']['name'])) {
  $image_name = basename($_FILES['image']['name']);
  move_uploaded_file($_FILES['image']['tmp_name'], '../images/' . $image_name);
}

$sql = "UPDATE bouquet SET
  category_id = ?,
  variation = ?,
  name = ?,
  description = ?,
  price = ?,
  is_custom = ?,
  image = ?,
  status = ?,
  category = ?,
  stock = ?
  WHERE bouquet_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
  $stmt,
  "isssdisssii",
  $category_id,
  $variation,
  $name,
  $description,
  $price,
  $is_custom,
  $image_name,
  $status,
  $category,
  $stock,
  $bouquet_id
);

if (mysqli_stmt_execute($stmt)) {
  header("Location: ../products-admin.php");
  exit;
} else {
  echo "Error: " . mysqli_error($conn);
}
?>
