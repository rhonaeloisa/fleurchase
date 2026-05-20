<?php
header("Content-Type: application/json");
require "db_connection.php";

try {
    $promoId = (int)($_POST["promo_id"] ?? 0);

    if ($promoId <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid promo"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM promos WHERE promo_id = ?");
    $stmt->bind_param("i", $promoId);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Promo deleted"]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
