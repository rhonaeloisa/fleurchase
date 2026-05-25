<?php
header('Content-Type: application/json');
require_once 'db_connection.php'; // Tatawagin natin ang database connection mo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your email address.']);
        exit;
    }

    // I-check kung nag-e-exist ang email sa `user` table
    $stmt = $conn->prepare("SELECT user_id FROM `user` WHERE user_email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Kapag nahanap ang email sa DB, saka lang mag-ge-generate ng OTP
        $otp = rand(100000, 999999);
        echo json_encode(['success' => true, 'otp' => $otp]);
    } else {
        // Kapag wala ang email sa DB, ibabalik ang error message na ito
        echo json_encode(['success' => false, 'message' => 'This email is not registered in our system.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>