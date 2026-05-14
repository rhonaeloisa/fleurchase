<?php
header("Content-Type: application/json");
require "db_connection.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $items = json_decode($_POST["items"] ?? "[]", true);

    if (!is_array($items) || count($items) === 0) {
        echo json_encode(["success" => false, "message" => "No checkout items received."]);
        exit;
    }

    $conn->begin_transaction();

    foreach ($items as $item) {
        $bouquetId = (int)($item["productId"] ?? 0);
        $qty = (int)($item["qty"] ?? 0);

        if ($bouquetId <= 0 || $qty <= 0) {
            throw new Exception("Invalid item in checkout.");
        }

        $stmt = $conn->prepare("SELECT stock FROM bouquet WHERE bouquet_id = ? FOR UPDATE");
        $stmt->bind_param("i", $bouquetId);
        $stmt->execute();

        $result = $stmt->get_result();
        $bouquet = $result->fetch_assoc();

        if (!$bouquet) {
            throw new Exception("Bouquet not found.");
        }

        if ((int)$bouquet["stock"] < $qty) {
            throw new Exception("Not enough stock for one or more bouquets.");
        }

        $update = $conn->prepare("UPDATE bouquet SET stock = stock - ? WHERE bouquet_id = ?");
        $update->bind_param("ii", $qty, $bouquetId);
        $update->execute();
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Stock updated."
    ]);
} catch (Throwable $e) {
    if (isset($conn)) {
        $conn->rollback();
    }

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
