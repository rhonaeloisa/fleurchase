<?php
header('Content-Type: application/json');

define('DB_HOST', 'localhost');
define('DB_NAME', 'fleurchase_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

function monthBounds(string $month): array {
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = date('Y-m');
    }

    $start = DateTime::createFromFormat('Y-m-d', $month . '-01');
    if (!$start) {
        $start = new DateTime('first day of this month');
    }

    $end = clone $start;
    $end->modify('first day of next month');

    $prevStart = clone $start;
    $prevStart->modify('first day of previous month');
    $prevEnd = clone $start;

    return [
        'month' => $start->format('Y-m'),
        'label' => $start->format('F Y'),
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d'),
        'prev_start' => $prevStart->format('Y-m-d'),
        'prev_end' => $prevEnd->format('Y-m-d'),
    ];
}

function getAvailableMonths(PDO $db): array {
    $stmt = $db->query(
        "SELECT DISTINCT DATE_FORMAT(order_date, '%Y-%m') AS value,
                DATE_FORMAT(order_date, '%M %Y') AS label
         FROM `order`
         WHERE order_date IS NOT NULL
         ORDER BY value DESC"
    );

    $months = $stmt->fetchAll();

    if (!$months) {
        $months[] = [
            'value' => date('Y-m'),
            'label' => date('F Y'),
        ];
    }

    return $months;
}

function metricSlice(PDO $db, string $start, string $end): array {
    $stmt = $db->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN status = 'Delivered' THEN total_amount ELSE 0 END), 0) AS revenue,
            COUNT(CASE WHEN status = 'Delivered' THEN 1 END) AS completed_orders,
            COUNT(DISTINCT CASE WHEN status = 'Delivered' THEN user_id END) AS customers
         FROM `order`
         WHERE order_date >= ? AND order_date < ?"
    );
    $stmt->execute([$start, $end]);
    $row = $stmt->fetch() ?: [];

    $repeatStmt = $db->prepare(
        "SELECT COUNT(*) FROM (
            SELECT user_id
            FROM `order`
            WHERE order_date >= ?
              AND order_date < ?
              AND status = 'Delivered'
              AND user_id IS NOT NULL
            GROUP BY user_id
            HAVING COUNT(*) > 1
         ) repeat_customers"
    );
    $repeatStmt->execute([$start, $end]);
    $repeatCustomers = (int) $repeatStmt->fetchColumn();
    $customers = (int) ($row['customers'] ?? 0);

    return [
        'revenue' => (float) ($row['revenue'] ?? 0),
        'completed_orders' => (int) ($row['completed_orders'] ?? 0),
        'avg_order_value' => ((int) ($row['completed_orders'] ?? 0)) > 0
            ? round(((float) ($row['revenue'] ?? 0)) / (int) $row['completed_orders'], 2)
            : 0,
        'repeat_percent' => $customers > 0 ? round(($repeatCustomers / $customers) * 100) : 0,
    ];
}

function getMetrics(PDO $db, array $bounds): array {
    $current = metricSlice($db, $bounds['start'], $bounds['end']);
    $previous = metricSlice($db, $bounds['prev_start'], $bounds['prev_end']);

    return [
        'current' => $current,
        'previous' => $previous,
    ];
}

function getTopProducts(PDO $db, array $bounds): array {
    $stmt = $db->prepare(
        "SELECT
            COALESCE(b.name, oi.snapshot_name, 'Product') AS name,
            SUM(COALESCE(oi.quantity, 1)) AS units,
            SUM(COALESCE(oi.subtotal, oi.unit_price * COALESCE(oi.quantity, 1), 0)) AS revenue
         FROM order_item oi
         INNER JOIN `order` o ON oi.order_id = o.order_id
         LEFT JOIN bouquet b ON oi.bouquet_id = b.bouquet_id
         WHERE o.order_date >= ?
           AND o.order_date < ?
           AND o.status = 'Delivered'
         GROUP BY oi.bouquet_id, COALESCE(b.name, oi.snapshot_name, 'Product')
         ORDER BY units DESC, revenue DESC, name ASC
         LIMIT 8"
    );
    $stmt->execute([$bounds['start'], $bounds['end']]);
    return $stmt->fetchAll();
}

function getCategoryBreakdown(PDO $db, array $bounds): array {
    $stmt = $db->prepare(
        "SELECT
            COALESCE(NULLIF(b.category, ''), NULLIF(b.bouquet_type, ''), 'Uncategorized') AS category,
            SUM(COALESCE(oi.subtotal, oi.unit_price * COALESCE(oi.quantity, 1), 0)) AS revenue
         FROM order_item oi
         INNER JOIN `order` o ON oi.order_id = o.order_id
         LEFT JOIN bouquet b ON oi.bouquet_id = b.bouquet_id
         WHERE o.order_date >= ?
           AND o.order_date < ?
           AND o.status = 'Delivered'
         GROUP BY category
         ORDER BY revenue DESC"
    );
    $stmt->execute([$bounds['start'], $bounds['end']]);
    return $stmt->fetchAll();
}

function getPaymentBreakdown(PDO $db, array $bounds): array {
    $stmt = $db->prepare(
        "SELECT
            COALESCE(NULLIF(p.payment_type, ''), 'Unspecified') AS method,
            COUNT(DISTINCT o.order_id) AS orders
         FROM `order` o
         LEFT JOIN payment p ON o.order_id = p.order_id
         WHERE o.order_date >= ?
           AND o.order_date < ?
           AND COALESCE(o.status, '') <> 'Cancelled'
         GROUP BY method
         ORDER BY orders DESC, method ASC"
    );
    $stmt->execute([$bounds['start'], $bounds['end']]);
    return $stmt->fetchAll();
}

function getDeliveryBreakdown(PDO $db, array $bounds): array {
    $stmt = $db->prepare(
        "SELECT
            COALESCE(NULLIF(a.city, ''), o.delivery_type, 'Unspecified') AS area,
            COUNT(DISTINCT o.order_id) AS orders
         FROM `order` o
         LEFT JOIN shipment s ON o.order_id = s.order_id
         LEFT JOIN address a ON s.address_id = a.address_id
         WHERE o.order_date >= ?
           AND o.order_date < ?
           AND COALESCE(o.status, '') <> 'Cancelled'
         GROUP BY area
         ORDER BY orders DESC, area ASC
         LIMIT 8"
    );
    $stmt->execute([$bounds['start'], $bounds['end']]);
    return $stmt->fetchAll();
}

function getOrderLog(PDO $db, array $bounds): array {
    $stmt = $db->prepare(
        "SELECT
            o.order_id,
            o.order_name,
            o.order_date,
            o.total_amount,
            o.status,
            COALESCE(NULLIF(a.city, ''), o.delivery_type, 'Unspecified') AS location,
            COALESCE(NULLIF(p.payment_type, ''), 'Unspecified') AS payment_method,
            GROUP_CONCAT(CONCAT(COALESCE(oi.snapshot_name, 'Item'), ' x', COALESCE(oi.quantity, 1)) SEPARATOR ', ') AS items
         FROM `order` o
         LEFT JOIN shipment s ON o.order_id = s.order_id
         LEFT JOIN address a ON s.address_id = a.address_id
         LEFT JOIN payment p ON o.order_id = p.order_id
         LEFT JOIN order_item oi ON o.order_id = oi.order_id
         WHERE o.order_date >= ?
           AND o.order_date < ?
         GROUP BY o.order_id, o.order_name, o.order_date, o.total_amount, o.status, location, payment_method
         ORDER BY o.order_date DESC, o.order_id DESC"
    );
    $stmt->execute([$bounds['start'], $bounds['end']]);
    return $stmt->fetchAll();
}

try {
    $db = getDB();
    $bounds = monthBounds($_GET['month'] ?? date('Y-m'));

    echo json_encode([
        'months' => getAvailableMonths($db),
        'period' => [
            'value' => $bounds['month'],
            'label' => $bounds['label'],
        ],
        'metrics' => getMetrics($db, $bounds),
        'top_products' => getTopProducts($db, $bounds),
        'categories' => getCategoryBreakdown($db, $bounds),
        'payments' => getPaymentBreakdown($db, $bounds),
        'delivery_areas' => getDeliveryBreakdown($db, $bounds),
        'orders' => getOrderLog($db, $bounds),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
