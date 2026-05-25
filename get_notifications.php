<?php
header("Content-Type: application/json");
require "db_connection.php";

$user_id = $_GET["user_id"] ?? "";
$email = $_GET["email"] ?? "";

if (!$user_id && !$email) {
    echo json_encode(["success" => false, "message" => "Missing user"]);
    exit;
}

if (!$user_id && $email) {
    $stmtUser = $conn->prepare("
        SELECT user_id
        FROM `user`
        WHERE user_email = ?
        LIMIT 1
    ");
    $stmtUser->bind_param("s", $email);
    $stmtUser->execute();
    $userResult = $stmtUser->get_result();

    if ($userResult->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }

    $user = $userResult->fetch_assoc();
    $user_id = $user["user_id"];
}

$stmt = $conn->prepare("
    SELECT notification_id, type, title, body, is_read, created_at
    FROM notification
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$notifications = [];

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode([
    "success" => true,
    "notifications" => $notifications
]);
?>