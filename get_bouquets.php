<?php
header("Content-Type: application/json");

require "db_connection.php";

try {
    $products = [];

    // =========================
    // FETCH BOUQUETS
    // =========================
    $sql = "SELECT 
                b.bouquet_id,
                b.created_by_user_id,
                b.variation,
                b.name,
                b.description,
                b.price,
                b.is_custom,
                b.image,
                b.status,
                b.category, 
                b.stock,
                COALESCE(br.avg_rating, 0) AS avg_rating,
                COALESCE(br.review_count, 0) AS review_count
            FROM bouquet b
            LEFT JOIN (
                SELECT
                    oi.bouquet_id,
                    AVG(r.rating) AS avg_rating,
                    COUNT(r.review_id) AS review_count
                FROM order_item oi
                INNER JOIN reviews r ON oi.order_item_id = r.order_item_id
                WHERE oi.bouquet_id IS NOT NULL
                GROUP BY oi.bouquet_id
            ) br ON b.bouquet_id = br.bouquet_id
            WHERE b.status = 'Available' 
               OR b.status = 'Active' 
               OR b.status = 'active' 
               OR b.status IS NULL
            ORDER BY b.bouquet_id DESC";

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
            "rating" => round((float)$row["avg_rating"], 1),
            "reviews" => (int)$row["review_count"],
            "stock" => (int)$row["stock"],
            "badge" => $row["is_custom"] ? "Custom" : "Ready"
        ];
    }

    // =========================
    // FETCH INDIVIDUAL STEMS
    // from product table
    // =========================
    $stemSql = "SELECT
                    p.product_id,
                    p.product_name,
                    p.product_type,
                    p.product_image,
                    p.stock,
                    p.price,
                    p.status,
                    COALESCE(pr.avg_rating, 0) AS avg_rating,
                    COALESCE(pr.review_count, 0) AS review_count
                FROM product p
                LEFT JOIN (
                    SELECT
                        oi.product_id,
                        AVG(r.rating) AS avg_rating,
                        COUNT(r.review_id) AS review_count
                    FROM order_item oi
                    INNER JOIN reviews r ON oi.order_item_id = r.order_item_id
                    WHERE oi.product_id IS NOT NULL
                    GROUP BY oi.product_id
                ) pr ON p.product_id = pr.product_id
                WHERE p.product_type = 'flower'
                  AND p.status = 'Active'
                ORDER BY p.product_name ASC";

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
            "rating" => round((float)$row["avg_rating"], 1),
            "reviews" => (int)$row["review_count"],
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
