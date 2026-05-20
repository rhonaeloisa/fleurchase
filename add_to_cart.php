<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db_connection.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON received']);
    exit;
}

$unit_price  = isset($data['unit_price']) ? floatval($data['unit_price']) : 0;
$is_custom   = !empty($data['is_custom']) ? 1 : 0;
$custom_data = isset($data['custom_data']) ? json_encode($data['custom_data']) : null;

$quantity   = isset($data['quantity']) ? intval($data['quantity']) : 1;
$bouquet_id = isset($data['bouquet_id']) ? intval($data['bouquet_id']) : 0;

$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 14;

if ($is_custom === 1 && $unit_price <= 0 && isset($data['custom_data']['total'])) {
    $unit_price = floatval($data['custom_data']['total']);
}

if ($unit_price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid price']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $cart_id = intval($result->fetch_assoc()['cart_id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, created_at) VALUES (?, NOW())");
        $stmt->bind_param("i", $current_user_id);
        $stmt->execute();
        $cart_id = intval($conn->insert_id);
    }
    $stmt->close();

    if ($is_custom === 1) {
        $insert = $conn->prepare("
            INSERT INTO cart_item 
            (cart_id, bouquet_id, quantity, unit_price, is_custom, custom_data) 
            VALUES (?, NULL, ?, ?, 1, ?)
        ");

        $insert->bind_param(
            "iids",
            $cart_id,
            $quantity,
            $unit_price,
            $custom_data
        );
    } else {
        $insert = $conn->prepare("
            INSERT INTO cart_item 
            (cart_id, bouquet_id, quantity, unit_price, is_custom) 
            VALUES (?, ?, ?, ?, 0)
        ");

        $insert->bind_param(
            "iiid",
            $cart_id,
            $bouquet_id,
            $quantity,
            $unit_price
        );
    }

    $success = $insert->execute();
    $insert->close();

    $cart_count = 0;

    if ($success) {
        $count_stmt = $conn->prepare("
            SELECT COALESCE(SUM(quantity), 0) AS cart_count
            FROM cart_item
            WHERE cart_id = ?
        ");
        $count_stmt->bind_param("i", $cart_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();

        if ($count_result && $row = $count_result->fetch_assoc()) {
            $cart_count = intval($row['cart_count']);
        }

        $count_stmt->close();
    }

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Added successfully' : 'Failed to insert',
        'unit_price' => $unit_price,
        'cart_count' => $cart_count
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>