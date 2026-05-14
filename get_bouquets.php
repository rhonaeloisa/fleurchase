<?php
header("Content-Type: application/json");

require "db_connection.php";

try {
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
                category
            FROM bouquet
            WHERE status = 'Available' OR status = 'active' OR status IS NULL
            ORDER BY bouquet_id DESC";

    $result = $conn->query($sql);

    $bouquets = [];

    while ($row = $result->fetch_assoc()) {
    $image = $row["image"];

    if ($image && !str_starts_with($image, "images/") && !str_starts_with($image, "uploads/") && !str_starts_with($image, "http")) {
        $image = "images/" . $image;
    }

    $bouquets[] = [
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
        "stock" => 99,
        "badge" => $row["is_custom"] ? "Custom" : "Ready"
    ];
}

    echo json_encode([
        "success" => true,
        "products" => $bouquets
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
