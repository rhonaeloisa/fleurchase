<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<?php
header("Content-Type: application/json");
require "db_connection.php";

$order_id = str_replace("ORD-", "", $_POST["order_id"] ?? "");
$ref_num = $_POST["ref_num"] ?? "";

if (!$order_id) {
    echo json_encode(["success" => false, "message" => "Missing order ID"]);
    exit;
}

$conn->query("
UPDATE `order`
SET status='Processing'
WHERE order_id='$order_id'
");

$conn->query("
UPDATE payment
SET status='verified',
    reference_number='$ref_num'
WHERE order_id='$order_id'
");

echo json_encode(["success" => true]);
?>