// ============================================================
//  FleurChase — Shared Data Store  (data.js)
// ============================================================
const FC = (() => {
  const SEED_PRODUCTS = [
    { id:'p1', name:'Red Rose Bouquet',       type:'bouquet', category:'ready-made', price:350,  maxPrice:850, bg:'#fff5f7', badge:'New',      rating:4.8, reviews:42,  stock:30, desc:'Classic 12-stem red rose arrangement.' },
    { id:'p2', name:'Tulip Spring Bundle',    type:'bouquet', category:'seasonal',   price:280,  oldPrice:450, bg:'#f0f4ff', badge:'Sale',     rating:4.6, reviews:28,  stock:20, desc:'Fresh seasonal tulip bundle.' },
    { id:'p3', name:'Custom Arrangement',     type:'bouquet', category:'customized', price:350,  maxPrice:850, bg:'#f0fdf4', badge:'Popular',  rating:4.9, reviews:88,  stock:99, desc:'You design it. Our florists bring it to life.' },
    { id:'p4', name:'Love & Blooms Gift Set', type:'bouquet', category:'gift-set',   price:980,                bg:'#fdf4ff', badge:'',         rating:4.7, reviews:35,  stock:15, desc:'Roses + Chocolates + Greeting card.' },
    { id:'p5', name:'Cherry Blossom Fan',     type:'bouquet', category:'seasonal',   price:650,                bg:'#fff0f6', badge:'Seasonal', rating:4.5, reviews:19,  stock:12, desc:'Japanese-inspired seasonal arrangement.' },
    { id:'p6', name:"Valentine's Rose Trio",  type:'bouquet', category:'promo',      price:550,                bg:'#fff5f7', badge:'Promo',    rating:4.8, reviews:64,  stock:25, desc:"Buy 3 get 2 free! Special arrangement." },
    { id:'p7', name:'Sunflower Sale Bundle',  type:'bouquet', category:'sale',       price:180,  oldPrice:350, bg:'#fffbea', badge:'On Sale',  rating:4.3, reviews:11,  stock:10, desc:'Near-expiry sunflowers at great price.' },
    { id:'p8', name:'Graduation Bouquet',     type:'bouquet', category:'ready-made', price:520,                bg:'#fafff0', badge:'',         rating:4.7, reviews:22,  stock:18, desc:"Sunflowers & Baby's Breath." },
    { id:'p9', name:'Peony Bliss Bundle',     type:'bouquet', category:'ready-made', price:750,                bg:'#fef0f3', badge:'Premium',  rating:4.9, reviews:17,  stock:8,  desc:'Lush premium peonies.' },
    { id:'f1', name:'Red Rose',         type:'flower', category:'individual', price:45, bg:'#fff5f7', badge:'',        rating:4.9, reviews:120, stock:200, desc:'Fresh red rose, sold per stem.' },
    { id:'f2', name:'Pink Tulip',       type:'flower', category:'individual', price:35, bg:'#f0f4ff', badge:'',        rating:4.7, reviews:80,  stock:150, desc:'Fresh pink tulip, sold per stem.' },
    { id:'f3', name:'Cherry Blossom',   type:'flower', category:'individual', price:40, bg:'#fff0f6', badge:'',        rating:4.6, reviews:55,  stock:120, desc:'Cherry blossom sprig, per stem.' },
    { id:'f4', name:'Sunflower',        type:'flower', category:'individual', price:30, bg:'#fffbea', badge:'Sale',    rating:4.5, reviews:90,  stock:80,  desc:'Bright sunflower, sold per stem.' },
    { id:'f5', name:'White Lily',       type:'flower', category:'individual', price:50, bg:'#f5f5ff', badge:'',        rating:4.8, reviews:65,  stock:100, desc:'Elegant white lily, per stem.' },
    { id:'f6', name:'Peony',            type:'flower', category:'individual', price:60, bg:'#fef0f3', badge:'Premium', rating:4.9, reviews:40,  stock:60,  desc:'Premium peony bloom, per stem.' },
    { id:'f7', name:"Baby's Breath",    type:'flower', category:'individual', price:20, bg:'#f0fdf4', badge:'',        rating:4.6, reviews:70,  stock:300, desc:"Baby's breath filler, per stem." },
    { id:'f8', name:'Gerbera Daisy',    type:'flower', category:'individual', price:28, bg:'#fafff0', badge:'',        rating:4.5, reviews:45,  stock:130, desc:'Colorful gerbera daisy, per stem.' },
    { id:'f9', name:'Lavender',         type:'flower', category:'individual', price:25, bg:'#f3e8ff', badge:'',        rating:4.7, reviews:38,  stock:90,  desc:'Aromatic lavender sprig, per stem.' },
  ];

  const SEED_ADDONS = [
    { id:'a1', name:'Ferrero Rocher Box',   category:'chocolates', price:180, stock:50,  desc:'16-piece Ferrero Rocher gift box.' },
    { id:'a2', name:'Heart Chocolates Box', category:'chocolates', price:120, stock:40,  desc:'Assorted heart-shaped chocolates, 12 pcs.' },
    { id:'a3', name:'Small Teddy Bear',     category:'toys',       price:150, stock:30,  desc:'Soft 20cm teddy bear.' },
    { id:'a4', name:'Large Teddy Bear',     category:'toys',       price:350, stock:15,  desc:'Plush 45cm teddy bear.' },
    { id:'a5', name:'Greeting Card',        category:'cards',      price:50,  stock:100, desc:'Personalized message card.' },
    { id:'a6', name:'Balloon Bouquet',      category:'balloons',   price:120, stock:25,  desc:'5-balloon bouquet.' },
    { id:'a7', name:'Scented Candle',       category:'extras',     price:200, stock:20,  desc:'Soy wax candle with floral scent.' },
    { id:'a8', name:'Wine Bottle (red)',    category:'extras',     price:450, stock:12,  desc:'750ml red wine.' },
  ];

  const SEED_PROMOS = [
    { id:'pr1', name:"Valentine's Special", type:'percent', value:20, category:'all',      products:[], startDate:'2024-02-01', endDate:'2024-02-28', status:'active',   minOrder:0,   desc:"20% off all bouquets for Valentine's!" },
    { id:'pr2', name:"Mother's Day 20% Off",type:'percent', value:20, category:'all',      products:[], startDate:'2024-05-01', endDate:'2024-05-15', status:'inactive', minOrder:0,   desc:"20% off for Mother's Day." },
    { id:'pr3', name:'Grad Season Sale',    type:'percent', value:15, category:'seasonal', products:[], startDate:'2024-03-01', endDate:'2024-04-30', status:'active',   minOrder:0,   desc:'15% off seasonal picks. Applies to seasonal products only.' },
    { id:'pr4', name:'Free Ribbon ₱500+',   type:'fixed',   value:0,  category:'all',      products:[], startDate:'2024-03-01', endDate:'2024-03-31', status:'active',   minOrder:500, desc:'Free ribbon on orders over ₱500.' },
    { id:'pr5', name:'Buy 3 Roses Get 2',   type:'bundle',  value:2,  category:'promo',    products:['p6'], startDate:'2024-02-01', endDate:'2024-02-28', status:'active', minOrder:0, desc:'Buy 3 roses, get 2 free stems. For promo items only.' },
    { id:'pr6', name:'Grad Bouquet Deal',   type:'percent', value:10, category:'ready-made',products:[], startDate:'2024-03-01', endDate:'2024-04-30', status:'active',   minOrder:500, desc:'10% off ready-made bouquets ₱500+.' },
  ];

  const SEED_INVENTORY = [
    { id:'i1', flowerId:'f1', name:'Red Roses',       stock:200, pricePerStem:45, arrived:'Mar 15', lifeDays:7,  freshness:85 },
    { id:'i2', flowerId:'f2', name:'Pink Tulips',     stock:150, pricePerStem:35, arrived:'Mar 14', lifeDays:7,  freshness:40 },
    { id:'i3', flowerId:'f4', name:'Sunflowers',      stock:80,  pricePerStem:30, arrived:'Mar 13', lifeDays:7,  freshness:15 },
    { id:'i4', flowerId:'f3', name:'Cherry Blossom',  stock:120, pricePerStem:40, arrived:'Mar 16', lifeDays:7,  freshness:90 },
    { id:'i5', flowerId:'f8', name:'Gerbera Daisies', stock:130, pricePerStem:28, arrived:'Mar 14', lifeDays:7,  freshness:55 },
    { id:'i6', flowerId:'f6', name:'Peonies',         stock:60,  pricePerStem:60, arrived:'Mar 15', lifeDays:5,  freshness:20 },
    { id:'i7', flowerId:'f5', name:'White Lilies',    stock:100, pricePerStem:50, arrived:'Mar 12', lifeDays:7,  freshness:5  },
    { id:'i8', flowerId:'f7', name:"Baby's Breath",   stock:300, pricePerStem:20, arrived:'Mar 16', lifeDays:10, freshness:95 },
    { id:'i9', flowerId:'f9', name:'Lavender',        stock:90,  pricePerStem:25, arrived:'Mar 15', lifeDays:8,  freshness:70 },
  ];

  const SEED_ORDERS = [
    { id:'FC-0048', customer:'Ana Cruz',    loc:'Legazpi City', items:'Red Rose Bouquet (M)', itemDetails:[{productId:'p1',qty:1,stems:0,name:'Red Rose Bouquet',icon:'🌹'}], addonDetails:[], delivDate:'Mar 20', delivTime:'2:00–4:00 PM',      payMethod:'GCash 50% Deposit', payStatus:'uploaded',    status:'Pending',          total:550,  sub:550,  addonTotal:0,   discount:0,   shippingFee:0,   promoLabel:'', receipt:null, phone:'09171234567', address:'Brgy. Rizal, Legazpi' },
    { id:'FC-0047', customer:'Rico Santos', loc:'Daraga',       items:"Valentine's Set",       itemDetails:[{productId:'p6',qty:1,stems:0,name:"Valentine's Rose Trio",icon:'🥀'}], addonDetails:[{addonId:'a1',qty:1,name:'Ferrero Rocher Box',icon:'🍫',price:180}], delivDate:'Mar 18', delivTime:'10:00 AM–12:00 PM', payMethod:'Full GCash', payStatus:'verified', status:'Processing', total:1200, sub:550,  addonTotal:180, discount:110, shippingFee:0,   promoLabel:"Valentine's Special", receipt:null, phone:'09181234567', address:'Daraga Town Center' },
    { id:'FC-0046', customer:'Joy Reyes',   loc:'Legazpi City', items:'Tulip Bundle x2',       itemDetails:[{productId:'p2',qty:2,stems:0,name:'Tulip Spring Bundle',icon:'🌷'}], addonDetails:[], delivDate:'Mar 17', delivTime:'12:00 PM–2:00 PM', payMethod:'Maya 50% Deposit', payStatus:'verified', status:'Out for Delivery', total:560, sub:560, addonTotal:0, discount:0, shippingFee:0, promoLabel:'', receipt:null, phone:'09191234567', address:'Brgy. Oro Site, Legazpi' },
    { id:'FC-0045', customer:'Mark Tan',    loc:'Camalig',      items:'Custom Bouquet S',       itemDetails:[{productId:'p3',qty:1,stems:5,name:'Custom Arrangement',icon:'💐'}], addonDetails:[{addonId:'a5',qty:1,name:'Greeting Card',icon:'💌',price:50}], delivDate:'Mar 16', delivTime:'8:00 AM–10:00 AM', payMethod:'Cash on Delivery', payStatus:'cod-pending', status:'Delivered', total:450, sub:350, addonTotal:50, discount:0, shippingFee:50, promoLabel:'', receipt:null, phone:'09201234567', address:'Camalig Market Area' },
    { id:'FC-0044', customer:'Luz V.',      loc:'Oas',          items:'Graduation Bouquet',     itemDetails:[{productId:'p8',qty:1,stems:0,name:'Graduation Bouquet',icon:'🌼'}], addonDetails:[], delivDate:'Mar 15', delivTime:'2:00 PM–4:00 PM', payMethod:'Full GCash', payStatus:'verified', status:'Delivered', total:670, sub:520, addonTotal:0, discount:0, shippingFee:150, promoLabel:'', receipt:null, phone:'09211234567', address:'Oas Proper, Oas Albay' },
  ];

  const SHIPPING = {
    'Legazpi City':0,'Daraga':0,
    'Camalig':50,'Guinobatan':80,'Libon':120,'Oas':150,
    'Tabaco City':100,'Polangui':120,'Ligao City':100,
    'Malilipot':70,'Bacacay':90,'Other in Albay':150,
  };
  const SERVICE_FEE = 50; // Fixed service fee per order
  const BOUQUET_SIZES = {
    Small:  {
      label:'Small',  minStems:3,  maxStems:10, icon:'💐',
      desc:'3–10 stems. Perfect for a single recipient — a sweet, hand-held arrangement with your choice of flowers.',
    },
    Medium: {
      label:'Medium', minStems:11, maxStems:20, icon:'💐',
      desc:'11–20 stems. A generous arrangement suitable for birthdays, anniversaries, and special occasions.',
    },
    Large:  {
      label:'Large',  minStems:21, maxStems:30, icon:'💐',
      desc:'21–30 stems. A grand, full bouquet that makes a lasting impression — ideal for celebrations and events.',
    },
  };

  const load = (key, def) => {
    try { const v = localStorage.getItem('fc_'+key); return v ? JSON.parse(v) : def; }
    catch { return def; }
  };
  const save = (key, val) => {
    try { localStorage.setItem('fc_'+key, JSON.stringify(val)); return true; }
    catch(e) { console.warn('FC storage error:', key, e.message); return false; }
  };

  // Receipts stored SEPARATELY under fc_receipt_<orderId> to avoid 5MB quota overflow.
  const saveReceipt = (orderId, base64) => {
    const key = 'fc_receipt_' + orderId;
    try { localStorage.setItem(key, base64); return true; }
    catch(e) {
      // Quota exceeded — evict the oldest receipt then retry once
      try {
        const old = Object.keys(localStorage).filter(k => k.startsWith('fc_receipt_') && k !== key);
        if (old.length) { localStorage.removeItem(old[0]); localStorage.setItem(key, base64); return true; }
      } catch {}
      return false;
    }
  };
  const loadReceipt = (orderId) => {
    try { return localStorage.getItem('fc_receipt_' + orderId) || null; } catch { return null; }
  };

  return {
    SHIPPING, SERVICE_FEE, BOUQUET_SIZES,
    getUser:      ()  => load('user', null),
    setUser:      (u) => save('user', u),
    clearUser:    ()  => { try { localStorage.removeItem('fc_user'); } catch {} },
    getProducts:  ()  => load('products',  SEED_PRODUCTS),
    saveProducts: (p) => save('products',  p),
    getAddons:    ()  => load('addons',    SEED_ADDONS),
    saveAddons:   (a) => save('addons',    a),
    getPromos:    ()  => load('promos',    SEED_PROMOS),
    savePromos:   (p) => save('promos',    p),
    getInventory: ()  => load('inventory', SEED_INVENTORY),
    saveInventory:(i) => save('inventory', i),
    getOrders:    ()  => load('orders',    SEED_ORDERS),
    saveOrders:   (orders) => {
      // Strip any receipt blobs before saving — receipts live in fc_receipt_<id> keys
      const clean = orders.map(o => { const c = {...o}; delete c.receipt; return c; });
      return save('orders', clean);
    },
    getCart:      ()  => load('cart',      []),
    saveCart:     (c) => save('cart',      c),
    calcShipping: (mun) => {
      const extra = (SHIPPING[mun] !== undefined) ? SHIPPING[mun] : 100;
      return { base: 0, extra, total: extra };
    },
    // ── Stock deduction — ONLY called after admin validates payment ──
    deductStock: (itemDetails, addonDetails) => {
      const inv   = load('inventory', SEED_INVENTORY);
      const prods = load('products',  SEED_PRODUCTS);
      const ads   = load('addons',    SEED_ADDONS);
      (itemDetails || []).forEach(it => {
        if (!it.productId) return;
        const p = prods.find(x => x.id === it.productId);
        if (p) p.stock = Math.max(0, p.stock - (it.qty || 1));
        const invEntry = inv.find(i => i.flowerId === it.productId);
        if (invEntry) {
          const deduct = it.stems > 0 ? it.stems : (it.qty || 1);
          invEntry.stock = Math.max(0, invEntry.stock - deduct);
        }
      });
      (addonDetails || []).forEach(it => {
        if (!it.addonId) return;
        const a = ads.find(x => x.id === it.addonId);
        if (a) a.stock = Math.max(0, a.stock - (it.qty || 1));
      });
      save('products', prods);
      save('inventory', inv);
      save('addons', ads);
    },
    applyPromo: (promoId, subtotal) => {
      const promos = load('promos', SEED_PROMOS);
      const pr = promos.find(p => p.id === promoId && p.status === 'active');
      if (!pr) return { discount:0, label:'' };
      let disc = 0;
      if (pr.type === 'percent') disc = Math.round(subtotal * pr.value / 100);
      else if (pr.type === 'fixed') disc = Math.min(pr.value, subtotal);
      return { discount: disc, label: pr.name };
    },
    getActivePromos: () => load('promos', SEED_PROMOS).filter(p => p.status === 'active'),
    generateOrderId: () => 'FC-' + String(Date.now()).slice(-6),
    // ── Validate promo code with full T&C checks ──
    // Returns { ok, discount, label, reason }
    validatePromo: (promoId, subtotal, cartItems) => {
      const promos = load('promos', SEED_PROMOS);
      const pr = promos.find(p => p.id === promoId || p.id.toUpperCase() === promoId.toUpperCase());
      if (!pr)                   return { ok:false, discount:0, label:'', reason:'Promo code not found.' };
      if (pr.status !== 'active') return { ok:false, discount:0, label:'', reason:'This promo is no longer active.' };
      // Date check
      const today = new Date(); today.setHours(0,0,0,0);
      if (pr.startDate) { const s = new Date(pr.startDate); if (today < s) return { ok:false, discount:0, label:'', reason:'This promo has not started yet. Starts ' + pr.startDate + '.' }; }
      if (pr.endDate)   { const e = new Date(pr.endDate);   if (today > e) return { ok:false, discount:0, label:'', reason:'This promo has expired (ended ' + pr.endDate + ').' }; }
      // Minimum order check
      if (pr.minOrder && subtotal < pr.minOrder) return { ok:false, discount:0, label:'', reason:'Minimum order of ₱' + pr.minOrder + ' required. Your subtotal is ₱' + subtotal + '.' };
      // Category / product check
      if (pr.category && pr.category !== 'all') {
        const prods = load('products', SEED_PRODUCTS);
        const hasMatch = (cartItems||[]).some(ci => {
          if (pr.products && pr.products.length) return pr.products.includes(ci.productId);
          const p = prods.find(x => x.id === ci.productId);
          return p && p.category === pr.category;
        });
        if (!hasMatch) return { ok:false, discount:0, label:'', reason:'This promo only applies to ' + pr.category + ' products. None found in your cart.' };
      }
      // Compute discount
      let disc = 0;
      if (pr.type === 'percent') disc = Math.round(subtotal * pr.value / 100);
      else if (pr.type === 'fixed') disc = Math.min(pr.value || 0, subtotal);
      else if (pr.type === 'bundle') disc = 0; // bundle handled separately
      return { ok:true, discount:disc, label:pr.name, reason:'', promo:pr };
    },
    // Receipt stored separately from orders (avoids localStorage quota overflow)
    storeReceipt: (orderId, base64) => saveReceipt(orderId, base64),
    getReceipt: (orderId) => {
      // Check separate key first, then fall back to inline (seed data)
      const sep = loadReceipt(orderId);
      if (sep) return sep;
      const o = load('orders', SEED_ORDERS).find(x => x.id === orderId);
      return (o && o.receipt) ? o.receipt : null;
    },
  };
})();
