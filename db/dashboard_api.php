

<?php
/**
 * FleurChase — Dashboard API
 * Endpoint: /dashboard_api.php
 *
 * ── SETUP ──────────────────────────────────────────────────────────────────
 * 1. Copy this file to your project root (same level as admin.html).
 * 2. Fill in the DB_ constants below with your credentials.
 * 3. The frontend admin.html will call: GET /dashboard_api.php?action=<name>
 * ───────────────────────────────────────────────────────────────────────────
 */

// ── Database config ──────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'fleurchase_db');   // ← change this
define('DB_USER', 'root');         // ← change this
define('DB_PASS', '');     // ← change this
define('DB_CHARSET', 'utf8mb4');

// ── CORS / headers (adjust origin in production) ─────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── PDO connection ────────────────────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ── Router ────────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? 'all';

try {
    $db = getDB();

    switch ($action) {
        case 'metrics':     echo json_encode(getMetrics($db));     break;
        case 'orders':      echo json_encode(getRecentOrders($db)); break;
        case 'top_flowers': echo json_encode(getTopFlowers($db));  break;
        case 'alerts':      echo json_encode(getFreshnessAlerts($db)); break;
        case 'all':
        default:
            echo json_encode([
                'metrics'     => getMetrics($db),
                'orders'      => getRecentOrders($db),
                'top_flowers' => getTopFlowers($db),
                'alerts'      => getFreshnessAlerts($db),
            ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// ── Action handlers ───────────────────────────────────────────────────────────

/**
 * KPI metrics shown in the top metric cards.
 */
function getMetrics(PDO $db): array {
    // Total orders
    $totalOrders = (int) $db->query('SELECT COUNT(*) FROM `order`')->fetchColumn();

    // Revenue is recognized only from delivered orders.
    $revenue = (float) $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM `order` WHERE status = 'Delivered'")->fetchColumn();

    // Pending orders (status = 'Pending')
    $pending = (int) $db->query(
        "SELECT COUNT(*) FROM `order` WHERE status = 'Pending'"
    )->fetchColumn();

    // Low-stock bouquets (stock < 30 stems)
    $lowStock = (int) $db->query(
        "SELECT COUNT(*) FROM bouquet WHERE stock < 30 AND status = 'active'"
    )->fetchColumn();

    // Also count low-stock products (accessories / add-ons)
    $lowStockProducts = (int) $db->query(
        "SELECT COUNT(*) FROM product WHERE stock < 30 AND status = 'active'"
    )->fetchColumn();

    return [
        'total_orders'   => $totalOrders,
        'revenue'        => $revenue,
        'pending_orders' => $pending,
        'low_stock'      => $lowStock + $lowStockProducts,
    ];
}

/**
 * 5 most recent orders for the Recent Orders table.
 */
function getRecentOrders(PDO $db): array {
    $stmt = $db->query(
        "SELECT order_id, order_name, order_date, total_amount, status, delivery_type
         FROM `order`
         ORDER BY order_date DESC
         LIMIT 5"
    );
    return $stmt->fetchAll();
}

function getTopFlowers(PDO $db): array {
    $stmt = $db->prepare(
        "SELECT
            COALESCE(b.name, oi.snapshot_name, 'Bouquet') AS name,
            COALESCE(b.stock, 0) AS stock,
            COALESCE(b.category, '') AS category,
            COALESCE(b.bouquet_type, '') AS bouquet_type,
            SUM(COALESCE(oi.quantity, 1)) AS sold_qty,
            SUM(COALESCE(oi.subtotal, oi.unit_price * COALESCE(oi.quantity, 1), 0)) AS revenue
         FROM order_item oi
         INNER JOIN `order` o ON oi.order_id = o.order_id
         LEFT JOIN bouquet b ON oi.bouquet_id = b.bouquet_id
         WHERE o.status = 'Delivered'
           AND oi.bouquet_id IS NOT NULL
           AND LOWER(COALESCE(b.category, '')) <> 'addon'
           AND LOWER(COALESCE(b.bouquet_type, '')) <> 'addon'
         GROUP BY
            oi.bouquet_id,
            COALESCE(b.name, oi.snapshot_name, 'Bouquet'),
            COALESCE(b.stock, 0),
            COALESCE(b.category, ''),
            COALESCE(b.bouquet_type, '')
         HAVING sold_qty > 0
         ORDER BY sold_qty DESC, revenue DESC, name ASC
         LIMIT 6"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    return array_map(function ($r) {
        $soldQty = (int) $r['sold_qty'];

        return [
            'name'      => $r['name'],
            'stock'     => (int) $r['stock'],
            'category'  => $r['category'],
            'type'      => $r['bouquet_type'],
            'sold_qty'  => $soldQty,
            'revenue'   => (float) $r['revenue'],
            'chart_val' => $soldQty,
        ];
    }, $rows);
}

/**
 * Bouquets and products approaching or past their best-before date.
 * Returns items where best_before is within the next 5 days or already past.
 */
function getFreshnessAlerts(PDO $db): array {
    // Bouquets
    $stmtB = $db->prepare(
        "SELECT name AS name, stock, best_before, 'bouquet' AS item_type,
                DATEDIFF(best_before, CURDATE()) AS days_left
         FROM bouquet
         WHERE status = 'active'
           AND best_before IS NOT NULL
           AND best_before <= DATE_ADD(CURDATE(), INTERVAL 5 DAY)
         ORDER BY best_before ASC
         LIMIT 10"
    );
    $stmtB->execute();
    $bouquets = $stmtB->fetchAll();

    // Products
    $stmtP = $db->prepare(
        "SELECT product_name AS name, stock, best_before_date AS best_before,
                'product' AS item_type,
                DATEDIFF(best_before_date, CURDATE()) AS days_left
         FROM product
         WHERE status = 'active'
           AND best_before_date IS NOT NULL
           AND best_before_date <= DATE_ADD(CURDATE(), INTERVAL 5 DAY)
         ORDER BY best_before_date ASC
         LIMIT 10"
    );
    $stmtP->execute();
    $products = $stmtP->fetchAll();

    $all = array_merge($bouquets, $products);
    // Sort combined list by days_left ascending
    usort($all, fn($a, $b) => (int)$a['days_left'] <=> (int)$b['days_left']);

    return array_slice($all, 0, 10);
}
