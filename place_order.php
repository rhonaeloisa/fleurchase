<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connection.php';

try {
    $user_email = $_POST['user_email'] ?? '';
    $order_name = $_POST['order_name'] ?? '';
    $discount_amount = floatval($_POST['discount_amount'] ?? 0);
    $shipping_fee = floatval($_POST['shipping_fee'] ?? 0);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $notes = $_POST['notes'] ?? '';
    $delivery_date = $_POST['delivery_date'] ?? null;
    $delivery_type = $_POST['delivery_type'] ?? 'Delivery';
    $delivery_time = $_POST['delivery_time'] ?? '';
    $address_id = intval($_POST['address_id'] ?? 0);
    $items = json_decode($_POST['items'] ?? '[]', true);

    if (!$user_email || !$order_name || $total_amount <= 0 || empty($items)) {
        throw new Exception('Missing order details.');
    }

    $conn->begin_transaction();

    $user_stmt = $conn->prepare("SELECT user_id FROM user WHERE user_email = ? LIMIT 1");
    if (!$user_stmt) {
        throw new Exception($conn->error);
    }

    $user_stmt->bind_param("s", $user_email);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result();

    if (!$user_res || $user_res->num_rows === 0) {
        throw new Exception('User not found.');
    }

    $user_id = intval($user_res->fetch_assoc()['user_id']);
    $user_stmt->close();

    $order_stmt = $conn->prepare("
        INSERT INTO `order`
        (user_id, order_name, order_date, discount_amount, shipping_fee, total_amount, status, notes, delivery_date, delivery_type, delivery_time)
        VALUES (?, ?, NOW(), ?, ?, ?, 'Pending', ?, ?, ?, ?)
    ");

    if (!$order_stmt) {
        throw new Exception($conn->error);
    }

    $order_stmt->bind_param(
        "isdddssss",
        $user_id,
        $order_name,
        $discount_amount,
        $shipping_fee,
        $total_amount,
        $notes,
        $delivery_date,
        $delivery_type,
        $delivery_time
    );

    if (!$order_stmt->execute()) {
        throw new Exception($order_stmt->error);
    }

    $order_id = intval($conn->insert_id);
    $order_stmt->close();

    foreach ($items as $item) {
        $quantity = intval($item['qty'] ?? 1);
        $unit_price = floatval($item['price'] ?? 0);
        $subtotal = $quantity * $unit_price;
        $snapshot_name = $item['name'] ?? 'Item';

        $bouquet_id = null;
        if (!empty($item['bouquet_id'])) {
            $bouquet_id = intval($item['bouquet_id']);
        } elseif (!empty($item['productId']) && is_numeric($item['productId'])) {
            $bouquet_id = intval($item['productId']);
        }

        if ($bouquet_id === null) {
            $item_stmt = $conn->prepare("
                INSERT INTO order_item
                (order_id, bouquet_id, quantity, unit_price, subtotal, snapshot_name)
                VALUES (?, NULL, ?, ?, ?, ?)
            ");

            if (!$item_stmt) {
                throw new Exception($conn->error);
            }

            $item_stmt->bind_param(
                "iidds",
                $order_id,
                $quantity,
                $unit_price,
                $subtotal,
                $snapshot_name
            );
        } else {
            $item_stmt = $conn->prepare("
                INSERT INTO order_item
                (order_id, bouquet_id, quantity, unit_price, subtotal, snapshot_name)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if (!$item_stmt) {
                throw new Exception($conn->error);
            }

            $item_stmt->bind_param(
                "iiidds",
                $order_id,
                $bouquet_id,
                $quantity,
                $unit_price,
                $subtotal,
                $snapshot_name
            );
        }

        if (!$item_stmt->execute()) {
            throw new Exception($item_stmt->error);
        }

        $item_stmt->close();
    }

    $payment_amount = $total_amount;
    $payment_type = $_POST['payment_type'] ?? 'GCash';
    $reference_number = $_POST['reference_number'] ?? '';
    $payment_status = 'uploaded';
    $img_receipt = $_POST['img_receipt'] ?? '';
    $receipt_base64 = $_POST['receipt_base64'] ?? '';

    if ($receipt_base64 && $img_receipt) {
        $uploadDir = __DIR__ . '/uploads/receipts/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($img_receipt));
        $targetPath = $uploadDir . $safeName;

        if (preg_match('/^data:image\/\w+;base64,/', $receipt_base64)) {
            $receipt_base64 = preg_replace('/^data:image\/\w+;base64,/', '', $receipt_base64);
        }

        $imageData = base64_decode($receipt_base64);

        if ($imageData === false) {
            throw new Exception('Invalid receipt image upload.');
        }

        if (file_put_contents($targetPath, $imageData) === false) {
            throw new Exception('Unable to save receipt image.');
        }

        $img_receipt = $safeName;
    }

    $pay_stmt = $conn->prepare("
        INSERT INTO payment
        (order_id, amount, payment_date, payment_type, reference_number, img_receipt, status)
        VALUES (?, ?, NOW(), ?, ?, ?, ?)
    ");

    if (!$pay_stmt) {
        throw new Exception($conn->error);
    }

    $pay_stmt->bind_param(
        "idssss",
        $order_id,
        $payment_amount,
        $payment_type,
        $reference_number,
        $img_receipt,
        $payment_status
    );

    if (!$pay_stmt->execute()) {
        throw new Exception($pay_stmt->error);
    }

    $pay_stmt->close();

    if ($address_id > 0) {
        $ship_status = 'Pending';
        $fee = $shipping_fee;

        $ship_stmt = $conn->prepare("
            INSERT INTO shipment
            (address_id, order_id, rate_id, status, fee)
            VALUES (?, ?, NULL, ?, ?)
        ");

        if (!$ship_stmt) {
            throw new Exception($conn->error);
        }

        $ship_stmt->bind_param(
            "iisd",
            $address_id,
            $order_id,
            $ship_status,
            $fee
        );

        if (!$ship_stmt->execute()) {
            throw new Exception($ship_stmt->error);
        }

        $ship_stmt->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $order_id
    ]);
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }

    echo json_encode([
        'success' => false,
        'message' => 'SQL error: ' . $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>