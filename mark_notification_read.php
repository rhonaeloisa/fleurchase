<?php
header("Content-Type: application/json");
require "db_connection.php";

$notification_id = intval($_POST["notification_id"] ?? 0);

if (!$notification_id) {
    echo json_encode(["success" => false, "message" => "Missing notification ID"]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE notification
    SET is_read = 1
    WHERE notification_id = ?
");
$stmt->bind_param("i", $notification_id);
$stmt->execute();

echo json_encode(["success" => true]);
?>