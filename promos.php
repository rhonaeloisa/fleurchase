<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>FleurChase — Promos & Sales</title>
<link rel="stylesheet" href="shared.css"/>
<style>
.promo-hero{background:linear-gradient(135deg,var(--p9) 0%,var(--p8) 50%,var(--p9) 100%);padding:2rem 2.5rem;border-bottom:1px solid var(--p7);position:relative;overflow:hidden}
.promo-hero::before{content:'🌸';position:absolute;font-size:140px;opacity:.06;right:1.5rem;top:-1rem;pointer-events:none}
.ptabs{display:flex;background:var(--soft);border-radius:var(--r);padding:3px;gap:3px;width:fit-content;margin-bottom:1.5rem}
.ptab{padding:8px 20px;border-radius:8px;border:none;font-family:var(--font-b);font-size:13px;font-weight:500;cursor:pointer;background:none;color:var(--muted);transition:all .2s}
.ptab.active{background:white;color:var(--g2);box-shadow:0 1px 6px rgba(0,0,0,.08)}
/* PROMO CARDS */
.promo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:16px;margin-bottom:1.5rem}
.promo-card{background:white;border-radius:var(--rl);border:1.5px solid var(--line);padding:1.6rem;position:relative;overflow:hidden;transition:all .22s}
.promo-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.09)}
.promo-card.is-active{border-color:var(--p5);background:linear-gradient(135deg,white 60%,var(--p9) 100%)}
.promo-card.is-active::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--p3),var(--p4))}
.pc-live{display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--g3);margin-bottom:.5rem}
.live-dot{width:6px;height:6px;border-radius:50%;background:var(--g4);animation:livepulse 1.5s infinite}
@keyframes livepulse{0%,100%{opacity:1}50%{opacity:.4}}
.pc-value{font-family:var(--font-d);font-size:26px;font-weight:800;margin-bottom:.3rem}
.pc-name{font-size:16px;font-weight:700;color:var(--g1);margin-bottom:.3rem}
.pc-desc{font-size:12px;color:var(--muted);line-height:1.6;margin-bottom:.8rem}
.pc-meta{display:flex;gap:6px;flex-wrap:wrap;font-size:11px;margin-bottom:1rem}
.pc-meta span{background:var(--soft);color:var(--muted);border-radius:6px;padding:3px 8px}
.pc-code{font-family:monospace;font-size:13px;font-weight:700;letter-spacing:1px;background:var(--g9);color:var(--g2);border:1px dashed var(--g5);border-radius:8px;padding:5px 12px;display:inline-block;cursor:pointer;transition:background .2s}
.pc-code:hover{background:var(--g8)}
/* SALE CARDS */
.sale-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:12px}
.sale-card{background:white;border-radius:var(--rl);overflow:hidden;border:1px solid var(--line);transition:all .22s;cursor:pointer}
.sale-card:hover{transform:translateY(-3px);box-shadow:0 6px 18px rgba(0,0,0,.09)}
.sc-img{height:120px;display:flex;align-items:center;justify-content:center;font-size:44px;position:relative}
.sc-sale-tag{position:absolute;top:7px;left:7px;background:var(--p3);color:white;font-size:9px;font-weight:700;padding:2px 7px;border-radius:7px;text-transform:uppercase;letter-spacing:.3px}
.sc-body{padding:10px 12px;background:white}
.sc-name{font-size:12px;font-weight:600;color:var(--ink);margin-bottom:2px}
.sc-desc{font-size:10px;color:var(--muted);margin-bottom:6px;line-height:1.4}
.sc-price-row{display:flex;align-items:center;gap:5px;flex-wrap:wrap}
.sc-price{font-size:14px;font-weight:700;color:var(--p3)}
.sc-old{font-size:11px;color:var(--muted);text-decoration:line-through}
.sc-pct{font-size:9px;background:var(--p9);color:var(--p3);padding:2px 5px;border-radius:4px;font-weight:700}
/* INFO BOX */
.info-box{background:linear-gradient(135deg,var(--g9),var(--g8));border:1px solid var(--g7);border-radius:var(--rl);padding:1.4rem}
</style>
</head>
<body>
<div id="top-nav" class="app-nav"></div>

<div class="main-area no-sidebar">

<div class="promo-hero">
  <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:var(--p4);margin-bottom:.4rem">FleurChase Deals</div>
  <h2 style="font-family:var(--font-d);font-size:36px;font-weight:700;color:var(--g1);margin-bottom:.3rem">Promos <em style="color:var(--p3);font-style:italic">&amp; Sales</em></h2>
  <p style="font-size:13px;color:var(--muted);max-width:440px;line-height:1.7">Exclusive deals for every occasion: seasonal discounts, flash sales on near-expiry blooms, and bundle offers.</p>
</div>

<div class="p-page">
  <div class="ptabs">
    <button class="ptab active" id="ptab-active"   onclick="switchTab('active',this)">🏷️ Active Promos</button>
    <button class="ptab"        id="ptab-sale"     onclick="switchTab('sale',this)">🔖 On Sale</button>
    <button class="ptab"        id="ptab-upcoming" onclick="switchTab('upcoming',this)">📅 Upcoming</button>
  </div>

  <div id="tab-active">
    <div class="promo-grid" id="active-promo-grid"></div>
    <div class="info-box">
      <div style="font-size:13px;font-weight:700;color:var(--g2);margin-bottom:.6rem">💡 How to use promo codes</div>
      <ol style="font-size:12px;color:var(--g3);line-height:2;padding-left:1.2rem">
        <li>Add items to your cart</li>
        <li>Go to <strong>My Cart</strong> and find the "Promo Code" box</li>
        <li>Type or paste the code and click <strong>Apply</strong></li>
        <li>The discount will be applied instantly to your subtotal</li>
        <li>Proceed to checkout. Discount carries over!</li>
      </ol>
    </div>
  </div>

  <div id="tab-sale" style="display:none">
    <div style="background:var(--p9);border:1px solid var(--p7);border-radius:var(--r);padding:12px 16px;margin-bottom:1.2rem;font-size:13px;color:var(--p2);line-height:1.6">
      🔖 <strong>On-Sale Blooms</strong> — These flowers are discounted because they need to be used soon. Same freshness, just act fast!
    </div>
    <div class="sale-grid" id="sale-items-grid"></div>
  </div>

  <div id="tab-upcoming" style="display:none">
    <div class="promo-grid" id="upcoming-promo-grid"></div>
    <div style="background:var(--soft);border-radius:var(--rl);border:1px dashed var(--line);padding:2rem;text-align:center;margin-top:.5rem">
      <div style="font-size:32px;margin-bottom:.7rem">🔔</div>
      <div style="font-size:15px;font-weight:600;color:var(--ink);margin-bottom:.4rem">Get notified first!</div>
      <p style="font-size:13px;color:var(--muted);margin-bottom:1rem;max-width:340px;margin-left:auto;margin-right:auto">Follow us on social media to be the first to know about our upcoming deals.</p>
      <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
        <button class="btn btn-green btn-sm" onclick="toast('Follow us: /FleurChaseAlbay 📘')">📘 Facebook</button>
        <button class="btn btn-pink btn-sm"  onclick="toast('Follow us: @fleurChase.albay 📸')">📸 Instagram</button>
      </div>
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
buildTopNav('promos');
renderFooter('footer-container', false);

// Injected database array for coupon cards
let loadedPromos = [];

async function loadPromosFromDB() {
  try {
    const res = await fetch('get_promos.php');
    const data = await res.json();

    if (!data.success) {
      toast(data.message || 'Unable to load promos.', 'err');
      return;
    }

    loadedPromos = data.promos.map(p => ({
      ...p,
      code: p.code || '',
      promo_name: p.promo_name || p.name || '',
      description: p.description || p.desc || '',
      discount_type: p.discount_type || p.type || '',
      discount_value: Number(p.discount_value ?? p.value ?? 0),
      start_date: p.start_date || p.startDate || '',
      end_date: p.end_date || p.endDate || '',
      min_order_amount: Number(p.min_order_amount ?? p.minOrder ?? 0),
      status: String(p.status || '').trim().toLowerCase()
    }));

    renderActivePromos();
    renderUpcomingPromos();
    showPromoInput();
    renderSaleItems();
  } catch (error) {
    console.error(error);
    toast('Cannot fetch promos from database.', 'err');
  }
}

// Show promo input box once promos load
function showPromoInput() {
  document.getElementById('promo-input-section').style.display = 'block';
}

function handleApplyPromo() {
  const code = document.getElementById('pi-code').value.trim();
  const feedback = document.getElementById('pi-feedback');
  const discRow  = document.getElementById('pi-discount-row');

  if (!code) {
    feedback.style.display = 'block';
    feedback.style.color = 'var(--p3)';
    feedback.textContent = '⚠️ Please enter a promo code.';
    return;
  }

  // Use the existing validateLocalPromo — pass 0 as subtotal on this page
  // (actual subtotal validation happens in the cart)
  const result = validateLocalPromo(code, 0);

  if (!result.ok) {
    feedback.style.display = 'block';
    feedback.style.color = 'var(--p3)';
    feedback.textContent = '❌ ' + result.reason;
    discRow.style.display = 'none';
    return;
  }

  feedback.style.display = 'block';
  feedback.style.color = 'var(--g3)';
  feedback.textContent = '✅ "' + result.label + '" is a valid promo!';
  document.getElementById('pi-discount-label').textContent = result.label;
  document.getElementById('pi-discount-amount').textContent =
    result.promo.discount_type === 'percent'
      ? result.promo.discount_value + '% OFF'
      : '₱' + result.promo.discount_value + ' OFF';
  discRow.style.display = 'flex';
  document.getElementById('pi-code').disabled = true;
  document.getElementById('pi-apply-btn').disabled = true;
}

function removeAppliedPromo() {
  document.getElementById('pi-code').value = '';
  document.getElementById('pi-code').disabled = false;
  document.getElementById('pi-apply-btn').disabled = false;
  document.getElementById('pi-feedback').style.display = 'none';
  document.getElementById('pi-discount-row').style.display = 'none';
}


// ============================================================
// LOCALIZED PROMO VALIDATION ENGINE (Decoupled from data.js)
// ============================================================
function getLocalActivePromos() {
  return loadedPromos.filter(p => p.status === 'active');
}

function applyLocalPromo(promoCode, subtotal) {
  const pr = loadedPromos.find(p => p.code.toUpperCase() === promoCode.toUpperCase() && p.status === 'active');
  if (!pr) return { discount: 0, label: '' };
  
  let disc = 0;
  if (pr.discount_type === 'percent') {
    disc = Math.round(subtotal * pr.discount_value / 100);
  } else if (pr.discount_type === 'fixed') {
    disc = Math.min(pr.discount_value, subtotal);
  }
  return { discount: disc, label: pr.promo_name };
}

function validateLocalPromo(promoCode, subtotal, cartItems) {
  const pr = loadedPromos.find(p => p.code.toLowerCase() === promoCode.toLowerCase());
  
  if (!pr) return { ok: false, discount: 0, label: '', reason: 'Promo code not found.' };
  if (pr.status !== 'active') return { ok: false, discount: 0, label: '', reason: 'This promo is no longer active.' };
  
  const today = new Date(); 
  today.setHours(0,0,0,0);
  
  if (pr.start_date) { 
    const s = new Date(pr.start_date); 
    if (today < s) return { ok: false, discount: 0, label: '', reason: 'This promo starts on ' + pr.start_date + '.' }; 
  }
  if (pr.end_date) { 
    const e = new Date(pr.end_date);   
    if (today > e) return { ok: false, discount: 0, label: '', reason: 'This promo expired on ' + pr.end_date + '.' }; 
  }
  
  if (pr.min_order_amount && subtotal < pr.min_order_amount) {
    return { ok: false, discount: 0, label: '', reason: 'Minimum order of ₱' + pr.min_order_amount + ' required.' };
  }
  
  let disc = 0;
  if (pr.discount_type === 'percent') {
    disc = Math.round(subtotal * pr.discount_value / 100);
  } else if (pr.discount_type === 'fixed') {
    disc = Math.min(pr.discount_value || 0, subtotal);
  }
  
  return { ok: true, discount: disc, label: pr.promo_name, reason: '', promo: pr };
}

// ============================================================
// INTERFACE CONTROLLERS & RENDERS
// ============================================================
function switchTab(t, btn) {
  ['active','sale','upcoming'].forEach(n => {
    document.getElementById('tab-' + n).style.display = n === t ? 'block' : 'none';
    document.getElementById('ptab-' + n).classList.toggle('active', n === t);
  });
}

function copyCode(code) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(code).then(() => toast('Code copied: ' + code + ' 📋')).catch(() => toast('Code: ' + code));
  } else { toast('Code: ' + code); }
}

function renderActivePromos() {
  const promos = getLocalActivePromos();
  const el = document.getElementById('active-promo-grid');
  if (!promos.length) {
    el.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><div class="ei">🏷️</div><h3>No active promos right now</h3><p>Check back soon — we run promos for every season!</p></div>`;
    return;
  }
  el.innerHTML = promos.map(p => {
    const valText = p.discount_type === 'percent' ? p.discount_value + '% OFF' : p.discount_type === 'fixed' ? '₱' + p.discount_value + ' OFF' : 'DEAL';
    const valColor = p.discount_type === 'percent' ? 'var(--p3)' : p.discount_type === 'fixed' ? 'var(--g3)' : '#E65100';
    const endDate = p.end_date ? new Date(p.end_date).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}) : 'Limited time';
    const minOrderText = p.min_order_amount > 0 ? `🛒 Min. Spend: ₱${p.min_order_amount}` : '🌸 No Min. Spend';

    return `
    <div class="promo-card is-active">
      <div class="pc-live"><span class="live-dot"></span>Active Now</div>
      <div class="pc-value" style="color:${valColor}">${valText}</div>
      <div class="pc-name">${p.promo_name}</div>
      <div class="pc-desc">${p.description}</div>
      <div class="pc-meta">
        <span>📅 Until ${endDate}</span>
        <span>${minOrderText}</span>
      </div>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <div class="pc-code" onclick="copyCode('${p.code.toUpperCase()}')" title="Click to copy">
          ${p.code.toUpperCase()} 📋
        </div>
        <div id="tab-active">
        <div class="promo-grid" id="active-promo-grid"></div>

        <!-- PROMO CODE INPUT -->
        <div class="promo-input-box">
          <input type="text" id="promoCodeInput" placeholder="Enter promo code">
          <button onclick="applyPromoCode()">Apply</button>
        </div>

        <div class="info-box">
        <a class="btn btn-pink btn-sm" href="shop.html" style="text-decoration:none">Shop Now →</a>
      </div>
    </div>`;

    
  }).join('');
}

function renderUpcomingPromos() {
  const promos = loadedPromos.filter(p => p.status !== 'active');
  const el = document.getElementById('upcoming-promo-grid');
  if (!promos.length) {
    el.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><div class="ei">📅</div><h3>No upcoming promos yet</h3><p>Stay tuned — something exciting is coming!</p></div>`;
    return;
  }
  el.innerHTML = promos.map(p => {
    const valText = p.discount_type === 'percent' ? p.discount_value + '% OFF' : p.discount_type === 'fixed' ? '₱' + p.discount_value + ' OFF' : 'DEAL';
    const startDate = p.start_date ? new Date(p.start_date).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}) : 'Coming soon';
    const minOrderText = p.min_order_amount > 0 ? `🛒 Min. Spend: ₱${p.min_order_amount}` : '🌸 No Min. Spend';

    return `
    <div class="promo-card">
      <div style="font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem">📅 Coming Soon</div>
      <div class="pc-value" style="color:var(--muted)">${valText}</div>
      <div class="pc-name">${p.promo_name}</div>
      <div class="pc-desc">${p.description}</div>
      <div class="pc-meta">
        <span>🚀 Starts ${startDate}</span>
        <span>${minOrderText}</span>
      </div>
      <button class="btn btn-ghost btn-sm" onclick="toast('We\\'ll notify you when this promo goes live! 🔔')">🔔 Remind Me</button>
    </div>`;
  }).join('');
}

function renderSaleItems() {
  const el = document.getElementById('sale-items-grid');
  el.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><div class="ei">🌱</div><h3>No sale items right now</h3><p>We update sale prices regularly based on freshness!</p></div>`;
}

// INITIALIZATION
loadPromosFromDB();
</script>
</body>
</html>