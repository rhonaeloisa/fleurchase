<?php
header("Content-Type: application/json");
require "db_connection.php";

try {
    $email = trim($_GET["email"] ?? "");

    if ($email === "") {
        echo json_encode(["success" => false, "message" => "Missing email"]);
        exit;
    }

  $sql = "
    SELECT 
        o.order_id,
        o.order_name,
        o.order_date,
        o.discount_amount,
        o.shipping_fee,
        o.total_amount,
        o.status,
        o.notes,
        o.delivery_date,
        o.delivery_type,
        o.delivery_time,

        CONCAT(
            COALESCE(a.house_no, ''), 
            IF(a.house_no IS NOT NULL AND a.street IS NOT NULL, ', ', ''),
            COALESCE(a.street, ''),
            IF(a.street IS NOT NULL AND a.barangay IS NOT NULL, ', ', ''),
            COALESCE(a.barangay, ''),
            IF(a.barangay IS NOT NULL AND a.city IS NOT NULL, ', ', ''),
            COALESCE(a.city, ''),
            IF(a.city IS NOT NULL AND a.province IS NOT NULL, ', ', ''),
            COALESCE(a.province, '')
        ) AS full_address,

        oi.order_item_id,
        oi.snapshot_name,
        oi.quantity,
        oi.unit_price,
        
        r.rating,
        r.review_text,

        p.payment_type,
        p.status AS payment_status

    FROM `order` o
    INNER JOIN `user` u ON o.user_id = u.user_id
    LEFT JOIN shipment s ON o.order_id = s.order_id
    LEFT JOIN `address` a ON s.address_id = a.address_id
    LEFT JOIN payment p ON o.order_id = p.order_id
    LEFT JOIN order_item oi ON o.order_id = oi.order_id
    LEFT JOIN reviews r ON oi.order_item_id = r.order_item_id

    WHERE u.user_email = ?
    ORDER BY o.order_id DESC
";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $id = $row["order_id"];

        if (!isset($orders[$id])) {
            $orders[$id] = [
                "id" => $id,
                "items" => $row["order_name"],
                "placedAt" => $row["order_date"],
                "delivDate" => $row["delivery_date"],
                "delivTime" => $row["delivery_time"],
                "status" => $row["status"],
                "sub" => (float)$row["total_amount"],
                "discount" => (float)$row["discount_amount"],
                "shippingFee" => (float)$row["shipping_fee"],
                "total" => (float)$row["total_amount"],
                "notes" => $row["notes"],
                "payMethod" => $row["payment_type"] ?? "—",
                "payStatus" => strtolower($row["payment_status"] ?? "pending"),
                "deliveryType" => $row["delivery_type"],
                'order_item_id' => intval($row['order_item_id']),
                "full_address" => $row["full_address"] ?? 'No address',
                "itemDetails" => [],
                "review" => null
            ];
        }

        if ($row["order_item_id"]) {
            $orders[$id]["itemDetails"][] = [
                "orderItemId" => $row["order_item_id"],
                "name" => $row["snapshot_name"],
                "qty" => (int)$row["quantity"],
                "price" => (float)$row["unit_price"]
            ];

            if ($row["rating"]) {
                $orders[$id]["review"] = [
                    "rating" => (int)$row["rating"],
                    "text" => $row["review_text"]
                ];
            }
        }
    }

    echo json_encode([
        "success" => true,
        "orders" => array_values($orders)
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
