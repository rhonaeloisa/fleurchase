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
?>