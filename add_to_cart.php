<?php
// 1. INITIALIZE SESSION MANAGEMENT FIRST
session_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once 'db_connection.php'; // Relational source truth connection handle

$success = false;

// 2. FETCH AND SAFELY RESOLVE THE INCOMING PAYLOAD
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Clean up potential double-encoding safely
if (is_string($data)) {
    $data = json_decode($data, true);
}

$bouquet_id = isset($data['bouquet_id']) ? intval($data['bouquet_id']) : 0;
$unit_price = isset($data['unit_price']) ? floatval($data['unit_price']) : 0.00;
$quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;

// 3. READ THE LIVE ACCOUNT LOGGED INTO THE CURRENT SESSION
// Falls back to user_id 14 (Auq) if you are testing in a private browsing tab
$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 14; 

if ($bouquet_id <= 0 || $unit_price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product or pricing parameters.']);
    exit;
}

// STEP 1: AUTOMATICALLY LOOK UP OR GENERATE THE PARENT CART MASTER CONTAINER
$cart_id = 0;
$cart_check = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? LIMIT 1");
$cart_check->bind_param("i", $current_user_id);
$cart_check->execute();
$cart_res = $cart_check->get_result();

if ($cart_res && $cart_res->num_rows > 0) {
    // Container row exists for this user profile: Extract it cleanly
    $cart_row = $cart_res->fetch_assoc();
    $cart_id = $cart_row['cart_id'];
} else {
    // AUTOMATION: First time adding an item? Auto-build a custom master cart container instantly!
    $cart_insert = $conn->prepare("INSERT INTO cart (user_id, created_at) VALUES (?, NOW())");
    $cart_insert->bind_param("i", $current_user_id);
    $cart_insert->execute();
    
    $cart_id = $conn->insert_id; // Capture the dynamic auto-increment primary key safely
    $cart_insert->close();
}
$cart_check->close();

// STEP 2: LOOK UP OR WRITE RECORD SEGMENTS INSIDE CART_ITEM BRIDGE
$check_stmt = $conn->prepare("SELECT cart_item_id, quantity FROM cart_item WHERE cart_id = ? AND bouquet_id = ?");
$check_stmt->bind_param("ii", $cart_id, $bouquet_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
$check_stmt->close();

if ($row) {
    // Element matches: Increment current quantity value additively
    $new_qty = $row['quantity'] + $quantity;
    $cart_item_id = $row['cart_item_id'];
    
    $update_stmt = $conn->prepare("UPDATE cart_item SET quantity = ? WHERE cart_item_id = ?");
    $update_stmt->bind_param("ii", $new_qty, $cart_item_id);
    $success = $update_stmt->execute();
    $update_stmt->close();
} else {
    // Element does not match: Commit a clean, relational child instance entry row safely
    $insert_stmt = $conn->prepare("INSERT INTO cart_item (cart_id, bouquet_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("iiid", $cart_id, $bouquet_id, $quantity, $unit_price);
    $success = $insert_stmt->execute();
    $insert_stmt->close();
}

$conn->close();

echo json_encode(['success' => $success]);
?>