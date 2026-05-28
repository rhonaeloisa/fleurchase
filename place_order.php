<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connection.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $user_email = $_POST['user_email'] ?? '';
    $order_name = $_POST['order_name'] ?? '';
    $discount_amount = floatval($_POST['discount_amount'] ?? 0);
    $shipping_fee = floatval($_POST['shipping_fee'] ?? 0);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $notes = $_POST['notes'] ?? '';
    $delivery_date = $_POST['delivery_date'] ?? null;
    $delivery_type = $_POST['delivery_type'] ?? 'Delivery';
    $address_id = intval($_POST['address_id'] ?? 0);
    $items = json_decode($_POST['items'] ?? '[]', true);

    // Read incoming master promo primary key ID from checkout form data payload
    $promo_id = isset($_POST['promo_id']) && $_POST['promo_id'] !== '' ? intval($_POST['promo_id']) : null;

    // Intercept string dropdown slots and convert to strict standard database TIME formats (HH:MM:SS)
    $raw_time = $_POST['delivery_time'] ?? '';
    $delivery_time = '12:00:00'; // Baseline defensive placeholder fallback

    if (!empty($raw_time)) {
        $time_parts = explode('–', $raw_time);
        $start_time_string = trim($time_parts[0]);
        
        $parsed_timestamp = strtotime($start_time_string);
        if ($parsed_timestamp !== false) {
            $delivery_time = date('H:i:s', $parsed_timestamp);
        }
    }

    if (!$user_email || !$order_name || $total_amount <= 0 || empty($items)) {
        throw new Exception('Missing critical order POST parameters.');
    }

    $conn->begin_transaction();

    // 1. LOOK UP SYSTEM USER_ID USING ACTIVE ACCOUNT EMAIL ADDRESS
    $user_stmt = $conn->prepare("SELECT user_id FROM user WHERE user_email = ? LIMIT 1");
    if (!$user_stmt) {
        throw new Exception($conn->error);
    }
    $user_stmt->bind_param("s", $user_email);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result();

    if (!$user_res || $user_res->num_rows === 0) {
        throw new Exception('User profile not found matching account email context.');
    }

    $user_id = intval($user_res->fetch_assoc()['user_id']);
    $user_stmt->close();

    // 2. TRACKING INTELLIGENCE: VERIFY & INSERT INTO THE USER_PROMO TABLE
    $user_promo_id = null; // Stays null unless a valid promo is applied

    if ($promo_id !== null && $promo_id > 0) {
        // Query to check if this user has already used this specific coupon code before
        $check_promo = $conn->prepare("SELECT user_promo_id FROM user_promo WHERE user_id = ? AND promo_id = ? LIMIT 1");
        $check_promo->bind_param("ii", $user_id, $promo_id);
        $check_promo->execute();
        $check_res = $check_promo->get_result();
        
        if ($check_res && $check_res->num_rows > 0) {
            throw new Exception('✕ You have already used this promotional coupon code on a previous order.');
        }
        $check_promo->close();

        // Safe insertion into your user_promo table structure mapping fields
        $insert_up = $conn->prepare("INSERT INTO user_promo (promo_id, user_id, status, usage_count) VALUES (?, ?, 'used', 1)");
        $insert_up->bind_param("ii", $promo_id, $user_id);
        $insert_up->execute();
        
        // Dynamic assignment fetches newly assigned row primary key index instantly
        $user_promo_id = intval($conn->insert_id);
        $insert_up->close();
    }

    // 3. INSERT MAIN RECORD ROW INTO PARENT ORDER TABLE
    $order_stmt = $conn->prepare("
        INSERT INTO `order`
        (user_id, promo_id, user_promo_id, order_name, order_date, discount_amount, shipping_fee, total_amount, status, notes, delivery_date, delivery_type, delivery_time)
        VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$order_stmt) {
        throw new Exception($conn->error);
    }

    $order_status = 'Pending';
    
    // Exactly 12 dynamic positional parameter arguments matching query structure slots perfectly
    $order_stmt->bind_param(
        "iiisdddsssss",
        $user_id,
        $promo_id,
        $user_promo_id,
        $order_name,
        $discount_amount,
        $shipping_fee,
        $total_amount,
        $order_status,
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

    // 4. INSERT INDIVIDUAL bouquet ITEM LINE RELATION LINES
    foreach ($items as $item) {
        $quantity = intval($item['qty'] ?? 1);
        $unit_price = floatval($item['price'] ?? 0);
        $subtotal = $quantity * $unit_price;
        $snapshot_name = $item['name'] ?? 'Item';

        $item_type = strtolower(trim($item['item_type'] ?? ''));
        $product_id = !empty($item['product_id']) ? intval($item['product_id']) : 0;
        $bouquet_id = !empty($item['bouquet_id']) ? intval($item['bouquet_id']) : 0;

        if (($item_type === 'flower' || $item_type === 'product') && $product_id > 0) {
            $item_stmt = $conn->prepare("
                INSERT INTO order_item
                (order_id, bouquet_id, product_id, quantity, unit_price, subtotal, snapshot_name)
                VALUES (?, NULL, ?, ?, ?, ?, ?)
            ");
            if (!$item_stmt) throw new Exception($conn->error);
            $item_stmt->bind_param("iiidds", $order_id, $product_id, $quantity, $unit_price, $subtotal, $snapshot_name);
        } elseif ($bouquet_id <= 0) {
            $item_stmt = $conn->prepare("
                INSERT INTO order_item
                (order_id, bouquet_id, product_id, quantity, unit_price, subtotal, snapshot_name)
                VALUES (?, NULL, NULL, ?, ?, ?, ?)
            ");
            if (!$item_stmt) throw new Exception($conn->error);
            $item_stmt->bind_param("iidds", $order_id, $quantity, $unit_price, $subtotal, $snapshot_name);
        } else {
            $item_stmt = $conn->prepare("
                INSERT INTO order_item
                (order_id, bouquet_id, quantity, unit_price, subtotal, snapshot_name)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if (!$item_stmt) throw new Exception($conn->error);
            $item_stmt->bind_param("iiidds", $order_id, $bouquet_id, $quantity, $unit_price, $subtotal, $snapshot_name);
        }

        if (!$item_stmt->execute()) {
            throw new Exception($item_stmt->error);
        }
        $item_stmt->close();
    }

    // 5. PARSE DATA INPUT PAYLOADS AND SAVE TRANSACTION PAYMENTS
    $payment_amount = $total_amount;
    $payment_type = $_POST['payment_type'] ?? 'GCash';
    $reference_number = $_POST['reference_number'] ?? '';
    $payment_status = 'uploaded';
    $img_receipt = $_POST['img_receipt'] ?? '';
    $receipt_base64 = $_POST['receipt_base64'] ?? '';

    if ($receipt_base64 && $img_receipt) {
        $uploadDir = __DIR__ . '/uploads/receipts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($img_receipt));
        $targetPath = $uploadDir . $safeName;

        if (preg_match('/^data:image\/\w+;base64,/', $receipt_base64)) {
            $receipt_base64 = preg_replace('/^data:image\/\w+;base64,/', '', $receipt_base64);
        }

        $imageData = base64_decode($receipt_base64);
        if ($imageData !== false) {
            file_put_contents($targetPath, $imageData);
            $img_receipt = $safeName;
        }
    }

    $pay_stmt = $conn->prepare("
        INSERT INTO payment
        (order_id, amount, payment_date, payment_type, reference_number, img_receipt, status)
        VALUES (?, ?, NOW(), ?, ?, ?, ?)
    ");
    if (!$pay_stmt) throw new Exception($conn->error);
    $pay_stmt->bind_param("idssss", $order_id, $payment_amount, $payment_type, $reference_number, $img_receipt, $payment_status);

    if (!$pay_stmt->execute()) {
        throw new Exception($pay_stmt->error);
    }
    $pay_stmt->close();

    // 6. INITIALIZE AND LINK SHIPMENT ROW ENTRIES
    if ($address_id > 0) {
        $ship_status = 'Pending';
        $fee = $shipping_fee;

        $ship_stmt = $conn->prepare("
            INSERT INTO shipment (address_id, order_id, status, fee)
            VALUES (?, ?, ?, ?)
        ");
        if (!$ship_stmt) throw new Exception($conn->error);
        $ship_stmt->bind_param("iisd", $address_id, $order_id, $ship_status, $fee);

        if (!$ship_stmt->execute()) {
            throw new Exception($ship_stmt->error);
        }
        $ship_stmt->close();
    }

    // 7. NOTIFY ADMIN ABOUT NEW ORDER
    $admin_id = 1;

    $name_stmt = $conn->prepare("
        SELECT first_name, last_name
        FROM `user`
        WHERE user_id = ?
        LIMIT 1
    ");
    $name_stmt->bind_param("i", $user_id);
    $name_stmt->execute();

    $name_result = $name_stmt->get_result();
    $name_row = $name_result->fetch_assoc();

    $customer_name = trim(($name_row["first_name"] ?? "") . " " . ($name_row["last_name"] ?? ""));
    if ($customer_name === "") {
        $customer_name = "A customer";
    }

    $name_stmt->close();

    $title = "New Order Received";
    $body = "$customer_name placed order ORD-$order_id.";

    $notif_stmt = $conn->prepare("
        INSERT INTO notification (user_id, type, title, body, is_read, created_at)
        VALUES (?, 'order', ?, ?, 0, NOW())
    ");

    $notif_stmt->bind_param("iss", $admin_id, $title, $body);
    $notif_stmt->execute();
    $notif_stmt->close();
    // Safely commit all operational data across all target relational tables together
    $conn->commit();

    // Clear output buffer streams before triggering the JSON callback response thread
    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode([
        'success' => false,
        'message' => 'SQL error: ' . $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
exit;
?>
