<?php
header("Content-Type: application/json");
require "db_connection.php";

try {
    $email = trim($_POST["email"] ?? "");
    $orderItemId = (int)($_POST["order_item_id"] ?? 0);
    $rating = (int)($_POST["rating"] ?? 0);
    $text = trim($_POST["review_text"] ?? "");

    if ($email === "" || $orderItemId <= 0 || $rating < 1 || $rating > 5 || $text === "") {
        echo json_encode(["success" => false, "message" => "Invalid review details"]);
        exit;
    }

    $userStmt = $conn->prepare("SELECT user_id FROM `user` WHERE user_email = ? LIMIT 1");
    $userStmt->bind_param("s", $email);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }

    $userId = (int)$user["user_id"];

    $sql = "INSERT INTO reviews (user_id, order_item_id, rating, review_text)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $userId, $orderItemId, $rating, $text);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Review submitted"]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
