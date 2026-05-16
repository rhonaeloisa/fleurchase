<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once 'db_connection.php'; // Standard database connection footprint

$success = false;

// Get the raw POST JSON payload from JavaScript
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Handle potential double-encoded wrapper strings cleanly
if (is_string($data)) {
    $data = json_decode($data, true);
}

$bouquet_id = isset($data['bouquet_id']) ? intval($data['bouquet_id']) : 0;
$unit_price = isset($data['unit_price']) ? floatval($data['unit_price']) : 0.00;
$quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;

// Read the dynamic user context safely from session cookie flags
$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 14; 

if ($bouquet_id <= 0 || $unit_price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product or pricing parameters.']);
    exit;
}

// STEP 1: Automatically find or build a parent cart container matching the user
$cart_id = 0;
$cart_check = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? LIMIT 1");
$cart_check->bind_param("i", $current_user_id);
$cart_check->execute();
$cart_res = $cart_check->get_result();

if ($cart_res && $cart_res->num_rows > 0) {
    $cart_row = $cart_res->fetch_assoc();
    $cart_id = $cart_row['cart_id'];
} else {
    $cart_insert = $conn->prepare("INSERT INTO cart (user_id, created_at) VALUES (?, NOW())");
    $cart_insert->bind_param("i", $current_user_id);
    $cart_insert->execute();
    $cart_id = $conn->insert_id; 
    $cart_insert->close();
}
$cart_check->close();

// STEP 2: Handle individual item quantities additively inside cart_item
$check_stmt = $conn->prepare("SELECT cart_item_id, quantity FROM cart_item WHERE cart_id = ? AND bouquet_id = ?");
$check_stmt->bind_param("ii", $cart_id, $bouquet_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
$check_stmt->close();

if ($row) {
    $new_qty = $row['quantity'] + $quantity;
    $cart_item_id = $row['cart_item_id'];
    
    $update_stmt = $conn->prepare("UPDATE cart_item SET quantity = ? WHERE cart_item_id = ?");
    $update_stmt->bind_param("ii", $new_qty, $cart_item_id);
    $success = $update_stmt->execute();
    $update_stmt->close();
} else {
    $insert_stmt = $conn->prepare("INSERT INTO cart_item (cart_id, bouquet_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("iiid", $cart_id, $bouquet_id, $quantity, $unit_price);
    $success = $insert_stmt->execute();
    $insert_stmt->close();
}

$conn->close();

echo json_encode(['success' => $success]);
exit;
?>