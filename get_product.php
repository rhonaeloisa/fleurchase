<?php
header("Content-Type: application/json");
require "db_connection.php";

try {
    // ==========================================
    // GET ALL ACTIVE PRODUCTS
    // ==========================================
    $sql = "SELECT
                product_id,
                product_name,
                price,
                status,
                product_type,
                product_image,
                stock,
                date_arrived,
                best_before_date
            FROM product
            WHERE status = 'Active' OR status = 'active' OR status IS NULL
            ORDER BY product_name ASC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $products = [];

    while ($row = $result->fetch_assoc()) {
        // Use product_image column
        $image = $row["product_image"] ?? "";

        if (
            $image &&
            strpos($image, "images/") !== 0 &&
            strpos($image, "uploads/") !== 0 &&
            strpos($image, "http") !== 0
        ) {
            $image = "images/" . $image;
        }

        $products[] = [
            "id" => (int)$row["product_id"],
            "name" => $row["product_name"],
            "price" => (float)$row["price"],
            "status" => $row["status"],
            "img" => $image,
            "type" => strtolower($row["product_type"] ?? "flower"),
            "stock" => (int)($row["stock"] ?? 0),
            "date_arrived" => $row["date_arrived"],
            "best_before_date" => $row["best_before_date"]
        ];
    }

    echo json_encode([
        "success" => true,
        "products" => $products
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>