<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connection.php';

$type = strtolower(trim($_GET['type'] ?? 'bouquet'));
$id = intval($_GET['id'] ?? 0);

$product = null;
$reviews = [];
$review_count = 0;
$average_rating = 0.0;
$rating_breakdown = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalize_image_path($image) {
    if (!$image) {
        return '';
    }

    if (
        !str_starts_with($image, 'images/') &&
        !str_starts_with($image, 'uploads/') &&
        !str_starts_with($image, 'http')
    ) {
        return 'images/' . $image;
    }

    return $image;
}

if ($id > 0 && $type === 'flower') {
    $stmt = $conn->prepare("
        SELECT
            product_id AS id,
            product_name AS name,
            product_type AS type,
            product_image AS image,
            stock,
            price,
            status
        FROM product
        WHERE product_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $product = [
            'id' => intval($row['id']),
            'name' => $row['name'],
            'description' => 'Fresh individual flower stem.',
            'price' => floatval($row['price']),
            'image' => normalize_image_path($row['image']),
            'category' => 'individual',
            'variation' => $row['type'],
            'status' => $row['status'],
            'stock' => intval($row['stock']),
            'item_type' => 'flower',
            'is_flower' => true
        ];
    }
    $stmt->close();
} elseif ($id > 0) {
    $stmt = $conn->prepare("
        SELECT
            bouquet_id,
            variation,
            name,
            description,
            price,
            is_custom,
            image,
            status,
            category,
            stock
        FROM bouquet
        WHERE bouquet_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $product = [
            'id' => intval($row['bouquet_id']),
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => floatval($row['price']),
            'image' => normalize_image_path($row['image']),
            'category' => $row['category'],
            'variation' => $row['variation'],
            'status' => $row['status'],
            'stock' => intval($row['stock']),
            'is_custom' => intval($row['is_custom']),
            'item_type' => 'bouquet',
            'is_flower' => false
        ];
    }
    $stmt->close();

    if ($product) {
        $summary_stmt = $conn->prepare("
            SELECT
                COUNT(r.review_id) AS review_count,
                COALESCE(AVG(r.rating), 0) AS average_rating
            FROM reviews r
            INNER JOIN order_item oi ON r.order_item_id = oi.order_item_id
            WHERE oi.bouquet_id = ?
        ");
        $summary_stmt->bind_param("i", $id);
        $summary_stmt->execute();
        $summary_result = $summary_stmt->get_result();

        if ($summary_result && $summary = $summary_result->fetch_assoc()) {
            $review_count = intval($summary['review_count']);
            $average_rating = round(floatval($summary['average_rating']), 1);
        }
        $summary_stmt->close();

        $breakdown_stmt = $conn->prepare("
            SELECT r.rating, COUNT(*) AS total
            FROM reviews r
            INNER JOIN order_item oi ON r.order_item_id = oi.order_item_id
            WHERE oi.bouquet_id = ?
            GROUP BY r.rating
        ");
        $breakdown_stmt->bind_param("i", $id);
        $breakdown_stmt->execute();
        $breakdown_result = $breakdown_stmt->get_result();

        while ($breakdown_result && $row = $breakdown_result->fetch_assoc()) {
            $rating = max(1, min(5, intval($row['rating'])));
            $rating_breakdown[$rating] = intval($row['total']);
        }
        $breakdown_stmt->close();

        $review_stmt = $conn->prepare("
            SELECT
                r.review_id,
                r.rating,
                r.review_text,
                u.first_name,
                u.last_name
            FROM reviews r
            INNER JOIN order_item oi ON r.order_item_id = oi.order_item_id
            LEFT JOIN `user` u ON r.user_id = u.user_id
            WHERE oi.bouquet_id = ?
            ORDER BY r.review_id DESC
            LIMIT 20
        ");
        $review_stmt->bind_param("i", $id);
        $review_stmt->execute();
        $review_result = $review_stmt->get_result();

        while ($review_result && $row = $review_result->fetch_assoc()) {
            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $reviews[] = [
                'id' => intval($row['review_id']),
                'rating' => intval($row['rating']),
                'text' => $row['review_text'],
                'customer' => $name !== '' ? $name : 'FleurChase Customer'
            ];
        }
        $review_stmt->close();
    }
}

$conn->close();

$page_title = $product ? $product['name'] : 'Product not found';
$is_available = $product && intval($product['stock']) > 0 && strtolower((string)$product['status']) !== 'inactive';
$star_fill_percent = $average_rating > 0 ? max(0, min(100, ($average_rating / 5) * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>FleurChase - <?php echo h($page_title); ?></title>
<link rel="stylesheet" href="shared.css"/>
<style>
.detail-shell{max-width:1080px;margin:0 auto;padding:1.4rem 1.5rem 2rem}
.crumb{display:inline-flex;align-items:center;gap:6px;color:var(--muted);font-size:12px;text-decoration:none;margin-bottom:1rem}
.detail-grid{display:grid;grid-template-columns:minmax(280px,430px) 1fr;gap:1.5rem;align-items:start}
.media-panel{background:white;border:1px solid var(--line);border-radius:var(--rl);overflow:hidden}
.product-img{aspect-ratio:1/1;background:var(--soft);display:flex;align-items:center;justify-content:center}
.product-img img{width:100%;height:100%;object-fit:cover;display:block}
.placeholder-flower{font-size:76px;color:var(--muted)}
.info-panel{background:white;border:1px solid var(--line);border-radius:var(--rl);padding:1.3rem}
.tag-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:.8rem}
.mini-tag{font-size:10px;text-transform:uppercase;letter-spacing:.5px;font-weight:800;border-radius:999px;padding:5px 9px;background:var(--g9);color:var(--g2)}
.mini-tag.soft{background:var(--soft);color:var(--muted)}
.pd-name{font-family:var(--font-d);font-size:34px;line-height:1.05;color:var(--g1);margin-bottom:.65rem}
.rating-line{display:flex;align-items:center;gap:10px;margin-bottom:1rem;color:var(--muted);font-size:13px}
.stars{position:relative;display:inline-block;color:#D8D3C9;font-size:18px;line-height:1;letter-spacing:1px}
.stars::before{content:"★★★★★"}
.stars-fill{position:absolute;left:0;top:0;white-space:nowrap;overflow:hidden;color:#D4891A}
.stars-fill::before{content:"★★★★★"}
.price-row{display:flex;align-items:baseline;gap:10px;margin:.8rem 0 1rem}
.pd-price{font-size:28px;font-weight:800;color:var(--g3)}
.stock-pill{font-size:12px;font-weight:700;color:var(--g2);background:var(--g9);padding:5px 9px;border-radius:999px}
.stock-pill.out{background:var(--p9);color:var(--p3)}
.desc{font-size:14px;color:var(--ink);line-height:1.7;margin:0 0 1rem}
.facts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:1rem 0}
.fact{border:1px solid var(--line);border-radius:var(--r);padding:10px;background:var(--soft)}
.fact span{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);font-weight:800;margin-bottom:3px}
.fact strong{font-size:13px;color:var(--ink)}
.action-bar{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:6rem}
.reviews-grid{display:grid;grid-template-columns:280px 1fr;gap:1.2rem;margin-top:1.5rem}
.review-summary,.review-list{background:white;border:1px solid var(--line);border-radius:var(--rl);padding:1.2rem}
.summary-score{font-size:40px;font-weight:800;color:var(--g1);line-height:1}
.bar-row{display:grid;grid-template-columns:26px 1fr 28px;gap:8px;align-items:center;font-size:12px;color:var(--muted);margin-top:8px}
.bar{height:8px;background:var(--soft);border-radius:999px;overflow:hidden}
.bar-fill{height:100%;background:var(--g4)}
.review-card{border-bottom:1px solid var(--line);padding:1rem 0}
.review-card:first-child{padding-top:0}
.review-card:last-child{border-bottom:none;padding-bottom:0}
.review-head{display:flex;justify-content:space-between;gap:12px;margin-bottom:6px}
.review-name{font-size:13px;font-weight:800;color:var(--ink)}
.review-text{font-size:13px;color:var(--muted);line-height:1.6}
.empty-note{font-size:13px;color:var(--muted);line-height:1.6}
@media(max-width:820px){.detail-grid,.reviews-grid{grid-template-columns:1fr}.pd-name{font-size:28px}.action-bar{grid-template-columns:1fr}.detail-shell{padding:1rem}}
</style>
</head>
<body>
<nav class="app-nav" id="top-nav"></nav>
<div class="main-area no-sidebar">
  <main class="detail-shell">

    <?php if (!$product): ?>
      <div class="empty-state">
        <div class="ei">?</div>
        <h3>Product not found</h3>
        <p>This item may have been removed from the catalog.</p>
        <a class="btn btn-green" href="shop.html" style="margin-top:1rem;text-decoration:none">Return to Shop</a>
      </div>
    <?php else: ?>
      <section class="detail-grid">
        <div class="media-panel">
          <div class="product-img">
            <?php if (!empty($product['image'])): ?>
              <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['name']); ?>">
            <?php else: ?>
              <div class="placeholder-flower">FleurChase</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="info-panel">
          <div class="tag-row">
            <span class="mini-tag"><?php echo h($product['category'] ?: 'Bouquet'); ?></span>
            <?php if (!empty($product['variation'])): ?>
              <span class="mini-tag soft"><?php echo h($product['variation']); ?></span>
            <?php endif; ?>
          </div>

          <h1 class="pd-name"><?php echo h($product['name']); ?></h1>

          <div class="rating-line">
            <span class="stars"><span class="stars-fill" style="width:<?php echo h($star_fill_percent); ?>%"></span></span>
            <strong><?php echo h($average_rating > 0 ? number_format($average_rating, 1) : 'No rating yet'); ?></strong>
            <span><?php echo h($review_count); ?> review<?php echo $review_count === 1 ? '' : 's'; ?></span>
          </div>

          <div class="price-row">
            <div class="pd-price">₱<?php echo h(number_format($product['price'], 0)); ?></div>
            <span class="stock-pill <?php echo $is_available ? '' : 'out'; ?>">
              <?php echo $is_available ? h($product['stock'] . ' in stock') : 'Out of stock'; ?>
            </span>
          </div>

          <p class="desc"><?php echo h($product['description'] ?: 'A handcrafted FleurChase arrangement prepared fresh for your order.'); ?></p>

          <div class="facts">
            <div class="fact"><span>Category</span><strong><?php echo h($product['category'] ?: 'Ready-made'); ?></strong></div>
            <div class="fact"><span>Stock</span><strong><?php echo h($product['stock']); ?> item<?php echo intval($product['stock']) === 1 ? '' : 's'; ?></strong></div>
          </div>

          <div class="action-bar">
            <button class="btn btn-outline" onclick="addDetailToCart()" <?php echo !$is_available ? 'disabled' : ''; ?>>Add to Cart</button>
            <button class="btn btn-green" onclick="buyNow()" <?php echo !$is_available ? 'disabled' : ''; ?>>Buy Now</button>
          </div>
        </div>
      </section>

      <section class="reviews-grid">
        <aside class="review-summary">
          <div style="font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px">Customer Rating</div>
          <div class="summary-score"><?php echo h($average_rating > 0 ? number_format($average_rating, 1) : '0.0'); ?></div>
          <div class="rating-line" style="margin:.35rem 0 .8rem">
            <span class="stars"><span class="stars-fill" style="width:<?php echo h($star_fill_percent); ?>%"></span></span>
          </div>
          <?php for ($rating = 5; $rating >= 1; $rating--): ?>
            <?php $percent = $review_count > 0 ? ($rating_breakdown[$rating] / $review_count) * 100 : 0; ?>
            <div class="bar-row">
              <span><?php echo $rating; ?> star</span>
              <div class="bar"><div class="bar-fill" style="width:<?php echo h($percent); ?>%"></div></div>
              <span><?php echo h($rating_breakdown[$rating]); ?></span>
            </div>
          <?php endfor; ?>
        </aside>

        <div class="review-list">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h2 style="font-size:16px;color:var(--ink);margin:0">Reviews</h2>
            <span style="font-size:12px;color:var(--muted)"><?php echo h($review_count); ?> total</span>
          </div>

          <?php if (empty($reviews)): ?>
            <div class="empty-note">No customer reviews yet. After customers receive an order, their reviews for this bouquet will appear here.</div>
          <?php else: ?>
            <?php foreach ($reviews as $review): ?>
              <article class="review-card">
                <div class="review-head">
                  <div class="review-name"><?php echo h($review['customer']); ?></div>
                  <div class="stars" style="font-size:14px"><span class="stars-fill" style="width:<?php echo h(($review['rating'] / 5) * 100); ?>%"></span></div>
                </div>
                <div class="review-text"><?php echo h($review['text']); ?></div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>
  <div id="footer-wrap"></div>
</div>
<div id="fc-toast" class="toast"></div>
<script src="data.js"></script>
<script src="nav.js"></script>
<script>
const PRODUCT = <?php echo json_encode($product); ?>;

const user = requireAuth('customer');
if (user) {
  buildTopNav('shop');
  renderFooter('footer-wrap', false);
}

async function addDetailToCart() {
  if (!PRODUCT) return false;

  const user = FC.getUser();
  const userId = user?.user_id || user?.id;

  if (!userId) {
    toast('Please sign in before adding items to your cart.', 'err');
    return false;
  }

  try {
    const response = await fetch('add_to_cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: userId,
        item_type: PRODUCT.item_type,
        bouquet_id: PRODUCT.is_flower ? null : PRODUCT.id,
        product_id: PRODUCT.is_flower ? PRODUCT.id : null,
        unit_price: PRODUCT.price,
        quantity: 1
      })
    });

    const result = await response.json();

    if (!result.success) {
      toast('Failed to add item: ' + (result.error || 'Unknown error'), 'warn');
      return false;
    }

    const cart = FC.getCart();
    const cartId = (PRODUCT.is_flower ? 'product_' : 'bouquet_') + String(PRODUCT.id);
    const existing = cart.find(item => String(item.cartId) === cartId);

    if (existing) {
      existing.qty += 1;
    } else {
      cart.push({
        cartId: cartId,
        productId: PRODUCT.id,
        bouquet_id: PRODUCT.is_flower ? null : PRODUCT.id,
        product_id: PRODUCT.is_flower ? PRODUCT.id : null,
        item_type: PRODUCT.item_type,
        name: PRODUCT.name,
        img: PRODUCT.image || null,
        price: Number(PRODUCT.price),
        qty: 1,
        checked: true
      });
    }

    FC.saveCart(cart);
    if (typeof updateCartBadge === 'function') updateCartBadge();
    toast('Added to your cart!');
    return true;
  } catch (error) {
    console.error(error);
    toast('Connection to server failed.', 'warn');
    return false;
  }
}

async function buyNow() {
  if (!PRODUCT) return;

  const user = FC.getUser();
  if (!user) {
    toast('Please sign in before buying this item.', 'err');
    return;
  }

  sessionStorage.setItem('fc_checkout_items', JSON.stringify([{
    cartId: (PRODUCT.is_flower ? 'product_' : 'bouquet_') + String(PRODUCT.id),
    productId: PRODUCT.id,
    bouquet_id: PRODUCT.is_flower ? null : PRODUCT.id,
    product_id: PRODUCT.is_flower ? PRODUCT.id : null,
    item_type: PRODUCT.item_type,
    name: PRODUCT.name,
    img: PRODUCT.image || null,
    price: Number(PRODUCT.price),
    qty: 1,
    checked: true
  }]));
  sessionStorage.setItem('fc_checkout_promo', '');
  location.href = 'checkout.html';
}
</script>
</body>
</html>
