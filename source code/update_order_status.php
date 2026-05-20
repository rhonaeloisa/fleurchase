<?php
header("Content-Type: application/json");
require "db_connection.php";

$order_id = str_replace("ORD-", "", $_POST["order_id"] ?? "");
$status = $_POST["status"] ?? "";

$conn->query("
UPDATE `order`
SET status='$status'
WHERE order_id='$order_id'
");

echo json_encode(["success" => true]);
?>