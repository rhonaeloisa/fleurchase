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
SET status = 'Processing'
WHERE order_id = '$order_id'
");

$conn->query("
UPDATE payment
SET status = 'verified',
    reference_number = '$ref_num'
WHERE order_id = '$order_id'
");

$userResult = $conn->query("
SELECT user_id
FROM `order`
WHERE order_id = '$order_id'
");

if ($userResult && $userResult->num_rows > 0) {
    $order = $userResult->fetch_assoc();
    $user_id = $order["user_id"];

    $title = "Payment Verified";
    $body = "Your payment for order ORD-$order_id has been verified. Your order is now Processing.";

    $stmt = $conn->prepare("
        INSERT INTO notification (user_id, type, title, body, is_read, created_at)
        VALUES (?, 'payment', ?, ?, 0, NOW())
    ");
    $stmt->bind_param("iss", $user_id, $title, $body);
    $stmt->execute();
}

echo json_encode(["success" => true]);
?>