<?php
header("Content-Type: application/json");
require "db_connection.php";

try {
    $email = trim($_POST["email"] ?? "");
    $addressId = (int)($_POST["address_id"] ?? 0);
    $houseNo = trim($_POST["house_no"] ?? "");
    $street = trim($_POST["street"] ?? "");
    $barangay = trim($_POST["barangay"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $province = trim($_POST["province"] ?? "");
    $zip = trim($_POST["zip"] ?? "");

    if ($email === "" || $street === "" || $barangay === "" || $city === "" || $province === "") {
        echo json_encode(["success" => false, "message" => "Please complete address details"]);
        exit;
    }

    $userStmt = $conn->prepare("SELECT user_id FROM `user` WHERE user_email = ? LIMIT 1");
    $userStmt->bind_param("s", $email);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }

    $userId = (int)$user["user_id"];

    if ($addressId > 0) {
        $sql = "UPDATE address
                SET house_no = ?, street = ?, barangay = ?, city = ?, province = ?, zip = ?
                WHERE address_id = ? AND user_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssii", $houseNo, $street, $barangay, $city, $province, $zip, $addressId, $userId);
        $stmt->execute();

        echo json_encode(["success" => true, "message" => "Address updated"]);
        exit;
    }

    $checkSql = "SELECT address_id FROM address
                 WHERE user_id = ?
                   AND house_no = ?
                   AND street = ?
                   AND barangay = ?
                   AND city = ?
                   AND province = ?
                   AND zip = ?
                 LIMIT 1";

    $check = $conn->prepare($checkSql);
    $check->bind_param("issssss", $userId, $houseNo, $street, $barangay, $city, $province, $zip);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        echo json_encode(["success" => false, "message" => "This address already exists"]);
        exit;
    }

    $isDefault = 0;

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM address WHERE user_id = ?");
    $countStmt->bind_param("i", $userId);
    $countStmt->execute();
    $count = $countStmt->get_result()->fetch_assoc();

    if ((int)$count["total"] === 0) {
        $isDefault = 1;
    }

    $sql = "INSERT INTO address
            (user_id, house_no, street, barangay, city, province, zip, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssi", $userId, $houseNo, $street, $barangay, $city, $province, $zip, $isDefault);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Address added"]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
