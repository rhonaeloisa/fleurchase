<?php
session_start();
header("Content-Type: application/json");

// Establish connection and catch structural errors cleanly
require "db_connection.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$action = $_POST['action'] ?? '';

try {
    if ($action === 'update_address') {
        $id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
        
        // FIXED: Explicitly fallback to an empty string if house number is left blank
        $house = isset($_POST['house_no']) ? trim($_POST['house_no']) : '';
        $street = isset($_POST['street']) ? trim($_POST['street']) : '';
        $barangay = isset($_POST['barangay']) ? trim($_POST['barangay']) : '';
        $city = isset($_POST['city']) ? trim($_POST['city']) : '';
        $province = isset($_POST['province']) ? trim($_POST['province']) : 'Albay';
        $zip = isset($_POST['zip']) ? trim($_POST['zip']) : '';

        if ($id <= 0 || empty($street) || empty($barangay) || empty($zip)) {
            echo json_encode(["success" => false, "message" => "Missing required address fields."]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE address SET house_no = ?, street = ?, barangay = ?, city = ?, province = ?, zip = ? WHERE address_id = ?");
        $stmt->bind_param("ssssssi", $house, $street, $barangay, $city, $province, $zip, $id);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Database execution failed during update query."]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'create_address') {
        $email = isset($_POST['user_email']) ? trim($_POST['user_email']) : '';
        
        // FIXED: Safe string extraction fallback for new address items
        $house = isset($_POST['house_no']) ? trim($_POST['house_no']) : '';
        $street = isset($_POST['street']) ? trim($_POST['street']) : '';
        $barangay = isset($_POST['barangay']) ? trim($_POST['barangay']) : '';
        $city = isset($_POST['city']) ? trim($_POST['city']) : '';
        $province = isset($_POST['province']) ? trim($_POST['province']) : 'Albay';
        $zip = isset($_POST['zip']) ? trim($_POST['zip']) : '';

        if (empty($email) || empty($street) || empty($barangay) || empty($zip)) {
            echo json_encode(["success" => false, "message" => "Missing required fields for new address record."]);
            exit;
        }

        // Trace user ID matching current account session parameters
        $uStmt = $conn->prepare("SELECT user_id FROM `user` WHERE user_email = ? LIMIT 1");
        $uStmt->bind_param("s", $email);
        $uStmt->execute();
        $user = $uStmt->get_result()->fetch_assoc();
        $uStmt->close();

        if (!$user) {
            echo json_encode(["success" => false, "message" => "Active user session record not found."]);
            exit;
        }
        $userId = intval($user['user_id']);

        $stmt = $conn->prepare("INSERT INTO address (user_id, house_no, street, barangay, city, province, zip, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("issssss", $userId, $house, $street, $barangay, $city, $province, $zip);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "new_address_id" => $conn->insert_id]);
        } else {
            echo json_encode(["success" => false, "message" => "Database execution failed during insertion query."]);
        }
        $stmt->close();
        exit;
    }

    echo json_encode(["success" => false, "message" => "Invalid or unrecognized action parameter context."]);

} catch (Throwable $e) {
    // If anything fails inside XAMPP, return a descriptive error instead of crashing silently
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Server transaction error: " . $e->getMessage()
    ]);
}

$conn->close();
exit;
?>