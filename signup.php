<?php
session_start();
header("Content-Type: application/json");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require "db_connection.php";

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo json_encode(["success" => false, "message" => "Invalid request method"]);
        exit;
    }

    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $contact = trim($_POST["contact"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($firstName === "" || $lastName === "" || $email === "" || $contact === "" || $password === "") {
        echo json_encode(["success" => false, "message" => "Please fill in all fields."]);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO `user` (first_name, last_name, user_email, contact, user_pass, user_role)
            VALUES (?, ?, ?, ?, ?, 'customer')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $firstName, $lastName, $email, $contact, $hashedPassword);
    $stmt->execute();

    $userId = $conn->insert_id;

    $_SESSION["user_id"] = $userId;
    $_SESSION["user_email"] = $email;
    $_SESSION["user_role"] = "customer";

    echo json_encode([
        "success" => true,
        "message" => "Account created successfully",
        "user" => [
            "user_id" => $userId,
            "id" => $userId,
            "email" => $email,
            "user_email" => $email,
            "role" => "customer",
            "name" => trim($firstName . " " . $lastName),
            "first_name" => $firstName,
            "last_name" => $lastName,
            "initial" => strtoupper(substr($firstName, 0, 1))
        ]
    ]);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);

    if (isset($conn) && $conn->errno === 1062) {
        echo json_encode([
            "success" => false,
            "message" => "Email already exists."
        ]);
        exit;
    }

    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>