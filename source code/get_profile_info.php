<?php
header("Content-Type: application/json");
require "db_connection.php";

try {
    $email = trim($_GET["email"] ?? "");

    if ($email === "") {
        echo json_encode(["success" => false, "message" => "Missing email"]);
        exit;
    }

    $sql = "SELECT 
                user_id,
                user_email,
                user_role,
                first_name,
                last_name,
                contact,
                user_profile,
                created_at
            FROM `user`
            WHERE user_email = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }

    $addrSql = "SELECT 
                address_id,
                house_no,
                street,
                barangay,
                city,
                province,
                zip,
                is_default
            FROM address
            WHERE user_id = ?
            ORDER BY is_default DESC, address_id DESC";

    $addrStmt = $conn->prepare($addrSql);
    $addrStmt->bind_param("i", $user["user_id"]);
    $addrStmt->execute();

    $addrResult = $addrStmt->get_result();
    $addresses = [];

    while ($addr = $addrResult->fetch_assoc()) {
        $addresses[] = $addr;
    }

    // --- FIXED: Join both tables and pick whichever image column is not null ---
    $orderSql = "SELECT 
                    o.order_id, 
                    o.order_name, 
                    o.total_amount,  
                    o.delivery_date, 
                    o.status,
                    COALESCE(b.image, p.product_image) AS final_image
                 FROM `order` o
                 LEFT JOIN `bouquet` b ON o.order_name = b.name
                 LEFT JOIN `product` p ON o.order_name = p.product_name
                 WHERE o.user_id = ? 
                 ORDER BY o.order_id DESC";

    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param("i", $user["user_id"]);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    
    $orders = [];
    while ($row = $orderResult->fetch_assoc()) {
        $orders[] = [
            "id" => $row["order_id"],
            "items" => $row["order_name"], 
            "total" => floatval($row["total_amount"]),
            "delivDate" => $row["delivery_date"],
            "status" => $row["status"],
            "image" => $row["final_image"] // Sends either bouquet image filename or product image filename
        ];
    }


    echo json_encode([
        "success" => true,
        "user" => $user,
        "addresses" => $addresses,
        "orders" => $orders 
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>