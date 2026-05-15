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
                user_profile
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


    echo json_encode([
        "success" => true,
        "user" => $user,
        "addresses" => $addresses
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
