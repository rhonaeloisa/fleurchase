<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<?php
session_start();
include 'db/connection_db.php';

// Fetch products by type for the pickers
$flowers = [];
$fillers = [];
$addons  = [];

$prod_sql = "SELECT * FROM product WHERE status = 'Active' ORDER BY product_name ASC";
$prod_res = mysqli_query($conn, $prod_sql);
while ($p = mysqli_fetch_assoc($prod_res)) {
  if ($p['product_type'] === 'flower') $flowers[] = $p;
  elseif ($p['product_type'] === 'filler') $fillers[] = $p;
  elseif ($p['product_type'] === 'addon')  $addons[]  = $p;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>FleurChase — Add Bouquet</title>
<link rel="stylesheet" href="shared.css"/>
<style>
/* ── Layout ── */
.ab-layout{display:grid;grid-template-columns:1fr 300px;gap:1.5rem;padding:2rem 2.5rem;align-items:start}
@media(max-width:960px){.ab-layout{grid-template-columns:1fr}}

/* ── Steps ── */
.step-card{background:white;border-radius:var(--rl);border:1px solid var(--line);padding:1.4rem;margin-bottom:1rem}
.step-head{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:1rem;display:flex;align-items:center;gap:8px}
.step-head::after{content:'';flex:1;height:1px;background:var(--line)}
.step-num{width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,var(--g4),var(--g2));color:white;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}

/* ── Picker grid ── */
.picker-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:10px}
.pk-card{background:var(--soft);border-radius:var(--rl);border:2px solid transparent;padding:12px;cursor:default;transition:all .18s;display:flex;flex-direction:column;align-items:center;text-align:center;gap:6px;position:relative}
.pk-card:hover{border-color:var(--g6)}
.pk-card.sel{border-color:var(--g4);background:var(--g9)}
.pk-card.sel .pk-check{display:flex}
.pk-check{display:none;position:absolute;top:8px;right:8px;width:18px;height:18px;border-radius:50%;background:var(--g4);color:white;font-size:10px;align-items:center;justify-content:center}
.pk-img{width:70px;height:70px;object-fit:cover;border-radius:var(--r);background:var(--line);flex-shrink:0}
.pk-img-ph{width:70px;height:70px;border-radius:var(--r);background:var(--line);display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0}
.pk-name{font-size:12px;font-weight:600;color:var(--ink);line-height:1.3}
.pk-price{font-size:11px;font-weight:700;color:var(--g3)}
.pk-stock{font-size:10px;color:var(--muted)}
.pk-ctrl{display:flex;align-items:center;gap:6px;margin-top:2px}
.pk-btn{width:26px;height:26px;border-radius:6px;border:1.5px solid var(--line);background:white;cursor:pointer;font-size:15px;font-weight:700;display:flex;align-items:center;justify-content:center;transition:all .15s;color:var(--ink);flex-shrink:0}
.pk-btn:hover{background:var(--g9);border-color:var(--g4);color:var(--g2)}
.pk-btn.rem:hover{background:var(--p9);border-color:var(--p4);color:var(--p2)}
.pk-btn:disabled{opacity:.3;cursor:not-allowed}
.pk-qty{font-size:14px;font-weight:700;min-width:22px;text-align:center;color:var(--ink)}

/* ── Color swatches ── */
.swatch-row{display:flex;gap:8px;flex-wrap:wrap}
.swatch{width:30px;height:30px;border-radius:50%;cursor:pointer;border:2px solid transparent;transition:all .18s;box-shadow:inset 0 0 0 1px rgba(0,0,0,.10)}
.swatch:hover{transform:scale(1.12)}
.swatch.sel{border-color:var(--g2);box-shadow:0 0 0 3px white,0 0 0 5px var(--g2)}

/* ── Summary sidebar ── */
.sum-sticky{position:sticky;top:calc(var(--nh) + 16px)}
.sum-row{display:flex;justify-content:space-between;font-size:12px;padding:5px 0;border-bottom:1px dashed var(--line)}
.sum-row:last-of-type{border-bottom:none}
.sum-k{color:var(--muted)}
.sum-v{font-weight:500;text-align:right;max-width:160px;word-break:break-word}
.sum-total{display:flex;justify-content:space-between;font-size:17px;font-weight:700;padding:10px 0 0;border-top:2px solid var(--line);margin-top:4px;color:var(--g1);font-family:var(--font-d)}
.sum-section{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);padding:8px 0 3px;border-top:1px solid var(--line);margin-top:4px}

/* ── Selection list in sidebar ── */
.sel-list{list-style:none;margin:0;padding:0}
.sel-list li{display:flex;justify-content:space-between;align-items:center;font-size:11px;color:var(--ink);padding:3px 0;border-bottom:1px dashed var(--line)}
.sel-list li:last-child{border-bottom:none}
.sel-list .sli-name{color:var(--ink);font-weight:500}
.sel-list .sli-qty{font-size:10px;color:var(--g3);font-weight:700;background:var(--g9);padding:1px 6px;border-radius:10px}

/* ── Upload zone ── */
.ab-upload{border:2px dashed var(--line);border-radius:var(--rl);padding:1.2rem;text-align:center;cursor:pointer;transition:all .2s;background:var(--soft)}
.ab-upload:hover,.ab-upload.drag{border-color:var(--g5);background:var(--g9)}
.ab-upload strong{font-size:13px;display:block;margin-bottom:3px;color:var(--ink)}
.ab-upload p{font-size:11px;color:var(--muted)}

/* ── Tabs for flowers/fillers/addons ── */
.pk-tabs{display:flex;gap:5px;flex-wrap:wrap;margin-bottom:12px}
.pk-tab{padding:5px 13px;border-radius:16px;border:1.5px solid var(--line);background:white;font-family:var(--font-b);font-size:11px;font-weight:600;cursor:pointer;color:var(--muted);transition:all .18s}
.pk-tab:hover{border-color:var(--g5);color:var(--g2)}
.pk-tab.on{background:var(--g9);border-color:var(--g4);color:var(--g2)}

/* ── Stem progress ── */
.stem-wrap{background:var(--soft);border-radius:var(--r);padding:10px 14px;margin-bottom:1rem}
.stem-bar-track{background:var(--line);border-radius:10px;height:7px;overflow:hidden;flex:1}
.stem-bar-fill{height:100%;border-radius:10px;background:linear-gradient(90deg,var(--g5),var(--g3));transition:width .3s}
.stem-bar-fill.ready{background:linear-gradient(90deg,var(--p4),var(--p3))}
.stem-row{display:flex;align-items:center;gap:10px;margin-bottom:4px}

/* ── Error state ── */
.ff.err input,.ff.err select,.ff.err textarea{border-color:var(--p3)!important}
.err-msg{font-size:11px;color:var(--p3);margin-top:3px;font-weight:500}

/* empty states */
.pk-empty{grid-column:1/-1;text-align:center;padding:2rem;color:var(--muted);font-size:13px}
</style>
</head>
<body>
<div id="top-nav" class="app-nav"></div>
<aside class="sidebar" id="fc-sidebar"></aside>

<div class="main-area">
  <div class="ab-layout">

    <!-- ══ LEFT COLUMN: FORM STEPS ══ -->
    <div>
      <div class="page-title" style="margin-bottom:.2rem">
        Add <em style="color:var(--p3);font-style:italic">New</em> Bouquet
      </div>
      <p class="page-sub">Fill in each step — choose the flowers and fillers that make up this bouquet.</p>

      <!-- STEP 1: Basic Info -->
      <div class="step-card">
        <div class="step-head"><span class="step-num">1</span> Basic Information</div>
        <div class="fg2">
          <div class="ff">
            <label>Category *</label>
            <select id="f-cat" required>
              <option value="" disabled selected>Select category</option>
              <option value="ready-made">Ready-made</option>
              <option value="seasonal">Seasonal</option>
              <option value="gift-set">Gift sets</option>
              <option value="promo">Promos</option>
              <option value="sale">On sale</option>
            </select>
          </div>
          <div class="ff">
            <label>Variation (Size) *</label>
            <select id="f-variation" required onchange="updateStemLimits()">
              <option value="" disabled selected>Select size</option>
              <option value="small"  data-min="3"  data-max="10">Small (3–10 stems)</option>
              <option value="medium" data-min="11" data-max="20">Medium (11–20 stems)</option>
              <option value="large"  data-min="21" data-max="40">Large (21–40 stems)</option>
            </select>
          </div>
        </div>
        <div class="ff">
          <label>Bouquet Name *</label>
          <input type="text" id="f-name" placeholder="e.g. Rosé Reverie" required>
        </div>
        <div class="ff">
          <label>Description</label>
          <textarea id="f-desc" rows="2"
            style="border:1.5px solid var(--line);border-radius:var(--r);padding:10px 12px;font-family:var(--font-b);font-size:13px;color:var(--ink);outline:none;background:var(--soft);width:100%;resize:vertical;transition:border-color .2s"
            placeholder="Brief description…"></textarea>
        </div>
        <div class="fg2">
          <div class="ff">
            <label>Stock *</label>
            <input type="number" id="f-stock" min="0" placeholder="0" required>
          </div>
          <div class="ff">
            <label>Status</label>
            <select id="f-status">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Out of Stock">Out of Stock</option>
            </select>
          </div>
        </div>
        <div class="fg2">
          <div class="ff">
            <label>Bouquet Type</label>
            <select id="f-btype">
              <option value="bouquet">Bouquet</option>
              <option value="single-stem">Single Stem</option>
              <option value="box">Box Arrangement</option>
              <option value="basket">Basket</option>
            </select>
          </div>
          <div class="ff">
            <label>Is Customized?</label>
            <select id="f-iscustom">
              <option value="0">No — Pre-made</option>
              <option value="1">Yes — Custom order</option>
            </select>
          </div>
        </div>
        <div class="fg2">
          <div class="ff">
            <label>Date Arrived *</label>
            <input type="date" id="f-arrived" required>
          </div>
          <div class="ff">
            <label>Best Before *</label>
            <input type="date" id="f-bestbefore" required>
            <div id="f-fresh-hint" class="hint"></div>
          </div>
        </div>
      </div>

      <!-- STEP 2: Wrapper Color -->
      <div class="step-card">
        <div class="step-head"><span class="step-num">2</span> Wrapper Color</div>
        <div class="swatch-row" id="swatch-row">
          <?php
          $wrappers = [
            ['val'=>'white',    'hex'=>'#FFFFFF', 'label'=>'White'],
            ['val'=>'ivory',    'hex'=>'#FFFFF0', 'label'=>'Ivory'],
            ['val'=>'blush',    'hex'=>'#FFB6C1', 'label'=>'Blush'],
            ['val'=>'rose',     'hex'=>'#FF007F', 'label'=>'Rose'],
            ['val'=>'red',      'hex'=>'#E63946', 'label'=>'Red'],
            ['val'=>'coral',    'hex'=>'#FF6B6B', 'label'=>'Coral'],
            ['val'=>'peach',    'hex'=>'#FFCBA4', 'label'=>'Peach'],
            ['val'=>'gold',     'hex'=>'#FFD700', 'label'=>'Gold'],
            ['val'=>'sage',     'hex'=>'#B2C9A4', 'label'=>'Sage'],
            ['val'=>'forest',   'hex'=>'#2E8B57', 'label'=>'Forest'],
            ['val'=>'teal',     'hex'=>'#008080', 'label'=>'Teal'],
            ['val'=>'sky',      'hex'=>'#87CEEB', 'label'=>'Sky Blue'],
            ['val'=>'navy',     'hex'=>'#1B2A6B', 'label'=>'Navy'],
            ['val'=>'lavender', 'hex'=>'#E6DEFF', 'label'=>'Lavender'],
            ['val'=>'purple',   'hex'=>'#7B2FBE', 'label'=>'Purple'],
            ['val'=>'kraft',    'hex'=>'#C4A35A', 'label'=>'Kraft'],
            ['val'=>'black',    'hex'=>'#1C1A17', 'label'=>'Black'],
          ];
          foreach ($wrappers as $w): ?>
            <div class="swatch" title="<?php echo $w['label']; ?>"
                 style="background:<?php echo $w['hex']; ?>"
                 data-val="<?php echo $w['val']; ?>"
                 data-label="<?php echo $w['label']; ?>"
                 onclick="pickWrapper(this)"></div>
          <?php endforeach; ?>
        </div>
        <div id="wrapper-preview" style="display:none;align-items:center;gap:8px;margin-top:10px;font-size:12px;color:var(--muted)">
          <span id="wrapper-dot" style="width:14px;height:14px;border-radius:50%;border:1.5px solid var(--line);display:inline-block"></span>
          <span id="wrapper-label" style="font-weight:600;color:var(--ink)"></span>
          <button type="button" onclick="clearWrapper()"
                  style="background:none;border:none;font-size:11px;color:var(--muted);cursor:pointer;text-decoration:underline">Clear</button>
        </div>
        <p class="hint" style="margin-top:8px">Choose the wrapping paper colour for this bouquet.</p>
      </div>

      <!-- STEP 3: Flowers -->
      <div class="step-card">
        <div class="step-head"><span class="step-num">3</span> Choose Flowers</div>

        <!-- Stem progress -->
        <div class="stem-wrap" id="stem-wrap">
          <div class="stem-row">
            <div class="stem-bar-track">
              <div class="stem-bar-fill" id="stem-fill" style="width:0%"></div>
            </div>
            <span style="font-size:13px;font-weight:700;color:var(--g2);white-space:nowrap" id="stem-count-lbl">0 stems</span>
          </div>
          <div style="font-size:11px;color:var(--muted)" id="stem-hint-lbl">Select a variation first to set stem limits.</div>
        </div>

        <?php if (empty($flowers)): ?>
          <div class="pk-empty">🌸 No flowers found in inventory. Add products with type <strong>flower</strong> first.</div>
        <?php else: ?>
        <div class="picker-grid" id="flower-picker">
          <?php foreach ($flowers as $f): ?>
          <div class="pk-card" id="fpk-<?php echo $f['product_id']; ?>"
               data-id="<?php echo $f['product_id']; ?>"
               data-name="<?php echo htmlspecialchars($f['product_name']); ?>"
               data-price="<?php echo $f['price']; ?>"
               data-stock="<?php echo $f['stock']; ?>">
            <div class="pk-check">✓</div>
            <?php if ($f['product_image']): ?>
              <img class="pk-img" src="images/<?php echo htmlspecialchars($f['product_image']); ?>"
                   alt="<?php echo htmlspecialchars($f['product_name']); ?>"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="pk-img-ph" style="display:none">🌸</div>
            <?php else: ?>
              <div class="pk-img-ph">🌸</div>
            <?php endif; ?>
            <div class="pk-name"><?php echo htmlspecialchars($f['product_name']); ?></div>
            <div class="pk-price">₱<?php echo number_format($f['price'], 2); ?>/stem</div>
            <div class="pk-stock">Stock: <?php echo $f['stock']; ?></div>
            <div class="pk-ctrl">
              <button class="pk-btn rem" type="button"
                      onclick="changeQty('flower','<?php echo $f['product_id']; ?>',-1)"
                      id="fminus-<?php echo $f['product_id']; ?>" disabled>−</button>
              <span class="pk-qty" id="fqty-<?php echo $f['product_id']; ?>">0</span>
              <button class="pk-btn" type="button"
                      onclick="changeQty('flower','<?php echo $f['product_id']; ?>',1)"
                      id="fplus-<?php echo $f['product_id']; ?>">+</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- STEP 4: Fillers -->
      <div class="step-card">
        <div class="step-head"><span class="step-num">4</span> Fillers <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;margin-left:4px">(optional)</span></div>
        <?php if (empty($fillers)): ?>
          <div class="pk-empty">🌿 No fillers found. Add products with type <strong>filler</strong> first.</div>
        <?php else: ?>
        <div class="picker-grid" id="filler-picker">
          <?php foreach ($fillers as $f): ?>
          <div class="pk-card" id="lkpk-<?php echo $f['product_id']; ?>"
               data-id="<?php echo $f['product_id']; ?>"
               data-name="<?php echo htmlspecialchars($f['product_name']); ?>"
               data-price="<?php echo $f['price']; ?>">
            <div class="pk-check">✓</div>
            <?php if ($f['product_image']): ?>
              <img class="pk-img" src="images/<?php echo htmlspecialchars($f['product_image']); ?>"
                   alt="<?php echo htmlspecialchars($f['product_name']); ?>"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="pk-img-ph" style="display:none">🌿</div>
            <?php else: ?>
              <div class="pk-img-ph">🌿</div>
            <?php endif; ?>
            <div class="pk-name"><?php echo htmlspecialchars($f['product_name']); ?></div>
            <div class="pk-price">₱<?php echo number_format($f['price'], 2); ?></div>
            <div class="pk-stock">Stock: <?php echo $f['stock']; ?></div>
            <div class="pk-ctrl">
              <button class="pk-btn rem" type="button"
                      onclick="changeQty('filler','<?php echo $f['product_id']; ?>',-1)"
                      id="lminus-<?php echo $f['product_id']; ?>" disabled>−</button>
              <span class="pk-qty" id="lqty-<?php echo $f['product_id']; ?>">0</span>
              <button class="pk-btn" type="button"
                      onclick="changeQty('filler','<?php echo $f['product_id']; ?>',1)"
                      id="lplus-<?php echo $f['product_id']; ?>">+</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- STEP 5: Add-ons -->
      <div class="step-card">
        <div class="step-head"><span class="step-num">5</span> Add-ons / Extras <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;margin-left:4px">(optional)</span></div>
        <?php if (empty($addons)): ?>
          <div class="pk-empty">No add-ons found. Add products with type <strong>addon</strong> first.</div>
        <?php else: ?>
        <div class="pk-tabs" id="addon-tabs">
          <button class="pk-tab on" data-cat="all" onclick="filterAddons(this)">All</button>
          <button class="pk-tab" data-cat="chocolates" onclick="filterAddons(this)">Chocolates</button>
          <button class="pk-tab" data-cat="toys" onclick="filterAddons(this)">Teddies</button>
          <button class="pk-tab" data-cat="cards" onclick="filterAddons(this)">Cards</button>
          <button class="pk-tab" data-cat="balloons" onclick="filterAddons(this)">Balloons</button>
          <button class="pk-tab" data-cat="extras" onclick="filterAddons(this)">Extras</button>
        </div>
        <div class="picker-grid" id="addon-picker">
          <?php foreach ($addons as $a): ?>
          <div class="pk-card" id="apk-<?php echo $a['product_id']; ?>"
               data-id="<?php echo $a['product_id']; ?>"
               data-name="<?php echo htmlspecialchars($a['product_name']); ?>"
               data-price="<?php echo $a['price']; ?>"
               data-cat="<?php echo htmlspecialchars($a['category'] ?? 'extras'); ?>">
            <div class="pk-check">✓</div>
            <?php if ($a['product_image']): ?>
              <img class="pk-img" src="images/<?php echo htmlspecialchars($a['product_image']); ?>"
                   alt="<?php echo htmlspecialchars($a['product_name']); ?>"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="pk-img-ph" style="display:none">🎁</div>
            <?php else: ?>
              <div class="pk-img-ph">🎁</div>
            <?php endif; ?>
            <div class="pk-name"><?php echo htmlspecialchars($a['product_name']); ?></div>
            <div class="pk-price">₱<?php echo number_format($a['price'], 2); ?></div>
            <div class="pk-ctrl">
              <button class="pk-btn rem" type="button"
                      onclick="changeQty('addon','<?php echo $a['product_id']; ?>',-1)"
                      id="aminus-<?php echo $a['product_id']; ?>" disabled>−</button>
              <span class="pk-qty" id="aqty-<?php echo $a['product_id']; ?>">0</span>
              <button class="pk-btn" type="button"
                      onclick="changeQty('addon','<?php echo $a['product_id']; ?>',1)"
                      id="aplus-<?php echo $a['product_id']; ?>">+</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- STEP 6: Photo -->
      <div class="step-card">
        <div class="step-head"><span class="step-num">6</span> Bouquet Photo</div>
        <div class="ab-upload" id="upload-zone" onclick="document.getElementById('f-image').click()">
          <div id="upload-inner">
            <div style="font-size:28px;margin-bottom:6px">📷</div>
            <strong>Click or drag to upload</strong>
            <p>JPG, PNG or WEBP · max 5 MB</p>
          </div>
        </div>
        <input type="file" id="f-image" accept="image/*" style="display:none" onchange="previewPhoto(this)">
      </div>

    </div><!-- /left col -->

    <!-- ══ RIGHT COLUMN: STICKY SUMMARY ══ -->
    <div class="sum-sticky">
      <div class="step-card">
        <div class="step-head">Summary</div>

        <!-- Mini preview -->
        <div id="sum-preview"
             style="background:var(--soft);border-radius:var(--rl);height:110px;display:flex;align-items:center;justify-content:center;border:1px solid var(--line);margin-bottom:1rem;overflow:hidden;font-size:32px;color:var(--line)">
          🌸
        </div>

        <div class="sum-section">Bouquet</div>
        <div class="sum-row"><span class="sum-k">Name</span><span class="sum-v" id="s-name">—</span></div>
        <div class="sum-row"><span class="sum-k">Category</span><span class="sum-v" id="s-cat">—</span></div>
        <div class="sum-row"><span class="sum-k">Variation</span><span class="sum-v" id="s-var">—</span></div>
        <div class="sum-row"><span class="sum-k">Wrapper</span><span class="sum-v" id="s-wrap">—</span></div>
        <div class="sum-row"><span class="sum-k">Stems</span><span class="sum-v" id="s-stems">0</span></div>

        <div class="sum-section">Ingredients</div>
        <ul class="sel-list" id="s-flower-list"><li style="font-size:11px;color:var(--muted)">No flowers yet</li></ul>

        <div id="s-filler-section">
          <div class="sum-section">Fillers</div>
          <ul class="sel-list" id="s-filler-list"><li style="font-size:11px;color:var(--muted)">None</li></ul>
        </div>

        <div id="s-addon-section">
          <div class="sum-section">Add-ons</div>
          <ul class="sel-list" id="s-addon-list"><li style="font-size:11px;color:var(--muted)">None</li></ul>
        </div>

        <div class="sum-section">Pricing</div>
        <div class="sum-row"><span class="sum-k">Flower cost</span><span class="sum-v" id="s-flwcost">₱0</span></div>
        <div class="sum-row"><span class="sum-k">Filler cost</span><span class="sum-v" id="s-fillcost">₱0</span></div>
        <div class="sum-row"><span class="sum-k">Add-on cost</span><span class="sum-v" id="s-addcost">₱0</span></div>
        <div class="sum-total"><span>Est. Total</span><span id="s-total">₱0</span></div>

        <div style="margin-top:1rem;display:flex;flex-direction:column;gap:8px">
          <button type="button" class="btn btn-green" style="justify-content:center"
                  onclick="submitBouquet()">
            <i class="ti ti-check" aria-hidden="true"></i> Save Bouquet
          </button>
          <a href="products-admin.php" class="btn btn-ghost" style="justify-content:center">
            Cancel
          </a>
        </div>
        <p style="font-size:10px;color:var(--muted);text-align:center;margin-top:8px;line-height:1.6">
          All selections will be saved to the database.<br>
          Flower/filler counts map to <code>bouquet_product</code>.
        </p>
      </div>
    </div>

  </div><!-- /ab-layout -->
</div><!-- /main-area -->

<div id="fc-toast" class="toast"></div>
<script src="data.js"></script>
<script src="nav.js"></script>

<!-- Hidden form that actually submits -->
<form id="real-form" action="db/save_bouquet.php" method="POST" enctype="multipart/form-data" style="display:none">
  <input type="hidden" name="category"    id="hf-cat">
  <input type="hidden" name="variation"   id="hf-var">
  <input type="hidden" name="name"        id="hf-name">
  <input type="hidden" name="description" id="hf-desc">
  <input type="hidden" name="price" id="hf-price" value="0">
  <input type="hidden" name="stock"       id="hf-stock">
  <input type="hidden" name="status"      id="hf-status">
  <input type="hidden" name="bouquet_type" id="hf-btype">
  <input type="hidden" name="is_custom"   id="hf-iscustom">
  <input type="hidden" name="date_arrived" id="hf-arrived">
  <input type="hidden" name="best_before" id="hf-bestbefore">
  <input type="hidden" name="wrapper"     id="hf-wrapper">
  <!-- products JSON: [{product_id, quantity, item_type}] -->
  <input type="hidden" name="products_json" id="hf-products">
</form>
<!-- File input is cloned into real-form on submit -->

<script>
requireAuth('admin');
buildTopNav('products-admin');
buildAdminSidebar('products-admin.php');

/* ── State ── */
const flowerQty = {};  // product_id → qty
const fillerQty = {};
const addonQty  = {};

let stemMin = 0, stemMax = 0;
let selWrapper = null;

/* ── Stem limits from variation select ── */
function updateStemLimits() {
  const sel = document.getElementById('f-variation');
  const opt = sel.options[sel.selectedIndex];
  stemMin = parseInt(opt.dataset.min || 0);
  stemMax = parseInt(opt.dataset.max || 0);
  updateStemBar();
  updateSummary();
}

function totalStems() {
  return Object.values(flowerQty).reduce((s, v) => s + v, 0);
}

function updateStemBar() {
  const used = totalStems();
  const pct  = stemMax > 0 ? Math.min(100, Math.round((used / stemMax) * 100)) : 0;
  const fill = document.getElementById('stem-fill');
  fill.style.width = pct + '%';
  fill.className   = 'stem-bar-fill' + (used >= stemMin && stemMin > 0 ? ' ready' : '');
  document.getElementById('stem-count-lbl').textContent = used + ' stem' + (used !== 1 ? 's' : '');

  let hint = '';
  if (!stemMax) hint = 'Select a variation to set stem limits.';
  else if (used === 0) hint = 'Add at least ' + stemMin + ' stems.';
  else if (used < stemMin) hint = 'Need ' + (stemMin - used) + ' more stem(s) (min ' + stemMin + ').';
  else if (used >= stemMax) hint = '✓ Maximum reached (' + stemMax + ' stems).';
  else hint = '✓ Good! Up to ' + (stemMax - used) + ' more stem(s) allowed.';
  document.getElementById('stem-hint-lbl').textContent = hint;

  /* also refresh + button disabled states */
  document.querySelectorAll('#flower-picker .pk-card').forEach(card => {
    const id   = card.dataset.id;
    const qty  = flowerQty[id] || 0;
    const plus = document.getElementById('fplus-' + id);
    if (plus) plus.disabled = (used >= stemMax && stemMax > 0);
  });
}

/* ── Generic qty handler ── */
function changeQty(type, id, delta) {
  const store = type === 'flower' ? flowerQty : (type === 'filler' ? fillerQty : addonQty);
  const prefix = type === 'flower' ? 'f' : (type === 'filler' ? 'l' : 'a');

  if (type === 'flower' && delta > 0 && stemMax > 0 && totalStems() >= stemMax) {
    toast('Maximum ' + stemMax + ' stems reached.', 'warn'); return;
  }

  const cur = store[id] || 0;
  const nxt = Math.max(0, cur + delta);
  if (nxt === 0) delete store[id]; else store[id] = nxt;

  /* update qty label */
  const qtyEl = document.getElementById(prefix + 'qty-' + id);
  if (qtyEl) qtyEl.textContent = nxt;

  /* update minus button */
  const minusEl = document.getElementById(prefix + 'minus-' + id);
  if (minusEl) minusEl.disabled = (nxt === 0);

  /* card sel state */
  const card = document.getElementById(
    (type === 'flower' ? 'fpk-' : (type === 'filler' ? 'lkpk-' : 'apk-')) + id
  );
  if (card) card.classList.toggle('sel', nxt > 0);

  if (type === 'flower') updateStemBar();
  updateSummary();
}

/* ── Wrapper ── */
function pickWrapper(el) {
  document.querySelectorAll('.swatch').forEach(s => s.classList.remove('sel'));
  el.classList.add('sel');
  selWrapper = { val: el.dataset.val, label: el.dataset.label, hex: el.style.background };
  document.getElementById('wrapper-dot').style.background = el.style.background;
  document.getElementById('wrapper-label').textContent    = el.dataset.label;
  document.getElementById('wrapper-preview').style.display = 'flex';
  updateSummary();
}
function clearWrapper() {
  document.querySelectorAll('.swatch').forEach(s => s.classList.remove('sel'));
  selWrapper = null;
  document.getElementById('wrapper-preview').style.display = 'none';
  updateSummary();
}

/* ── Addon category filter ── */
function filterAddons(btn) {
  document.querySelectorAll('#addon-tabs .pk-tab').forEach(t => t.classList.remove('on'));
  btn.classList.add('on');
  const cat = btn.dataset.cat;
  document.querySelectorAll('#addon-picker .pk-card').forEach(card => {
    card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
  });
}

/* ── Photo preview ── */
function previewPhoto(input) {
  if (!input.files || !input.files[0]) return;
  const url  = URL.createObjectURL(input.files[0]);
  const zone = document.getElementById('upload-zone');
  zone.innerHTML = `<img src="${url}" style="max-height:140px;border-radius:var(--r);object-fit:cover;display:block;margin:0 auto">`;
  zone.style.padding = '.5rem';
  document.getElementById('sum-preview').innerHTML =
    `<img src="${url}" style="width:100%;height:100%;object-fit:cover;border-radius:var(--rl)">`;
}

/* Drag & drop */
(function () {
  const zone = document.getElementById('upload-zone');
  zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('drag');
    const file = e.dataTransfer.files[0]; if (!file) return;
    const dt = new DataTransfer(); dt.items.add(file);
    const inp = document.getElementById('f-image');
    inp.files = dt.files;
    previewPhoto(inp);
  });
})();

/* ── Freshness hint on date change ── */
function updateFreshHint() {
  const bb  = document.getElementById('f-bestbefore').value;
  const hint = document.getElementById('f-fresh-hint');
  if (!bb) { hint.textContent = ''; return; }
  const today = new Date(); today.setHours(0, 0, 0, 0);
  const diff  = Math.round((new Date(bb) - today) / 86400000);
  if (diff < 0)      { hint.textContent = '⚠ Already expired!'; hint.style.color = 'var(--p3)'; }
  else if (diff === 0){ hint.textContent = 'Expires today'; hint.style.color = 'var(--p3)'; }
  else if (diff <= 3) { hint.textContent = diff + ' day(s) left — consider sale pricing.'; hint.style.color = '#E65100'; }
  else               { hint.textContent = diff + ' day(s) remaining.'; hint.style.color = 'var(--g3)'; }
}
document.getElementById('f-bestbefore').addEventListener('change', updateFreshHint);

/* ── Render summary sidebar ── */
function fmtP(n) { return '₱' + Number(n).toFixed(2); }

function buildSelList(store, priceMap, unit) {
  const entries = Object.entries(store).filter(([, q]) => q > 0);
  if (!entries.length) return '<li style="font-size:11px;color:var(--muted)">None</li>';
  return entries.map(([id, qty]) => {
    const name  = priceMap[id]?.name  || id;
    const price = priceMap[id]?.price || 0;
    return `<li>
      <span class="sli-name">${name}${unit ? ' (' + unit + ')' : ''}</span>
      <span class="sli-qty">×${qty} · ${fmtP(price * qty)}</span>
    </li>`;
  }).join('');
}

/* Build price maps from PHP data injected below */
const FLOWER_MAP = {
  <?php foreach ($flowers as $f): ?>
  '<?php echo $f['product_id']; ?>': {name:'<?php echo addslashes($f['product_name']); ?>',price:<?php echo $f['price']; ?>},
  <?php endforeach; ?>
};
const FILLER_MAP = {
  <?php foreach ($fillers as $f): ?>
  '<?php echo $f['product_id']; ?>': {name:'<?php echo addslashes($f['product_name']); ?>',price:<?php echo $f['price']; ?>},
  <?php endforeach; ?>
};
const ADDON_MAP = {
  <?php foreach ($addons as $a): ?>
  '<?php echo $a['product_id']; ?>': {name:'<?php echo addslashes($a['product_name']); ?>',price:<?php echo $a['price']; ?>},
  <?php endforeach; ?>
};

function updateSummary() {
  document.getElementById('s-name').textContent = document.getElementById('f-name').value || '—';
  document.getElementById('s-cat').textContent  = document.getElementById('f-cat').value  || '—';
  document.getElementById('s-var').textContent  = document.getElementById('f-variation').value || '—';
  document.getElementById('s-wrap').textContent = selWrapper ? selWrapper.label : '—';
  document.getElementById('s-stems').textContent = totalStems();

  document.getElementById('s-flower-list').innerHTML = buildSelList(flowerQty, FLOWER_MAP, 'stem');
  document.getElementById('s-filler-list').innerHTML = buildSelList(fillerQty, FILLER_MAP, null);
  document.getElementById('s-addon-list').innerHTML  = buildSelList(addonQty,  ADDON_MAP,  null);

  const flwCost  = Object.entries(flowerQty).reduce((s, [id, q]) => s + (FLOWER_MAP[id]?.price || 0) * q, 0);
  const fillCost = Object.entries(fillerQty).reduce((s, [id, q]) => s + (FILLER_MAP[id]?.price || 0) * q, 0);
  const addCost  = Object.entries(addonQty).reduce((s,  [id, q]) => s + (ADDON_MAP[id]?.price  || 0) * q, 0);
  const total    = flwCost + fillCost + addCost;

  document.getElementById('s-flwcost').textContent  = fmtP(flwCost);
  document.getElementById('s-fillcost').textContent = fmtP(fillCost);
  document.getElementById('s-addcost').textContent  = fmtP(addCost);
  document.getElementById('s-total').textContent    = fmtP(total);


}



/* wire summary to form inputs */
['f-name','f-cat','f-variation'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('change', updateSummary);
  if (el) el.addEventListener('input',  updateSummary);
});

/* ── SUBMIT ── */
function submitBouquet() {
  /* 1. Validate required fields */
  const required = [
    {id:'f-cat',        label:'Category'},
    {id:'f-variation',  label:'Variation'},
    {id:'f-name',       label:'Name'},
    {id:'f-stock',      label:'Stock'},
    {id:'f-arrived',    label:'Date Arrived'},
    {id:'f-bestbefore', label:'Best Before'},
  ];

  let errors = [];
  required.forEach(({id, label}) => {
    const el = document.getElementById(id);
    if (!el.value.trim()) {
      el.closest('.ff')?.classList.add('err');
      errors.push(label);
    } else {
      el.closest('.ff')?.classList.remove('err');
    }
  });

  if (errors.length) {
    toast('Please fill in: ' + errors.join(', '), 'err'); return;
  }

  /* 2. Validate dates */
  const arrived = new Date(document.getElementById('f-arrived').value);
  const bb      = new Date(document.getElementById('f-bestbefore').value);
  if (bb < arrived) {
    document.getElementById('f-bestbefore').closest('.ff').classList.add('err');
    toast('Best Before cannot be earlier than Date Arrived.', 'err'); return;
  }

  /* 3. Validate min stems */
  if (stemMin > 0 && totalStems() < stemMin) {
    toast('Please add at least ' + stemMin + ' flower stems for a ' +
          document.getElementById('f-variation').value + ' bouquet.', 'warn');
    return;
  }

  /* 4. Build products JSON */
  const products = [];
  Object.entries(flowerQty).filter(([,q]) => q > 0).forEach(([id, qty]) => {
    products.push({ product_id: id, quantity: qty, item_type: 'flower', is_addons: 0 });
  });
  Object.entries(fillerQty).filter(([,q]) => q > 0).forEach(([id, qty]) => {
    products.push({ product_id: id, quantity: qty, item_type: 'filler', is_addons: 0 });
  });
  Object.entries(addonQty).filter(([,q]) => q > 0).forEach(([id, qty]) => {
    products.push({ product_id: id, quantity: qty, item_type: 'addon', is_addons: 1 });
  });

  /* 5. Populate hidden form */
  document.getElementById('hf-cat').value       = document.getElementById('f-cat').value;
  document.getElementById('hf-var').value       = document.getElementById('f-variation').value;
  document.getElementById('hf-name').value      = document.getElementById('f-name').value;
  document.getElementById('hf-desc').value      = document.getElementById('f-desc').value;
  const flwC  = Object.entries(flowerQty).reduce((s,[id,q])=>s+(FLOWER_MAP[id]?.price||0)*q,0);
  const filC  = Object.entries(fillerQty).reduce((s,[id,q])=>s+(FILLER_MAP[id]?.price||0)*q,0);
  const addC  = Object.entries(addonQty).reduce((s,[id,q])=>s+(ADDON_MAP[id]?.price||0)*q,0);
  document.getElementById('hf-price').value = (flwC + filC + addC).toFixed(2);
  document.getElementById('hf-stock').value     = document.getElementById('f-stock').value;
  document.getElementById('hf-status').value    = document.getElementById('f-status').value;
  document.getElementById('hf-btype').value     = document.getElementById('f-btype').value;
  document.getElementById('hf-iscustom').value  = document.getElementById('f-iscustom').value;
  document.getElementById('hf-arrived').value   = document.getElementById('f-arrived').value;
  document.getElementById('hf-bestbefore').value= document.getElementById('f-bestbefore').value;
  document.getElementById('hf-wrapper').value   = selWrapper ? selWrapper.val : '';
  document.getElementById('hf-products').value  = JSON.stringify(products);

  /* 6. Move file input into real form */
  const fileInput = document.getElementById('f-image');
  const realForm  = document.getElementById('real-form');
  if (fileInput.files.length > 0) {
    fileInput.name = 'image';
    realForm.appendChild(fileInput);
  }

  realForm.submit();
}

/* Clear err on input */
document.querySelectorAll('input,select,textarea').forEach(el => {
  el.addEventListener('change', () => el.closest('.ff')?.classList.remove('err'));
  el.addEventListener('input',  () => el.closest('.ff')?.classList.remove('err'));
});

/* Set today as default arrived date */
document.getElementById('f-arrived').value = new Date().toISOString().split('T')[0];

updateSummary();
</script>
</body>
</html>
