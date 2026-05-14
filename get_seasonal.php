<?php
header("Content-Type: application/json");
require "db_connection.php";

$sql = "SELECT bouquet_id, name, category, stock, status 
        FROM bouquet 
        WHERE LOWER(status) = 'active'
        ORDER BY stock DESC";

$result = $conn->query($sql);

$data = [
    "all" => [
        "label" => "All Products",
        "icon" => "🌸",
        "period" => "Current Stock",
        "banner" => [
            "bg" => "var(--soft)",
            "border" => "var(--line)",
            "text" => "<strong>Seasonal Trends</strong> are based on current bouquet stock from the database."
        ],
        "flowers" => [],
        "restock" => []
    ]
];

while ($row = $result->fetch_assoc()) {
    $stock = (int)$row["stock"];

    if ($stock <= 5) {
        $priority = "🔴 Critical";
        $qty = "Restock 50+ pcs";
    } elseif ($stock <= 20) {
        $priority = "🟡 High";
        $qty = "Restock 30+ pcs";
    } else {
        $priority = "🔵 Medium";
        $qty = "Restock 10+ pcs";
    }

    $data["all"]["flowers"][] = [
        "n" => $row["name"],
        "i" => "💐",
        "v" => $stock,
        "c" => "var(--p3)"
    ];

    $data["all"]["restock"][] = [
        $row["name"],
        $qty,
        $priority
    ];
}

echo json_encode($data);
?>