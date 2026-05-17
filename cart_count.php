<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once 'db_connection.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Resolve dynamic account identifier profile context or fall back safely to 14
    $current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 14;

    $total_count = 0;

    // STEP 1: Find the target user's active cart record container
    $cart_query = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? LIMIT 1");
    $cart_query->bind_param("i", $current_user_id);
    $cart_query->execute();
    $cart_res = $cart_query->get_result()->fetch_assoc();
    $cart_query->close();

    if ($cart_res) {
        $cart_id = intval($cart_res['cart_id']);

        // STEP 2: Calculate the mathematical SUM of all items inside the cart_item table lines
        $count_query = $conn->prepare("SELECT SUM(quantity) AS total_items FROM cart_item WHERE cart_id = ?");
        $count_query->bind_param("i", $cart_id);
        $count_query->execute();
        $count_res = $count_query->get_result()->fetch_assoc();
        $count_query->close();

        if ($count_res && $count_res['total_items'] !== null) {
            $total_count = intval($count_res['total_items']);
        }
    }

    echo json_encode([
        'success' => true,
        'cart_count' => $total_count
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'cart_count' => 0,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
exit;
?>