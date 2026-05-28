<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db_connection.php';

$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

$cart_count = 0;

$stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $cart_id = intval($result->fetch_assoc()['cart_id']);

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

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'cart_count' => $cart_count
]);
?>
