// nav.js — Shared navigation utilities for FleurChase
// Icons: Google Material Icons (https://fonts.googleapis.com/icon?family=Material+Icons)
// Add this to your <head>:
// <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

function requireAuth(role) {
  const user = FC.getUser();

  if (!user) {
    location.replace('login.html');
    return null;
  }

  const userRole = String(user.role || '').trim().toLowerCase();
  const requiredRole = String(role || '').trim().toLowerCase();

  if (requiredRole && userRole !== requiredRole) {
    FC.clearUser();
    location.replace('login.html');
    return null;
  }

  user.role = userRole;
  FC.setUser(user);

  return user;
}

function doLogout() { FC.clearUser(); FC.saveCart([]); location.href = 'index.html'; }

let _toastTimer;
function toast(msg, type='') {
  let el = document.getElementById('fc-toast');
  if (!el) { el = document.createElement('div'); el.id='fc-toast'; el.className='toast'; document.body.appendChild(el); }
  el.textContent = msg; el.className = 'toast'+(type?' '+type:''); el.classList.add('show');
  clearTimeout(_toastTimer); _toastTimer = setTimeout(()=>el.classList.remove('show'),3000);
}
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
function closeAllModals() { document.querySelectorAll('.modal-overlay').forEach(m=>m.classList.remove('open')); }
document.addEventListener('click', e=>{ if(e.target.classList.contains('modal-overlay')) closeAllModals(); });

function updateCartBadge() {
  const count = FC.getCart().reduce((s,i)=>s+i.qty,0);
  document.querySelectorAll('.cart-count').forEach(el=>el.textContent=count);
}

function buildTopNav(activePage) {
  const user = FC.getUser();
  const nav = document.getElementById('top-nav');
  if (!nav || !user) return;

  if (user.role === 'admin') {
      const adminName = user.name || user.first_name || user.email || 'Admin';
      const displayName = adminName.includes('@') ? adminName.split('@')[0] : adminName;

      nav.innerHTML = `
        <a class="nav-logo" href="admin.html">FleurChase<em>.</em><sub>Albay</sub></a>
        <div class="nav-spacer"></div>
        <div style="display:flex;align-items:center;gap:8px;margin-left:10px">
          <a class="user-chip" href="admin-profile.html" style="text-decoration:none;color:inherit">
            <div class="user-av">${displayName[0].toUpperCase()}</div>
            <span>${displayName.split(' ')[0]}</span>
          </a>
          <button class="logout-btn" onclick="doLogout()">Sign Out</button>
        </div>`;
    } else {
    // Customer nav — no sidebar, all links in top bar
    const pages = [
      { id:'shop',     href:'shop.html',     label:'Shop' },
      { id:'customize',href:'customize.html', label:'Customize' },
      { id:'orders',   href:'orders.html',   label:'Orders' },
      { id:'promos',   href:'promos.php',    label:'Promos' }, 
      { id:'profile',  href:'profile.html',  label:'Profile' },
    ];
    nav.innerHTML = `
      <a class="nav-logo" href="shop.html">FleurChase<em>.</em><sub>Albay</sub></a>
      <div class="nav-spacer"></div>
      <nav class="nav-pill-row">
        ${pages.map(p=>`<a class="nav-pill${activePage===p.id||activePage===p.href?' active':''}" href="${p.href}">${p.label}</a>`).join('')}
      </nav>
      <div style="display:flex;align-items:center;gap:8px;margin-left:10px">
        <a class="nav-icon-btn" href="cart.php" title="Cart"><span class="material-icons" style="font-size:20px;vertical-align:middle">shopping_cart</span><span class="cart-badge cart-count">0</span></a>
        <div class="user-chip"><div class="user-av">${(user.name||'U')[0].toUpperCase()}</div><span>${user.name?.split(' ')[0]||'Me'}</span></div>
        <button class="logout-btn" onclick="doLogout()">Sign Out</button>
      </div>`;  
  }
  updateCartBadge();
}


// Customer sidebar is REMOVED — kept as no-op for backward compat
function buildCustomerSidebar() {}

function buildAdminSidebar(activePage) {
  const sb = document.getElementById('fc-sidebar');
  if (!sb) return;

  const user = FC.getUser();
  const pending = FC.getOrders().filter(o => o.payStatus === 'uploaded' && o.status === 'Pending').length;

  const nav = [
    { s:'Overview', items:[
      { href:'admin.html', icon:'dashboard', label:'Dashboard' },
      { href:'orders-admin.html', icon:'inventory_2', label:'Orders', badge: pending || '' },
    ]},
    { s:'Catalog', items:[
      { href:'products-admin.php', icon:'local_florist', label:'Bouquets' },
      { href:'promos-admin.html', icon:'sell', label:'Promos & Sales' },
    ]},
    { s:'Stock', items:[
      { href:'inventory-admin.php', icon:'eco', label:'Products' },
    ]},
    { s:'Insights', items:[
      { href:'seasonal-admin.html', icon:'trending_up', label:'Seasonal Trends' },
      { href:'reports-admin.html', icon:'assignment', label:'Reports' },
    ]},
    { s:'Account', items:[
      { href:'admin-profile.html', icon:'person', label:'Profile' },
    ]}
  ];

  sb.innerHTML = `
    <div class="sb-brand">
      <div class="sb-brand-name">FleurChase<em>.</em></div>
      <div class="sb-brand-sub">Admin Panel</div>
    </div>

    <div class="sb-body">
      ${nav.map(sec => `
        <div class="sb-section">
          <div class="sb-section-label">${sec.s}</div>
          ${sec.items.map(it => `
            <a class="sb-item${it.href === activePage ? ' active' : ''}" href="${it.href}">
              <span class="sbi">
                <span class="material-icons" style="font-size:18px;vertical-align:middle">${it.icon}</span>
              </span>
              <span class="sbl">${it.label}</span>
              ${it.badge ? `<span class="sb-badge">${it.badge}</span>` : ''}
            </a>
          `).join('')}
        </div>
      `).join('')}
    </div>

    <div class="sb-footer">
      <a class="sb-user" href="admin-profile.html" style="text-decoration:none;color:inherit">
        <div class="sb-av">
          <span class="material-icons" style="font-size:18px;vertical-align:middle">person</span>
        </div>
        <div>
          <span class="sb-uname">${user?.name || user?.first_name || user?.email || 'Admin'}</span>
          <span class="sb-urole">Administrator</span>
        </div>
      </a>
    </div>`;
}

function renderFooter(containerId, isAdmin) {
  const el = document.getElementById(containerId); if(!el) return;
  el.innerHTML = `<footer class="fc-footer">
    <div class="fc-footer-grid">
      <div>
        <div class="fl">FleurChase<em>.</em></div>
        <p>Albay's trusted flower shop. Handcrafted bouquets delivered with care across the province.</p>
        <div class="fc-socials">
          <div class="fc-social" onclick="toast('Follow us on Facebook: /FleurChaseAlbay')"><span class="material-icons" style="font-size:20px;vertical-align:middle">facebook</span></div>
          <div class="fc-social" onclick="toast('Follow us on Instagram: @fleurChase.albay')"><span class="material-icons" style="font-size:20px;vertical-align:middle">photo_camera</span></div>
          <div class="fc-social" onclick="toast('Follow us on TikTok: @fleurChase')"><span class="material-icons" style="font-size:20px;vertical-align:middle">music_note</span></div>
        </div>
      </div>
      <div>
        <h4>Quick Links</h4>
        ${!isAdmin
          ?`<a href="shop.html">Shop</a><a href="customize.html">Customize Bouquet</a><a href="promos.php">Promos & Sales</a><a href="orders.html">Track Orders</a><a href="cart.html">My Cart</a>` // CHANGED: promos.html to promos.php
          :`<a href="admin.html">Dashboard</a><a href="orders-admin.html">Orders</a><a href="products-admin.php">Bouquets</a>`}
      </div>
      <div>
        <h4>Information</h4>
        <a onclick="toast('Pre-Order Policy: Min. 48hrs advance booking. No cancellations after confirmation.')">Pre-Order Policy</a>
        <a onclick="toast('Payment: GCash 50% deposit or full payment.')">Payment Guide</a>
        <a onclick="toast('Free delivery in Legazpi City &amp; Daraga. Extra fee for other Albay areas.')">Delivery Coverage</a>
        <a onclick="toast('FAQs: Message us on Facebook!')">FAQs</a>
        <a onclick="toast('Privacy Policy: Your data is safe with us.')">Privacy Policy</a>
      </div>
      <div>
        <h4>Contact Us</h4>
        <div class="fc-contact"><div class="fc-contact-icon"><span class="material-icons" style="font-size:18px;vertical-align:middle">location_on</span></div><div><strong>Address</strong><span>Legazpi City, Albay 4500</span></div></div>
        <div class="fc-contact"><div class="fc-contact-icon"><span class="material-icons" style="font-size:18px;vertical-align:middle">phone</span></div><div><strong>Phone / GCash</strong><span>0966 123 4567</span></div></div>
        <div class="fc-contact"><div class="fc-contact-icon"><span class="material-icons" style="font-size:18px;vertical-align:middle">mail</span></div><div><strong>Email</strong><span>hello@fleurChase.ph</span></div></div>
        <div class="fc-contact"><div class="fc-contact-icon"><span class="material-icons" style="font-size:18px;vertical-align:middle">schedule</span></div><div><strong>Hours</strong><span>Mon–Sat: 8AM–6PM · Sun: 9AM–3PM</span></div></div>
      </div>
    </div>
    <div class="fc-footer-bottom">
      <p>© 2024 FleurChase. All rights reserved. Albay Province, Philippines.</p>
      <div class="links">
        <a onclick="toast('Terms: Pre-order only, 48hr notice, Albay delivery only.')">Terms</a>
        <a onclick="toast('Privacy Policy: Your information is safe.')">Privacy</a>
      </div>
    </div>
  </footer>`;
}

function renderPromoBanner(containerId) {
  const el = document.getElementById(containerId);
  if (!el) return;

  const promos = FC.getActivePromos();
  if (!promos.length) return;

  const content = promos
    .map(p => `<span class="material-icons" style="font-size:15px;vertical-align:middle;margin-right:4px">sell</span> ${p.name}: ${p.desc}`)
    .join('     •     ');

  el.innerHTML = `
    <div class="promo-banner">
      <div class="pb-mask">
        <div class="pb-scroll" id="promo-moving-text">${content}</div>
      </div>
    </div>`;

  const mask = el.querySelector('.pb-mask');
  const moving = document.getElementById('promo-moving-text');

  let x = mask.offsetWidth;

  function animatePromo() {
    x -= 3;

    if (x < -moving.scrollWidth) {
      x = mask.offsetWidth;
    }

    moving.style.transform = `translateX(${x}px)`;
    requestAnimationFrame(animatePromo);
  }

  moving.style.transform = `translateX(${x}px)`;
  animatePromo();
}



function fmtP(n) { return '₱' + Math.round(n).toLocaleString(); }

// ── IMAGE HELPERS ─────────────────────────────────────────
function imgPlaceholder(size=40) {
  const s = size;
  return `<svg width="${s}" height="${s}" viewBox="0 0 ${s} ${s}" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block">
    <rect width="${s}" height="${s}" rx="6" fill="#F0ECE4"/>
    <path d="M${s*.3} ${s*.62} Q${s*.5} ${s*.28} ${s*.7} ${s*.62}" stroke="#C8BFB0" stroke-width="1.4" fill="none"/>
    <circle cx="${s*.5}" cy="${s*.38}" r="${s*.09}" fill="#C8BFB0"/>
    <rect x="${s*.46}" y="${s*.55}" width="${s*.08}" height="${s*.18}" rx="${s*.02}" fill="#C8BFB0"/>
  </svg>`;
}

function productImg(obj, size=40, extraStyle='') {
  if (obj && obj.img) {
    return `<img src="${obj.img}" style="width:${size}px;height:${size}px;object-fit:cover;border-radius:6px;display:block;${extraStyle}" alt="${obj.name||''}"/>`;
  }
  return `<span style="display:inline-flex;flex-shrink:0">${imgPlaceholder(size)}</span>`;
}

function getBestPromo(subtotal, cartItems) {
  const promos = FC.getPromos().filter(p=>p.status==='active');
  let best=null, bestAmt=0;
  promos.forEach(p=>{
    const r = FC.validatePromo(p.id, subtotal, cartItems||[]);
    if(r.ok && r.discount > bestAmt){ bestAmt=r.discount; best=r.promo; }
  });
  return best ? {promo:best,discount:bestAmt} : {promo:null,discount:0};
}