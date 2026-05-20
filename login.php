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

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        echo json_encode(["success" => false, "message" => "Email and password are required"]);
        exit;
    }

    $sql = "SELECT user_id, user_email, user_pass, user_role
            FROM `user`
            WHERE user_email = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || $password !== $user["user_pass"]) {
        echo json_encode(["success" => false, "message" => "Invalid email or password"]);
        exit;
    }

    $_SESSION["user_id"] = $user["user_id"];
    $_SESSION["user_email"] = $user["user_email"];
    $_SESSION["user_role"] = $user["user_role"];

    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "role" => $user["user_role"],
        "email" => $user["user_email"]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>