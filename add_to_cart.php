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

$quantity   = max(1, isset($data['quantity']) ? intval($data['quantity']) : 1);
$bouquet_id = isset($data['bouquet_id']) ? intval($data['bouquet_id']) : 0;
$product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
$item_type  = strtolower(trim($data['item_type'] ?? ''));

$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if ($current_user_id <= 0 && isset($data['user_id'])) {
    $current_user_id = intval($data['user_id']);
}

if ($current_user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Please sign in before adding items to your cart']);
    exit;
}

$user_stmt = $conn->prepare("SELECT user_id FROM `user` WHERE user_id = ? LIMIT 1");
$user_stmt->bind_param("i", $current_user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if (!$user_result || $user_result->num_rows === 0) {
    $user_stmt->close();
    echo json_encode(['success' => false, 'error' => 'Logged-in user was not found. Please sign in again.']);
    exit;
}

$user_stmt->close();

$_SESSION['user_id'] = $current_user_id;

if ($is_custom === 1 && $unit_price <= 0 && isset($data['custom_data']['total'])) {
    $unit_price = floatval($data['custom_data']['total']);
}

$is_product_item = $product_id > 0 && ($item_type === 'flower' || $item_type === 'product' || $bouquet_id <= 0);

if ($is_custom !== 1) {
    if ($is_product_item) {
        $product_stmt = $conn->prepare("
            SELECT product_id, price, stock, status
            FROM product
            WHERE product_id = ?
            LIMIT 1
        ");
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();

        if (!$product_result || $product_result->num_rows === 0) {
            $product_stmt->close();
            echo json_encode(['success' => false, 'error' => 'Flower product was not found']);
            exit;
        }

        $product = $product_result->fetch_assoc();
        $product_stmt->close();

        if (intval($product['stock']) < $quantity) {
            echo json_encode(['success' => false, 'error' => 'Not enough flower stock']);
            exit;
        }

        if (strtolower((string)$product['status']) === 'inactive') {
            echo json_encode(['success' => false, 'error' => 'This flower is unavailable']);
            exit;
        }

        if ($unit_price <= 0) {
            $unit_price = floatval($product['price']);
        }

        $bouquet_id = 0;
    } else {
        if ($bouquet_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Missing bouquet or flower id']);
            exit;
        }

        $bouquet_stmt = $conn->prepare("
            SELECT bouquet_id, price, stock, status
            FROM bouquet
            WHERE bouquet_id = ?
            LIMIT 1
        ");
        $bouquet_stmt->bind_param("i", $bouquet_id);
        $bouquet_stmt->execute();
        $bouquet_result = $bouquet_stmt->get_result();

        if (!$bouquet_result || $bouquet_result->num_rows === 0) {
            $bouquet_stmt->close();
            echo json_encode(['success' => false, 'error' => 'Bouquet was not found']);
            exit;
        }

        $bouquet = $bouquet_result->fetch_assoc();
        $bouquet_stmt->close();

        if (intval($bouquet['stock']) < $quantity) {
            echo json_encode(['success' => false, 'error' => 'Not enough bouquet stock']);
            exit;
        }

        if (strtolower((string)$bouquet['status']) === 'inactive') {
            echo json_encode(['success' => false, 'error' => 'This bouquet is unavailable']);
            exit;
        }

        if ($unit_price <= 0) {
            $unit_price = floatval($bouquet['price']);
        }

        $product_id = 0;
    }
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

    $existing_cart_item_id = 0;

    if ($is_custom !== 1 && $is_product_item) {
        $existing = $conn->prepare("
            SELECT cart_item_id
            FROM cart_item
            WHERE cart_id = ? AND product_id = ? AND is_custom = 0
            LIMIT 1
        ");
        $existing->bind_param("ii", $cart_id, $product_id);
        $existing->execute();
        $existing_result = $existing->get_result();

        if ($existing_result && $existing_result->num_rows > 0) {
            $existing_cart_item_id = intval($existing_result->fetch_assoc()['cart_item_id']);
        }

        $existing->close();
    } elseif ($is_custom !== 1) {
        $existing = $conn->prepare("
            SELECT cart_item_id
            FROM cart_item
            WHERE cart_id = ? AND bouquet_id = ? AND is_custom = 0
            LIMIT 1
        ");
        $existing->bind_param("ii", $cart_id, $bouquet_id);
        $existing->execute();
        $existing_result = $existing->get_result();

        if ($existing_result && $existing_result->num_rows > 0) {
            $existing_cart_item_id = intval($existing_result->fetch_assoc()['cart_item_id']);
        }

        $existing->close();
    }

    if ($existing_cart_item_id > 0) {
        $update = $conn->prepare("
            UPDATE cart_item
            SET quantity = quantity + ?, unit_price = ?
            WHERE cart_item_id = ?
        ");
        $update->bind_param("idi", $quantity, $unit_price, $existing_cart_item_id);
        $success = $update->execute();
        $update->close();
    } elseif ($is_custom === 1) {
        $insert = $conn->prepare("
            INSERT INTO cart_item 
            (cart_id, bouquet_id, quantity, unit_price, is_custom, custom_data, product_id) 
            VALUES (?, NULL, ?, ?, 1, ?, NULL)
        ");

        $insert->bind_param(
            "iids",
            $cart_id,
            $quantity,
            $unit_price,
            $custom_data
        );
        $success = $insert->execute();
        $insert->close();
    } elseif ($is_product_item) {
        $insert = $conn->prepare("
            INSERT INTO cart_item 
            (cart_id, bouquet_id, quantity, unit_price, is_custom, product_id) 
            VALUES (?, NULL, ?, ?, 0, ?)
        ");

        $insert->bind_param(
            "iidi",
            $cart_id,
            $quantity,
            $unit_price,
            $product_id
        );
        $success = $insert->execute();
        $insert->close();
    } else {
        $insert = $conn->prepare("
            INSERT INTO cart_item 
            (cart_id, bouquet_id, quantity, unit_price, is_custom, product_id) 
            VALUES (?, ?, ?, ?, 0, NULL)
        ");

        $insert->bind_param(
            "iiid",
            $cart_id,
            $bouquet_id,
            $quantity,
            $unit_price
        );
        $success = $insert->execute();
        $insert->close();
    }

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

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
