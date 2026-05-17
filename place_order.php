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
    $addressId = (int)($_POST["address_id"] ?? 0);

    if ($email === "" || $orderName === "" || $deliveryDate === "" || !$deliveryTime || $addressId <= 0) {
        echo json_encode(["success" => false, "message" => "Missing order or delivery address details"]);
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

    $addrStmt = $conn->prepare("SELECT address_id FROM address WHERE address_id = ? AND user_id = ? LIMIT 1");
    $addrStmt->bind_param("ii", $addressId, $userId);
    $addrStmt->execute();
    $address = $addrStmt->get_result()->fetch_assoc();

    if (!$address) {
        echo json_encode(["success" => false, "message" => "Selected address not found"]);
        exit;
    }

    $promoId = null;
    $userPromoId = null;
    $status = "Pending";

    $conn->begin_transaction();

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
    $orderId = $conn->insert_id;

    $shipmentStatus = "Pending";
    $rateId = null;

    $shipSql = "INSERT INTO shipment
        (address_id, order_id, rate_id, status, fee)
        VALUES (?, ?, ?, ?, ?)";

    $shipStmt = $conn->prepare($shipSql);
    $shipStmt->bind_param(
        "iiisd",
        $addressId,
        $orderId,
        $rateId,
        $shipmentStatus,
        $shipping
    );
    $shipStmt->execute();

    $paymentSql = "INSERT INTO payment(
        payment_id, order_id, amount, payment_date, payment_type, reference_number, img_recceipt, status)
        VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?)";

    $paymentStmt = $conn->prepare($paymentSql);
    $paymentStmt->bind_param(
        "iisssss",
        $paymentId,
        $orderId,
        $total,
        $paymentType,
        $referenceNumber,
        $imgReceipt,
        $paymentStatus
    );
    

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Order saved",
        "order_id" => $orderId
    ]);
} catch (Throwable $e) {
    if (isset($conn)) {
        $conn->rollback();
    }

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
