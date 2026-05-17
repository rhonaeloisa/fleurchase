<?php
header("Content-Type: application/json");
require "db_connection.php";

function normalizeReceiptPath($receipt) {
    $receipt = trim($receipt ?? '');

    if ($receipt === '') {
        return '';
    }

    if (
        str_starts_with($receipt, 'http') ||
        str_starts_with($receipt, 'uploads/') ||
        str_starts_with($receipt, 'images/') ||
        str_starts_with($receipt, 'data:image')
    ) {
        return $receipt;
    }

    return 'uploads/receipts/' . $receipt;
}

$sql = "
SELECT 
    o.order_id,
    o.order_name,
    o.order_date,
    o.total_amount,
    o.status AS order_status,
    o.delivery_date,
    o.delivery_time,
    o.delivery_type,
    o.discount_amount,
    o.shipping_fee,
    o.notes,

     CONCAT(
            COALESCE(a.house_no, ''), 
            IF(a.house_no IS NOT NULL AND a.street IS NOT NULL, ', ', ''),
            COALESCE(a.street, ''),
            IF(a.street IS NOT NULL AND a.barangay IS NOT NULL, ', ', ''),
            COALESCE(a.barangay, ''),
            IF(a.barangay IS NOT NULL AND a.city IS NOT NULL, ', ', ''),
            COALESCE(a.province, '')
        ) AS full_address,

    u.first_name,
    u.last_name,
    u.contact,
    u.user_email,

    p.payment_type,
    p.reference_number,
    p.img_receipt,
    p.status AS payment_status,

    a.city,

    GROUP_CONCAT(oi.snapshot_name SEPARATOR ', ') AS items
FROM `order` o
LEFT JOIN `user` u ON o.user_id = u.user_id
LEFT JOIN shipment s ON o.order_id = s.order_id
LEFT JOIN `address` a ON s.address_id = a.address_id
LEFT JOIN payment p ON o.order_id = p.order_id
LEFT JOIN order_item oi ON o.order_id = oi.order_id
GROUP BY o.order_id
ORDER BY o.order_id DESC
";

$result = $conn->query($sql);

$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = [
        "id" => "ORD-" . $row["order_id"],
        "db_id" => $row["order_id"],
        "customer" => trim(($row["first_name"] ?? "") . " " . ($row["last_name"] ?? "")),
        "phone" => $row["contact"] ?? "—",
        "loc" => $row["delivery_type"] ?? "—",
        "items" => $row["items"] ?? "—",
        "delivDate" => $row["delivery_date"] ?? "—",
        "delivTime" => $row["delivery_time"] ?? "—",
        "payMethod" => $row["payment_type"] ?? "—",
        "payStatus" => strtolower($row["payment_status"] ?? "pending"),
        "receipt" => normalizeReceiptPath($row["img_receipt"] ?? ""),
        "receiptImg" => normalizeReceiptPath($row["img_receipt"] ?? ""),
        "refNum" => $row["reference_number"] ?? "",
        "status" => $row["order_status"] ?? "Pending",
        "total" => (float)($row["total_amount"] ?? 0),
        "discount" => (float)($row["discount_amount"] ?? 0),
        "city" => $row["city"] ?? "—",
        "full_address" => $row["full_address"] ?? 'No address',
        "shippingFee" => (float)($row["shipping_fee"] ?? 0),
        "giftMsg" => $row["notes"] ?? "",
        "placedAt" => $row["order_date"] ?? ""
    ];
}

echo json_encode($orders);
?>