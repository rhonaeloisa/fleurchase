<?php
session_start();
header("Content-Type: application/json");

require "db_connection.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function toMysqlTime($slot) {
    if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $slot, $m)) {
        $hour = (int)$m[1];
        $min = (int)$m[2];
        $ampm = strtoupper($m[3]);

        if ($ampm === "PM" && $hour !== 12) $hour += 12;
        if ($ampm === "AM" && $hour === 12) $hour = 0;

        return sprintf("%02d:%02d:00", $hour, $min);
    }

    return null;
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo json_encode(["success" => false, "message" => "Invalid request method"]);
        exit;
    }

    $email = trim($_POST["user_email"] ?? "");
    $orderName = trim($_POST["order_name"] ?? "");
    $discount = (float)($_POST["discount_amount"] ?? 0);
    $shipping = (float)($_POST["shipping_fee"] ?? 0);
    $total = (float)($_POST["total_amount"] ?? 0);
    $notes = trim($_POST["notes"] ?? "");
    $deliveryDate = $_POST["delivery_date"] ?? "";
    $deliveryType = $_POST["delivery_type"] ?? "Delivery";
    $deliveryTime = toMysqlTime($_POST["delivery_time"] ?? "");

    if ($email === "" || $orderName === "" || $deliveryDate === "" || !$deliveryTime) {
        echo json_encode(["success" => false, "message" => "Missing order details"]);
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
    $promoId = null;
    $userPromoId = null;
    $status = "Pending";

    $sql = "INSERT INTO `order`
        (user_id, promo_id, user_promo_id, order_name, order_date, discount_amount, shipping_fee, total_amount, status, notes, delivery_date, delivery_type, delivery_time)
        VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiisdddsssss",
        $userId,
        $promoId,
        $userPromoId,
        $orderName,
        $discount,
        $shipping,
        $total,
        $status,
        $notes,
        $deliveryDate,
        $deliveryType,
        $deliveryTime
    );

    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Order saved",
        "order_id" => $conn->insert_id
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
