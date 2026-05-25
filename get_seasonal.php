<?php
header('Content-Type: application/json');
require 'db_connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$SEASONS = [
    'all' => [
        'label' => 'All Year',
        'icon' => 'All',
        'period' => 'All recorded orders',
        'months' => [],
        'banner' => [
            'bg' => 'var(--soft)',
            'border' => 'var(--line)',
            'text' => '<strong>All-year demand</strong> is calculated from every non-cancelled order in the database.'
        ],
    ],
    'valentines' => [
        'label' => "Valentine's",
        'icon' => 'Feb',
        'period' => 'February',
        'months' => [2],
        'banner' => [
            'bg' => 'var(--p9)',
            'border' => 'var(--p6)',
            'text' => '<strong>Valentine demand</strong> uses February order history and current bouquet stock.'
        ],
    ],
    'graduation' => [
        'label' => 'Graduation',
        'icon' => 'Mar-Apr',
        'period' => 'March to April',
        'months' => [3, 4],
        'banner' => [
            'bg' => '#FFF8E1',
            'border' => '#FFA000',
            'text' => '<strong>Graduation demand</strong> uses March and April order history.'
        ],
    ],
    'mothers' => [
        'label' => "Mother's Day",
        'icon' => 'May',
        'period' => 'May',
        'months' => [5],
        'banner' => [
            'bg' => 'var(--g9)',
            'border' => 'var(--g6)',
            'text' => '<strong>Mother\'s Day demand</strong> uses May order history.'
        ],
    ],
    'all_saints' => [
        'label' => "All Saints'",
        'icon' => 'Nov',
        'period' => 'November',
        'months' => [11],
        'banner' => [
            'bg' => 'var(--soft)',
            'border' => 'var(--line)',
            'text' => '<strong>All Saints demand</strong> uses November order history.'
        ],
    ],
    'christmas' => [
        'label' => 'Christmas',
        'icon' => 'Dec',
        'period' => 'December',
        'months' => [12],
        'banner' => [
            'bg' => 'var(--g9)',
            'border' => 'var(--g5)',
            'text' => '<strong>Holiday demand</strong> uses December order history.'
        ],
    ],
    'wedding' => [
        'label' => 'Wedding',
        'icon' => 'Nov-Feb',
        'period' => 'November to February',
        'months' => [11, 12, 1, 2],
        'banner' => [
            'bg' => 'var(--p9)',
            'border' => 'var(--p6)',
            'text' => '<strong>Wedding season demand</strong> uses November through February order history.'
        ],
    ],
];

function runTopFlowers(mysqli $conn, array $months): array {
    $whereMonths = '';
    $types = '';
    $params = [];

    if ($months) {
        $placeholders = implode(',', array_fill(0, count($months), '?'));
        $whereMonths = "AND MONTH(o.order_date) IN ($placeholders)";
        $types = str_repeat('i', count($months));
        $params = $months;
    }

    $sql = "
        SELECT
            COALESCE(b.name, oi.snapshot_name, 'Bouquet') AS name,
            COALESCE(b.stock, 0) AS stock,
            COALESCE(SUM(COALESCE(oi.quantity, 1)), 0) AS sold_qty
        FROM order_item oi
        INNER JOIN `order` o ON oi.order_id = o.order_id
        LEFT JOIN bouquet b ON oi.bouquet_id = b.bouquet_id
        WHERE oi.bouquet_id IS NOT NULL
          AND COALESCE(o.status, '') <> 'Cancelled'
          AND LOWER(COALESCE(b.category, '')) <> 'addon'
          AND LOWER(COALESCE(b.bouquet_type, '')) <> 'addon'
          $whereMonths
        GROUP BY oi.bouquet_id, COALESCE(b.name, oi.snapshot_name, 'Bouquet'), COALESCE(b.stock, 0)
        HAVING sold_qty > 0
        ORDER BY sold_qty DESC, name ASC
        LIMIT 6
    ";

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function runStockFallback(mysqli $conn, array $months): array {
    $keywordSql = '';

    if (in_array(2, $months, true)) {
        $keywordSql = "AND (LOWER(name) LIKE '%rose%' OR LOWER(name) LIKE '%tulip%' OR category = 'seasonal')";
    } elseif (in_array(5, $months, true)) {
        $keywordSql = "AND (LOWER(name) LIKE '%lil%' OR LOWER(name) LIKE '%rose%' OR category IN ('seasonal','gift-set'))";
    } elseif (in_array(3, $months, true) || in_array(4, $months, true)) {
        $keywordSql = "AND (LOWER(name) LIKE '%sunflower%' OR LOWER(name) LIKE '%tulip%' OR category = 'seasonal')";
    } elseif (in_array(12, $months, true)) {
        $keywordSql = "AND (category IN ('gift-set','seasonal','ready-made') OR LOWER(name) LIKE '%rose%')";
    }

    $sql = "
        SELECT name, stock, stock AS sold_qty
        FROM bouquet
        WHERE LOWER(status) = 'active'
          $keywordSql
        ORDER BY stock DESC, name ASC
        LIMIT 6
    ";

    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function buildRestock(mysqli $conn, array $topRows): array {
    $names = array_column($topRows, 'name');
    $restock = [];

    foreach ($topRows as $row) {
        $stock = (int) $row['stock'];
        $sold = (int) $row['sold_qty'];
        $target = max(20, (int) ceil($sold * 1.5));
        $needed = max(0, $target - $stock);

        if ($stock <= 5 || $needed >= 40) {
            $priority = 'Critical';
            $qty = 'Restock ' . max(40, $needed) . '+ pcs';
        } elseif ($stock <= 20 || $needed >= 20) {
            $priority = 'High';
            $qty = 'Restock ' . max(20, $needed) . '+ pcs';
        } else {
            $priority = 'Medium';
            $qty = 'Restock ' . max(10, $needed) . '+ pcs';
        }

        $restock[] = [$row['name'], $qty, $priority];
        $names[] = $row['name'];
    }

    $lowSql = "
        SELECT name, stock
        FROM bouquet
        WHERE LOWER(status) = 'active'
          AND stock <= 20
        ORDER BY stock ASC, name ASC
        LIMIT 6
    ";

    foreach ($conn->query($lowSql)->fetch_all(MYSQLI_ASSOC) as $row) {
        if (in_array($row['name'], $names, true)) {
            continue;
        }

        $stock = (int) $row['stock'];
        $restock[] = [
            $row['name'],
            $stock <= 5 ? 'Restock 50+ pcs' : 'Restock 30+ pcs',
            $stock <= 5 ? 'Critical' : 'High',
        ];
    }

    return array_slice($restock, 0, 8);
}

function colorForRank(int $rank): string {
    $colors = ['var(--p3)', 'var(--g3)', '#FFA000', 'var(--p4)', 'var(--g4)', 'var(--muted)'];
    return $colors[$rank] ?? 'var(--muted)';
}

$data = [];

foreach ($SEASONS as $key => $season) {
    $rows = runTopFlowers($conn, $season['months']);
    $usesFallback = false;

    if (!$rows) {
        $rows = runStockFallback($conn, $season['months']);
        $usesFallback = true;
    }

    $max = max(array_map(fn($row) => (int) $row['sold_qty'], $rows) ?: [1]);
    $flowers = [];

    foreach ($rows as $index => $row) {
        $value = $usesFallback
            ? min(100, max(10, (int) round(((int) $row['sold_qty'] / max(1, $max)) * 100)))
            : min(100, max(8, (int) round(((int) $row['sold_qty'] / max(1, $max)) * 100)));

        $flowers[] = [
            'n' => $row['name'],
            'i' => '#' . ($index + 1),
            'v' => $value,
            'raw' => (int) $row['sold_qty'],
            'stock' => (int) $row['stock'],
            'c' => colorForRank($index),
        ];
    }

    if ($usesFallback) {
        $season['banner']['text'] .= ' No matching sales yet, so the chart is using active bouquet stock as a planning fallback.';
    }

    $data[$key] = [
        'label' => $season['label'],
        'icon' => $season['icon'],
        'period' => $season['period'],
        'banner' => $season['banner'],
        'flowers' => $flowers,
        'restock' => buildRestock($conn, $rows),
    ];
}

echo json_encode($data);
?>
