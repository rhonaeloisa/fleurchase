<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once 'db_connection.php'; 
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (is_string($data)) {
        $data = json_decode($data, true);
    }

    $bouquet_id = isset($data['bouquet_id']) ? intval($data['bouquet_id']) : 0;
    $unit_price = isset($data['unit_price']) ? floatval($data['unit_price']) : 0.00;
    $quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;

    // Resolve the active session profile or fall back safely to test user 14
    $current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 14; 

    if ($bouquet_id <= 0 || $unit_price <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid product or pricing parameters.']);
        exit;
    }

    // STEP 1: Find or initialize the parent cart record safely
    $cart_id = 0;
    $cart_check = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? LIMIT 1");
    $cart_check->bind_param("i", $current_user_id);
    $cart_check->execute();
    $cart_res = $cart_check->get_result();

    if ($cart_res && $cart_res->num_rows > 0) {
        $cart_row = $cart_res->fetch_assoc();
        $cart_id = $cart_row['cart_id'];
    } else {
        // FIXED: Safe insertion fallback without guessing timestamp column labels
        $cart_insert = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $cart_insert->bind_param("i", $current_user_id);
        $cart_insert->execute();
        $cart_id = $conn->insert_id; 
        $cart_insert->close();
    }
    $cart_check->close();

    // STEP 2: Handle individual individual quantities additively
    $check_stmt = $conn->prepare("SELECT cart_item_id, quantity FROM cart_item WHERE cart_id = ? AND bouquet_id = ?");
    $check_stmt->bind_param("ii", $cart_id, $bouquet_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    $check_stmt->close();

    if ($row) {
        $new_qty = $row['quantity'] + $quantity;
        $cart_item_id = $row['cart_item_id'];
        
        // FIXED: Explicitly syncs both new quantities and active unit prices cleanly
        $update_stmt = $conn->prepare("UPDATE cart_item SET quantity = ?, unit_price = ? WHERE cart_item_id = ?");
        $update_stmt->bind_param("idi", $new_qty, $unit_price, $cart_item_id);
        $success = $update_stmt->execute();
        $update_stmt->close();
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO cart_item (cart_id, bouquet_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("iiid", $cart_id, $bouquet_id, $quantity, $unit_price);
        $success = $insert_stmt->execute();
        $insert_stmt->close();
    }

    $conn->close();

    // FIXED: Wipes out any accidental echoes, whitespace, or hidden newlines before sending JSON
    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode(['success' => (bool)$success]);

} catch (Throwable $e) {
    // Catch database structure conflicts cleanly and return them as clear error text strings
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database SQL Exception: ' . $e->getMessage()
    ]);
}
exit;
?>