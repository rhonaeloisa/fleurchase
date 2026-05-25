<?php
header('Content-Type: application/json');
require_once 'db_connection.php'; // Siguraduhin na ang $conn ay galing dito

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (empty($email) || empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Missing data.']);
        exit;
    }

    // UPDATE query para sa table na `user`
    // Ginamit ang $conn para mag-match sa login.php mo
    $stmt = $conn->prepare("UPDATE `user` SET user_pass = ? WHERE user_email = ?");
    $stmt->bind_param("ss", $new_password, $email);
    $result = $stmt->execute();

    if ($result && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Password reset successful!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Email address not found or no change made.']);
    }
}
?>