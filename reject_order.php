<?php
header("Content-Type: application/json");
require "db_connection.php";

$order_id = str_replace("ORD-", "", $_POST["order_id"] ?? "");

$conn->query("
UPDATE `order`
SET status='Cancelled'
WHERE order_id='$order_id'
");

$conn->query("
UPDATE payment
SET status='rejected'
WHERE order_id='$order_id'
");

echo json_encode(["success" => true]);
?>