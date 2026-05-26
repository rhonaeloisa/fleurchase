<?php
header("Content-Type: application/json");
require "db_connection.php";

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    echo json_encode(["success" => false, "message" => "Email and password are required"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT user_id, user_email, user_pass, user_role, first_name, last_name
    FROM `user`
    WHERE user_email = ?
    LIMIT 1
");

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user["user_pass"])) {
    echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    exit;
}

echo json_encode([
    "success" => true,
    "user" => [
        "user_id" => $user["user_id"],
        "id" => $user["user_id"],
        "email" => $user["user_email"],
        "user_email" => $user["user_email"],
        "role" => $user["user_role"],
        "name" => trim($user["first_name"] . " " . $user["last_name"]),
        "first_name" => $user["first_name"],
        "last_name" => $user["last_name"],
        "initial" => strtoupper(substr($user["first_name"], 0, 1))
    ]
]);
?>