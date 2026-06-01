
<?php
header("Content-Type: application/json");
require "db_connection.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $promoId = (int)($_POST["promo_id"] ?? 0);
    $code = trim($_POST["code"] ?? "");
    $name = trim($_POST["promo_name"] ?? "");
    $desc = trim($_POST["description"] ?? "");
    $type = trim($_POST["discount_type"] ?? "");
    $value = (float)($_POST["discount_value"] ?? 0);
    $start = $_POST["start_date"] ?? "";
    $end = $_POST["end_date"] ?? "";
    $minOrder = (float)($_POST["min_order_amount"] ?? 0);
    $status = trim($_POST["status"] ?? "active");
    $usageLimit = (int)($_POST["usage_limit_per_user"] ?? 1);

    if ($code === "" || $name === "" || $type === "" || $value <= 0 || $start === "" || $end === "") {
        echo json_encode(["success" => false, "message" => "Please fill in all required fields"]);
        exit;
    }

    if ($promoId > 0) {
        $sql = "UPDATE promos
                SET code = ?,
                    promo_name = ?,
                    description = ?,
                    discount_type = ?,
                    discount_value = ?,
                    start_date = ?,
                    end_date = ?,
                    min_order_amount = ?,
                    status = ?,
                    usage_limit_per_user = ?
                WHERE promo_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssdssdsii",
            $code,
            $name,
            $desc,
            $type,
            $value,
            $start,
            $end,
            $minOrder,
            $status,
            $usageLimit,
            $promoId
        );
        $stmt->execute();

        echo json_encode(["success" => true, "message" => "Promo updated"]);
        exit;
    }

    $sql = "INSERT INTO promos
            (code, promo_name, description, discount_type, discount_value, start_date, end_date, min_order_amount, status, usage_limit_per_user)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssdssdsi",
        $code,
        $name,
        $desc,
        $type,
        $value,
        $start,
        $end,
        $minOrder,
        $status,
        $usageLimit
    );
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Promo added",
        "promo_id" => $conn->insert_id
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
