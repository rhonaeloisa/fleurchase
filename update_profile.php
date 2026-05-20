<?php
header("Content-Type: application/json");
require "db_connection.php";

try {
    $oldEmail = trim($_POST["old_email"] ?? "");
    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $contact = trim($_POST["contact"] ?? "");

    if ($oldEmail === "" || $firstName === "" || $email === "") {
        echo json_encode(["success" => false, "message" => "Name and email are required"]);
        exit;
    }

    $sql = "UPDATE `user`
            SET first_name = ?,
                last_name = ?,
                user_email = ?,
                contact = ?
            WHERE user_email = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $firstName, $lastName, $email, $contact, $oldEmail);
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Profile updated",
        "user" => [
            "email" => $email,
            "name" => trim($firstName . " " . $lastName),
            "phone" => $contact
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
