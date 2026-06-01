<?php
header("Content-Type: application/json");
require "db_connection.php";

$order_id = str_replace("ORD-", "", $_POST["order_id"] ?? "");

if (!$order_id) {
    echo json_encode([
        "success" => false,
        "message" => "Missing order ID"
    ]);
    exit;
}

$conn->begin_transaction();

try {

    // Check if already cancelled
    $check = $conn->prepare("
        SELECT status 
        FROM `order`
        WHERE order_id = ?
    ");

    $check->bind_param("i", $order_id);
    $check->execute();

    $order = $check->get_result()->fetch_assoc();

    if (!$order) {
        throw new Exception("Order not found");
    }

    if ($order["status"] === "Cancelled") {
        throw new Exception("Order already cancelled");
    }

    // Restore bouquet stock
    $items = $conn->prepare("
        SELECT bouquet_id, quantity
        FROM order_item
        WHERE order_id = ?
    ");

    $items->bind_param("i", $order_id);
    $items->execute();

    $result = $items->get_result();

    while ($row = $result->fetch_assoc()) {

        $bouquet_id = (int)$row["bouquet_id"];
        $qty = (int)$row["quantity"];

        $restore = $conn->prepare("
            UPDATE bouquet
            SET stock = stock + ?
            WHERE bouquet_id = ?
        ");

        $restore->bind_param("ii", $qty, $bouquet_id);
        $restore->execute();
    }

    // Cancel order
    $cancel = $conn->prepare("
        UPDATE `order`
        SET status = 'Cancelled'
        WHERE order_id = ?
    ");

    $cancel->bind_param("i", $order_id);
    $cancel->execute();

    // Reject payment
    $payment = $conn->prepare("
        UPDATE payment
        SET status = 'rejected'
        WHERE order_id = ?
    ");

    $payment->bind_param("i", $order_id);
    $payment->execute();

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Order rejected and stock restored"
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

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

        $title = "Order Status Rejected";
        $body = "Your order ORD-$order_id is now Cancelled. Please make a valid payment to place the order again.";

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