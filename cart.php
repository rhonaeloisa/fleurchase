<?php
// 1. DATABASE CONNECTION & SERVER-SIDE CART FETCHING
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connection.php'; 

// Resolve the logged-in user from the PHP session created during login/add-to-cart.
$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0; 

// LOOK UP THE CORRECT CART ID GENERATED FOR THIS LOGGED-IN ACCOUNT PROFILE
$current_cart_id = 0;
$cart_lookup_stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? LIMIT 1");
$cart_lookup_stmt->bind_param("i", $current_user_id);
$cart_lookup_stmt->execute();
$cart_lookup_res = $cart_lookup_stmt->get_result();

if ($cart_lookup_res && $cart_lookup_res->num_rows > 0) {
    $cart_lookup_row = $cart_lookup_res->fetch_assoc();
    $current_cart_id = intval($cart_lookup_row['cart_id']);
}
$cart_lookup_stmt->close();

// SQL Query joining cart_item with the bouquet catalog table matching our live user's cart container index
$sql = "
    SELECT ci.*, 
          b.name AS bouquet_name, 
          b.description AS bouquet_desc, 
          b.image AS bouquet_img,
          b.bouquet_id,
          p.product_name,
          p.product_type,
          p.product_image
    FROM cart_item ci
    LEFT JOIN bouquet b ON ci.bouquet_id = b.bouquet_id
    LEFT JOIN product p ON ci.product_id = p.product_id
    WHERE ci.cart_id = ?
    ORDER BY ci.cart_item_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_cart_id);
$stmt->execute();
$result = $stmt->get_result();

$db_cart_items = [];

while($row = $result->fetch_assoc()) {
    $isCustom = !empty($row['is_custom']) && $row['is_custom'] == 1;
    $customData = null;
    
    if ($isCustom && !empty($row['custom_data'])) {
        $customData = json_decode($row['custom_data'], true);
    }

    if ($isCustom && $customData) {
        // ====================== CUSTOM BOUQUET ======================
        $name = "Custom Bouquet (" . ($customData['size'] ?? 'Medium') . ")";

        $sub = ($customData['stems'] ?? 0) . " stems • " . 
               ($customData['color'] ?? 'No color') . "<br>" .
               ($customData['flowers'] ?? 'Mixed flowers');

        $price = floatval($row['unit_price']);
        $imagePath = "images/default.jpg"; 
    } 
    elseif (!empty($row['product_id'])) {
        // ====================== FLOWER / PRODUCT ITEM ======================
        $name = $row['product_name'] ?? 'Fresh Flower Stem';
        $sub  = 'Fresh ' . ($row['product_type'] ?? 'flower') . ' item.';
        $price = floatval($row['unit_price']);
        $imagePath = $row['product_image'] ?? '';
    }
    else {
        // ====================== NORMAL BOUQUET ======================
        $name = $row['bouquet_name'] ?? 'Premium Flower Arrangement';
        $sub  = $row['bouquet_desc'] ?? 'Handcrafted local bouquet choice.';
        $price = floatval($row['unit_price']);
        $imagePath = $row['bouquet_img'] ?? '';
    }

    // Image path correction
    if ($imagePath && !str_starts_with($imagePath, "images/") && !str_starts_with($imagePath, "uploads/") && !str_starts_with($imagePath, "http")) {
        $imagePath = "images/" . $imagePath;
    }

    $db_cart_items[] = [
        'cart_item_id' => intval($row['cart_item_id']),
        'cart_id'      => intval($row['cart_id']),
        'bouquet_id'   => intval($row['bouquet_id'] ?? 0),
        'product_id'   => intval($row['product_id'] ?? 0),
        'item_type'    => !empty($row['product_id']) ? 'flower' : 'bouquet',
        'qty'          => intval($row['quantity']),
        'price'        => $price,           
        'name'         => $name,
        'sub'          => $sub,
        'img'          => $imagePath,
        'checked'      => true,
        'is_custom'    => $isCustom,
        'custom_data'  => $customData
    ];
}

// FETCH ACTIVE VOUCHERS PRE-FILTERED TO EXCLUDE CODES ALREADY CLAIMED BY THIS USER
// FIXED: Runs an anti-join subquery checking against user_promo records to prevent reuse options early
$promo_sql = "
    SELECT p.* FROM promos p
    WHERE p.status = 'active'
      AND p.promo_id NOT IN (
          SELECT up.promo_id 
          FROM user_promo up 
          WHERE up.user_id = ? AND up.status = 'used'
      )
";

$promo_stmt = $conn->prepare($promo_sql);
$promo_stmt->bind_param("i", $current_user_id);
$promo_stmt->execute();
$promo_result = $promo_stmt->get_result();

$db_active_promos = [];

if ($promo_result && $promo_result->num_rows > 0) {
    while($row = $promo_result->fetch_assoc()) {
        $db_active_promos[] = [
            'promo_id'         => intval($row['promo_id']),
            'code'             => $row['code'],
            'name'             => $row['promo_name'],
            'description'      => $row['description'],
            'type'             => $row['discount_type'],
            'value'            => floatval($row['discount_value']),
            'min_order_amount' => floatval($row['min_order_amount']),
            'start_date'       => $row['start_date'],
            'end_date'         => $row['end_date']
        ];
    }
}
$promo_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>FleurChase — My Cart</title>
<link rel="stylesheet" href="shared.css"/>
<style>
.cart-wrap{max-width:980px;margin:0 auto;padding:2rem 1.5rem}
.cart-layout{display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start}
.cart-header-row{display:flex;align-items:center;gap:12px;padding:10px 14px;background:white;border-radius:var(--r);border:1px solid var(--line);margin-bottom:10px;font-size:13px;font-weight:600;color:var(--muted)}
.ci-wrap{background:white;border-radius:var(--rl);border:1px solid var(--line);margin-bottom:10px;overflow:hidden;transition:box-shadow .2s}
.ci-wrap:hover{box-shadow:0 3px 14px rgba(0,0,0,.07)}.ci-wrap.selected{border-color:var(--g4);border-width:2px}
.ci{display:flex;align-items:center;gap:12px;padding:1rem 1.1rem}
.ci-img{font-size:32px;width:56px;height:56px;background:var(--soft);border-radius:var(--r);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden}
.ci-img img{width:100%;height:100%;object-fit:cover}
.ci-info{flex:1;min-width:0}
.ci-name{font-size:13px;font-weight:600;color:var(--ink)}
.ci-sub{font-size:11px;color:var(--muted);margin-top:1px;line-height:1.4}
.ci-price{font-size:14px;font-weight:700;color:var(--g3);flex-shrink:0;min-width:70px;text-align:right}
.ci-del{background:none;border:none;color:var(--muted);font-size:14px;cursor:pointer;padding:4px 6px;border-radius:6px;transition:all .2s;flex-shrink:0}
.ci-del:hover{color:var(--p3);background:var(--p9)}
.qty-row{display:flex;align-items:center;gap:6px;margin-top:8px}
.qbtn{width:30px;height:30px;border-radius:8px;border:1.5px solid var(--line);background:var(--soft);cursor:pointer;font-size:16px;font-weight:700;display:flex;align-items:center;justify-content:center;transition:background .15s;color:var(--ink);flex-shrink:0;user-select:none;-webkit-user-select:none}
.qbtn:hover{background:var(--g8);border-color:var(--g5)}.qbtn:active{background:var(--g7)}
.qval{font-size:14px;font-weight:700;min-width:30px;text-align:center;color:var(--ink)}
.oc{background:white;border-radius:var(--rl);border:1px solid var(--line);padding:1.4rem;position:sticky;top:calc(var(--nh)+16px)}
.oc h3{font-size:14px;font-weight:700;color:var(--ink);margin-bottom:1rem}
.oc-row{display:flex;justify-content:space-between;font-size:13px;color:var(--muted);padding:5px 0}
.oc-row.disc{color:var(--p3);font-weight:600}
.oc-total{display:flex;justify-content:space-between;font-size:16px;font-weight:700;padding:10px 0 0;border-top:1.5px solid var(--line);margin-top:4px;color:var(--ink)}
.deposit-info{background:var(--g9);border-radius:var(--r);padding:10px;font-size:12px;color:var(--g2);margin:10px 0;line-height:1.6;border:1px solid var(--g7)}
.select-count{font-size:12px;color:var(--g3);font-weight:600;margin-top:8px;text-align:center}
.promo-input-row{display:flex;gap:8px;margin-top:8px}
.promo-input-row input{flex:1;border:1.5px solid var(--line);border-radius:var(--r);padding:8px 12px;font-family:var(--font-b);font-size:13px;outline:none;transition:border-color .2s;background:var(--soft)}
.promo-input-row input:focus{border-color:var(--g4)}
.promo-result{font-size:12px;margin-top:5px;line-height:1.5}
.promo-ok{color:var(--g3)}.promo-fail{color:var(--p3)}
.auto-promo{background:var(--g9);border:1px solid var(--g6);border-radius:var(--r);padding:8px 10px;font-size:12px;color:var(--g2);margin-top:8px;display:none}
@media(max-width:760px){.cart-layout{grid-template-columns:1fr}}
</style>
</head>
<body>
<div id="top-nav" class="app-nav"></div>
<div class="main-area no-sidebar">
<div class="cart-wrap">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
    <div class="page-title" style="margin-bottom:0">My <span style="color:var(--p3);font-style:italic">Cart</span></div>
    <button class="btn btn-ghost btn-sm" onclick="clearCart()">Clear All</button>
  </div>

  <div class="cart-layout">
    <div>
      <div class="cart-header-row">
        <input type="checkbox" class="fc-checkbox" id="select-all" checked onchange="toggleSelectAll(this.checked)"/>
        <label for="select-all" style="cursor:pointer;color:var(--ink);font-weight:600">Select All</label>
        <span style="margin-left:auto;font-size:12px;color:var(--muted)" id="sel-summary">0 items selected</span>
      </div>
      <div id="cart-items"></div>
    </div>

    <div class="oc">
      <h3>Order Summary</h3>
      <div class="oc-row"><span>Items selected</span><span id="os-count">0</span></div>
      <div class="oc-row"><span>Subtotal</span><span id="os-sub">₱0</span></div>
      <div class="oc-row disc" id="os-disc-row" style="display:none">
        <span id="os-disc-label">🏷️ Promo</span><span id="os-disc">−₱0</span>
      </div>
      <div class="oc-row"><span>Delivery</span><span style="color:var(--muted)">Calc. at checkout</span></div>
      <div class="oc-total"><span>Estimated Total</span><span id="os-total">₱0</span></div>
      <div class="deposit-info" id="os-deposit" style="display:none">
        50% deposit: <strong id="os-dep-amt">₱0</strong><br>
        Balance on delivery: <strong id="os-bal-amt">₱0</strong>
      </div>
      <div class="auto-promo" id="auto-promo"></div>
      <div class="divider"></div>
      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Promo Code</div>
      <div class="promo-input-row">
        <input id="promo-code" placeholder="Enter code..."/>
        <button class="btn btn-green btn-sm" onclick="applyPromoCode()">Apply</button>
      </div>
      <div class="promo-result" id="promo-result"></div>
      <div class="divider"></div>
      <button class="btn btn-green" style="width:100%;justify-content:center;padding:13px;margin-bottom:8px" onclick="proceedCheckout()">Checkout Selected</button>
      <a class="btn btn-ghost" href="shop.html" style="width:100%;justify-content:center;text-decoration:none">Continue Shopping</a>
      <div class="select-count" id="checkout-hint"></div>
    </div>
  </div>
</div>
<div id="footer-container"></div>
</div>
<div id="fc-toast" class="toast"></div>

<script src="data.js"></script>
<script src="nav.js"></script>
<script>
requireAuth('customer');
buildTopNav('cart');
renderFooter('footer-container', false);

let currentCartId = <?php echo $current_cart_id; ?>;
let cartItemsArray = <?php echo json_encode($db_cart_items); ?>;
let availablePromos = <?php echo json_encode($db_active_promos); ?>;

let appliedPromo = null;
let manualPromoUsed = false;

function toLocalCartItem(item) {
  const isFlower = Number(item.product_id || 0) > 0;
  const sourceId = isFlower ? item.product_id : item.bouquet_id;

  return {
    cart_item_id: item.cart_item_id,
    cartId: (isFlower ? 'product_' : 'bouquet_') + String(sourceId),
    productId: sourceId,
    bouquet_id: isFlower ? null : item.bouquet_id,
    product_id: isFlower ? item.product_id : null,
    item_type: isFlower ? 'flower' : 'bouquet',
    name: item.name,
    img: item.img || null,
    price: Number(item.price),
    qty: item.qty,
    checked: item.checked
  };
}

// ── RENDER CART ─────────────────────────────────────────────
function renderCart() {
  const el = document.getElementById('cart-items');

  if (!cartItemsArray.length) {
    el.innerHTML = `<div class="empty-state"><div class="ei">🌸</div><h3>Your cart is empty</h3><p>Browse our shop and add some beautiful flowers!</p><a class="btn btn-green" href="shop.html" style="margin-top:1rem;text-decoration:none">Shop Now</a></div>`;
    updateSummary();
    return;
  }

  el.innerHTML = cartItemsArray.map((item, i) => {
    const imgHtml = item.img 
      ? `<img src="${item.img}" alt="${item.name}" style="width:100%;height:100%;object-fit:cover;display:block"/>`
      : imgPlaceholder(48);

   return `
  <div class="ci-wrap ${item.checked?'selected':''}" id="ciw-${i}">
    <div class="ci">
      <input type="checkbox" class="fc-checkbox" id="chk-${i}" ${item.checked?'checked':''} onchange="toggleItem(${i},this.checked)"/>
      <div class="ci-img">${imgHtml}</div>
      <div class="ci-info">
        <div class="ci-name">${item.name}</div>
        <div class="ci-sub" style="line-height:1.4">${item.sub||''}</div>
        ${item.is_custom ? `
          <div style="font-size:11px;color:var(--p3);margin-top:4px;">
            ✨ Custom Arrangement
          </div>
        ` : ''}
        <div class="qty-row">
          <button class="qbtn" onclick="changeQty(${i},-1)">−</button>
          <span class="qval" id="qty-${i}">${item.qty}</span>
          <button class="qbtn" onclick="changeQty(${i},1)">+</button>
          <span style="font-size:11px;color:var(--muted)">pcs</span>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:3px;flex-shrink:0">
        <div class="ci-price" id="price-${i}">${fmtP(item.price*item.qty)}</div>
      </div>
      <button class="ci-del" onclick="removeItem(${i})">✕</button>
    </div>
  </div>`;
  }).join('');

  updateSummary();
  syncSelectAll();
  if (!manualPromoUsed) autoApplyPromo();
}

async function changeQty(i, delta) {
  if (!cartItemsArray[i]) return;
  const targetItem = cartItemsArray[i];
  if (targetItem.qty === 1 && delta === -1) return;

  try {
    const response = await fetch('cart_actions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'change_qty', cart_item_id: targetItem.cart_item_id, delta: delta })
    });
    const res = await response.json();
    if (res.success) {
      targetItem.qty += delta;
      document.getElementById('qty-' + i).textContent = targetItem.qty;
      document.getElementById('price-' + i).textContent = fmtP(targetItem.price * targetItem.qty);
      
      const updatedLocal = cartItemsArray.map(toLocalCartItem);
      FC.saveCart(updatedLocal); 

      updateSummary();
      if (!manualPromoUsed) autoApplyPromo();
    }
  } catch(err) {
    console.error(err);
  }
}

function toggleItem(i, checked) {
  if (!cartItemsArray[i]) return;
  cartItemsArray[i].checked = checked;
  document.getElementById('ciw-'+i)?.classList.toggle('selected', checked);
  updateSummary();
  syncSelectAll();
  if (!manualPromoUsed) autoApplyPromo();
}

function toggleSelectAll(checked) {
  cartItemsArray.forEach(item => item.checked = checked);
  renderCart();
}

function syncSelectAll() {
  const sa = document.getElementById('select-all');
  if (!sa) return;
  const all = cartItemsArray.length>0 && cartItemsArray.every(i=>i.checked);
  const none = cartItemsArray.every(i=>!i.checked);
  sa.checked = all;
  sa.indeterminate = !all && !none;
  const sel = cartItemsArray.filter(i=>i.checked);
  document.getElementById('sel-summary').textContent = sel.length+' of '+cartItemsArray.length+' item'+(cartItemsArray.length!==1?'s':'')+' selected';
}

async function removeItem(i) {
  if (!cartItemsArray[i]) return;
  try {
    const response = await fetch('cart_actions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'remove_item', cart_item_id: cartItemsArray[i].cart_item_id })
    });
    const res = await response.json();
    if(res.success) {
      cartItemsArray.splice(i, 1); 
      
      const updatedLocal = cartItemsArray.map(toLocalCartItem);
      FC.saveCart(updatedLocal); 

      renderCart(); 
      toast('Cart item deleted.');
    }
  } catch(err) {
    console.error(err);
  }
}

async function clearCart() {
  if (!confirm('Remove all items from your database cart context?')) return;
  try {
    const response = await fetch('cart_actions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'clear_all', cart_id: currentCartId })
    });
    const res = await response.json();
    if(res.success) {
      cartItemsArray = [];
      renderCart();
      toast('Database cart storage cleared.');
    }
  } catch(err) {
    console.error(err);
  }
}

function getSelectedSub() {
  return cartItemsArray.filter(i=>i.checked).reduce((s,i)=>s+i.price*i.qty,0);
}

function updateSummary() {
  const selected = cartItemsArray.filter(i=>i.checked);
  const count = selected.length;
  const sub = getSelectedSub();

  let discount = 0;
  if (appliedPromo) {
    if (appliedPromo.type==='percent') discount = Math.round(sub*appliedPromo.value/100);
    else if (appliedPromo.type==='fixed') discount = Math.min(appliedPromo.value, sub);
  }
  const finalSub = sub - discount;

  document.getElementById('os-count').textContent = count;
  document.getElementById('os-sub').textContent = fmtP(sub);

  const discRow = document.getElementById('os-disc-row');
  if (discount > 0) {
    discRow.style.display = 'flex';
    document.getElementById('os-disc-label').textContent = '🏷️ '+(appliedPromo?.name||'Promo');
    document.getElementById('os-disc').textContent = '−'+fmtP(discount);
  } else discRow.style.display = 'none';

  document.getElementById('os-total').textContent = fmtP(finalSub);

  const currentDbCount = cartItemsArray.reduce((total, item) => total + item.qty, 0);
  document.querySelectorAll('.cart-count').forEach(el => el.textContent = currentDbCount);

  const depBox = document.getElementById('os-deposit');
  if (count > 0) {
    depBox.style.display = 'block';
    document.getElementById('os-dep-amt').textContent = fmtP(Math.ceil(finalSub/2));
    document.getElementById('os-bal-amt').textContent = fmtP(Math.floor(finalSub/2));
  } else depBox.style.display = 'none';

  document.getElementById('checkout-hint').textContent = count===0
    ? 'Select items to checkout'
    : count+' item'+(count!==1?'s':'')+' will be checked out';
}

function validateLocalPromo(promoCode, subtotal) {
  const pr = availablePromos.find(p => p.code.toLowerCase() === promoCode.toLowerCase());
  
  if (!pr) return { ok: false, discount: 0, reason: 'Promo code already used or not found.' };
  
  const today = new Date(); 
  today.setHours(0,0,0,0);
  
  if (pr.start_date) { 
    const s = new Date(pr.start_date); 
    if (today < s) return { ok: false, discount: 0, reason: 'This promo is not active yet.' }; 
  }
  if (pr.end_date) { 
    const e = new Date(pr.end_date);   
    if (today > e) return { ok: false, discount: 0, reason: 'This promo has expired.' }; 
  }
  if (pr.min_order_amount && subtotal < pr.min_order_amount) {
    return { ok: false, discount: 0, reason: 'Minimum order of ₱' + pr.min_order_amount + ' required.' };
  }
  
  let disc = 0;
  if (pr.type === 'percent') {
    disc = Math.round(subtotal * pr.value / 100);
  } else if (pr.type === 'fixed') {
    disc = Math.min(pr.value || 0, subtotal);
  }
  
  return { ok: true, discount: disc, promo: pr };
}

function autoApplyPromo() {
  const selected = cartItemsArray.filter(i=>i.checked);
  if (!selected.length) { appliedPromo = null; updateSummary(); return; }
  const sub = getSelectedSub();

  let best = null, bestDisc = 0;
  availablePromos.forEach(p => {
    const r = validateLocalPromo(p.code, sub);
    if (r.ok && r.discount > bestDisc) { bestDisc = r.discount; best = r.promo; }
  });

  const noticeEl = document.getElementById('auto-promo');
  if (best) {
    appliedPromo = best;
    noticeEl.style.display = 'block';
    noticeEl.innerHTML = `🏷️ <strong>${best.name}</strong> auto-applied!`;
  } else {
    appliedPromo = null;
    noticeEl.style.display = 'none';
  }
  updateSummary();
}

function applyPromoCode() {
  const code = document.getElementById('promo-code').value.trim().toUpperCase();
  if (!code) { toast('Please enter a promo code.','warn'); return; }
  const selected = cartItemsArray.filter(i=>i.checked);
  if (!selected.length) { toast('Select items first.','warn'); return; }
  
  const sub = getSelectedSub();
  const resEl = document.getElementById('promo-result');
  const result = validateLocalPromo(code, sub);
  
  if (result.ok) {
    manualPromoUsed = true;
    appliedPromo = result.promo;
    document.getElementById('auto-promo').style.display = 'none';
    resEl.className = 'promo-result promo-ok';
    resEl.innerHTML = '✓ <strong>'+result.promo.name+'</strong> applied!';
    toast('🏷️ Promo applied!');
    updateSummary();
  } else {
    manualPromoUsed = false;
    appliedPromo = null;
    resEl.className = 'promo-result promo-fail';
    resEl.innerHTML = '✕ '+result.reason;
    autoApplyPromo();
  }
}

function proceedCheckout() {
  const selected = cartItemsArray.filter(i=>i.checked);
  if (!selected.length) { toast('Please select at least one item.','warn'); return; }
  sessionStorage.setItem('fc_checkout_items', JSON.stringify(selected));
  sessionStorage.setItem('fc_checkout_promo', appliedPromo ? JSON.stringify(appliedPromo) : '');
  location.href = 'checkout.html';
}

const syncedLocalItems = cartItemsArray.map(toLocalCartItem);

FC.saveCart(syncedLocalItems); 
renderCart();
</script>
</body>
</html>
