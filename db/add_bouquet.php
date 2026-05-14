<?php
session_start();
include 'connection_db.php';

$created_by_user_id = $_SESSION['user_id'] ?? 1;

$variation = $_POST['variation'] ?? '';
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? 0;
$is_custom = $_POST['is_custom'] ?? 0;
$status = $_POST['status'] ?? 'Active';
$category = $_POST['category'] ?? '';
$stock = $_POST['stock'] ?? 0;

$image_name = '';

if (!empty($_FILES['image']['name'])) {
  $image_name = basename($_FILES['image']['name']);
  $target_path = '../images/' . $image_name;

  move_uploaded_file($_FILES['image']['tmp_name'], $target_path);
}

$sql = "INSERT INTO bouquet 
  (created_by_user_id, variation, name, description, price, is_custom, image, status, category, stock)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
  $stmt,
  "isssdisssi",
  $created_by_user_id,
  $variation,
  $name,
  $description,
  $price,
  $is_custom,
  $image_name,
  $status,
  $category,
  $stock
);

if (mysqli_stmt_execute($stmt)) {
  header("Location: ../products-admin.php");
  exit;
} else {
  echo "Error: " . mysqli_error($conn);
}
?>
