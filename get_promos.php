<?php
header("Content-Type: application/json");
require "db_connection.php";

try {
    $sql = "SELECT 
                promo_id,
                code,
                promo_name,
                description,
                discount_type,
                discount_value,
                start_date,
                end_date,
                min_order_amount,
                status,
                usage_limit_per_user
            FROM promos
            ORDER BY promo_id DESC";

    $result = $conn->query($sql);
    $promos = [];

    while ($row = $result->fetch_assoc()) {
        $promos[] = [
            "id" => $row["promo_id"],
            "code" => $row["code"],
            "name" => $row["promo_name"],
            "desc" => $row["description"],
            "type" => $row["discount_type"],
            "value" => (float)$row["discount_value"],
            "startDate" => $row["start_date"],
            "endDate" => $row["end_date"],
            "minOrder" => (float)$row["min_order_amount"],
            "status" => strtolower($row["status"]),
            "usageLimit" => (int)$row["usage_limit_per_user"],
            "category" => "all",
            "products" => []
        ];
    }

    echo json_encode([
        "success" => true,
        "promos" => $promos
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
