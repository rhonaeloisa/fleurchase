<?php
header("Content-Type: application/json");
require "db_connection.php";

$order_id = str_replace("ORD-", "", $_POST["order_id"] ?? "");
$status = $_POST["status"] ?? "";

if (!$order_id || !$status) {
    echo json_encode(["success" => false, "message" => "Missing order ID or status"]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE `order`
    SET status = ?
    WHERE order_id = ?
");
$stmt->bind_param("si", $status, $order_id);
$stmt->execute();

if ($stmt->affected_rows >= 0) {
    $userStmt = $conn->prepare("
        SELECT user_id
        FROM `order`
        WHERE order_id = ?
    ");
    $userStmt->bind_param("i", $order_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult && $userResult->num_rows > 0) {
        $order = $userResult->fetch_assoc();
        $user_id = $order["user_id"];

        $title = "Order Status Updated";
        $body = "Your order ORD-$order_id is now $status.";

        $notifStmt = $conn->prepare("
            INSERT INTO notification (user_id, type, title, body, is_read, created_at)
            VALUES (?, 'order', ?, ?, 0, NOW())
        ");
        $notifStmt->bind_param("iss", $user_id, $title, $body);
        $notifStmt->execute();
    }

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Status update failed"]);
}
?>