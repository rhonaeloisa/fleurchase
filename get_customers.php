<?php
header("Content-Type: application/json");
require "db_connection.php";

$sql = "SELECT 
            user_id,
            user_email,
            user_role
        FROM `user`
        WHERE LOWER(user_role) = 'customer'
        ORDER BY user_id DESC";

$result = $conn->query($sql);

$customers = [];

while ($row = $result->fetch_assoc()) {
    $customers[] = [
        "id" => $row["user_id"],
        "name" => explode("@", $row["user_email"])[0],
        "email" => $row["user_email"],
        "phone" => "—",
        "loc" => "—",
        "joined" => "—",
        "orderCount" => 0,
        "spent" => 0,
        "preferred" => "—",
        "lastOrder" => null
    ];
}

echo json_encode($customers);
?>