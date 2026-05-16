<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once 'db_connection.php'; // Your main database connection script

// Read raw POST JSON data transmitted from JavaScript fetch requests
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (is_string($data)) {
    $data = json_decode($data, true);
}

$action = isset($data['action']) ? trim($data['action']) : '';
$response = ['success' => false];

// Read active user verification context safely from active sessions
$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 14;

switch ($action) {
    
    // ── 1. HANDLE INCREMENT / DECREMENT (+ AND -) BUTTONS ──
    case 'change_qty':
        $cart_item_id = isset($data['cart_item_id']) ? intval($data['cart_item_id']) : 0;
        $delta = isset($data['delta']) ? intval($data['delta']) : 0;
        
        if ($cart_item_id > 0 && ($delta === 1 || $delta === -1)) {
            // Get the current quantity first to make sure we don't go below 1
            $qty_stmt = $conn->prepare("SELECT quantity FROM cart_item WHERE cart_item_id = ?");
            $qty_stmt->bind_param("i", $cart_item_id);
            $qty_stmt->execute();
            $qty_res = $qty_stmt->get_result()->fetch_assoc();
            $qty_stmt->close();
            
            if ($qty_res) {
                $new_qty = intval($qty_res['quantity']) + $delta;
                
                if ($new_qty >= 1) {
                    $update_stmt = $conn->prepare("UPDATE cart_item SET quantity = ? WHERE cart_item_id = ?");
                    $update_stmt->bind_param("ii", $new_qty, $cart_item_id);
                    if ($update_stmt->execute()) {
                        $response['success'] = true;
                    }
                    $update_stmt->close();
                } else {
                    $response['error'] = 'Quantity cannot be less than 1';
                }
            }
        }
        break;

    // ── 2. HANDLE SINGLE ITEM DISCARD BUTTON (✕) ──
    case 'remove_item':
        $cart_item_id = isset($data['cart_item_id']) ? intval($data['cart_item_id']) : 0;
        
        if ($cart_item_id > 0) {
            $delete_stmt = $conn->prepare("DELETE FROM cart_item WHERE cart_item_id = ?");
            $delete_stmt->bind_param("i", $cart_item_id);
            if ($delete_stmt->execute()) {
                $response['success'] = true;
            }
            $delete_stmt->close();
        }
        break;

    // ── 3. HANDLE PURGE ENTIRE CART FLUSH BUTTON (Clear All) ──
    case 'clear_all':
        $cart_id = isset($data['cart_id']) ? intval($data['cart_id']) : 0;
        
        if ($cart_id > 0) {
            $clear_stmt = $conn->prepare("DELETE FROM cart_item WHERE cart_id = ?");
            $clear_stmt->bind_param("i", $cart_id);
            if ($clear_stmt->execute()) {
                $response['success'] = true;
            }
            $clear_stmt->close();
        }
        break;
        
    default:
        $response['error'] = 'Unknown action route context requested.';
        break;
}

$conn->close();
echo json_encode($response);
exit;
?>