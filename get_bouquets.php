<?php
header("Content-Type: application/json");

require "db_connection.php";

try {
    $products = [];

    // =========================
    // FETCH BOUQUETS
    // =========================
    $sql = "SELECT 
                bouquet_id,
                created_by_user_id,
                variation,
                name,
                description,
                price,
                is_custom,
                image,
                status,
                category, 
                stock
            FROM bouquet
            WHERE status = 'Available' 
               OR status = 'Active' 
               OR status = 'active' 
               OR status IS NULL
            ORDER BY bouquet_id DESC";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $image = $row["image"];

        if (
            $image &&
            !str_starts_with($image, "images/") &&
            !str_starts_with($image, "uploads/") &&
            !str_starts_with($image, "http")
        ) {
            $image = "images/" . $image;
        }

        $products[] = [
            "id" => $row["bouquet_id"],
            "name" => $row["name"],
            "desc" => $row["description"],
            "price" => (float)$row["price"],
            "img" => $image,
            "category" => $row["category"],
            "variation" => $row["variation"],
            "status" => $row["status"],
            "type" => "bouquet",
            "rating" => 5,
            "reviews" => 0,
            "stock" => (int)$row["stock"],
            "badge" => $row["is_custom"] ? "Custom" : "Ready"
        ];
    }

    // =========================
    // FETCH INDIVIDUAL STEMS
    // from product table
    // =========================
    $stemSql = "SELECT
                    product_id,
                    product_name,
                    product_type,
                    product_image,
                    stock,
                    price,
                    status
                FROM product
                WHERE product_type = 'flower'
                  AND status = 'Active'
                ORDER BY product_name ASC";

    $stemResult = $conn->query($stemSql);

    while ($row = $stemResult->fetch_assoc()) {
        $image = $row["product_image"];

        if (
            $image &&
            !str_starts_with($image, "images/") &&
            !str_starts_with($image, "uploads/") &&
            !str_starts_with($image, "http")
        ) {
            $image = "images/" . $image;
        }

        $products[] = [
            "id" => "stem_" . $row["product_id"],
            "real_id" => $row["product_id"],
            "name" => $row["product_name"],
            "desc" => "Fresh individual flower stem",
            "price" => (float)$row["price"],
            "img" => $image,
            "category" => "individual",
            "variation" => "stem",
            "status" => $row["status"],
            "type" => "flower",
            "rating" => 5,
            "reviews" => 0,
            "stock" => (int)$row["stock"],
            "badge" => "Fresh"
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
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>