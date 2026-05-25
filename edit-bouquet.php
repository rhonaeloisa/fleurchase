<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link
<?php
session_start();
include 'db/connection_db.php';

$bouquet_id = intval($_GET['id'] ?? 0);
if (!$bouquet_id) {
  header('Location: products-admin.php');
  exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM bouquet WHERE bouquet_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $bouquet_id);
mysqli_stmt_execute($stmt);
$b = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$b) {
  $_SESSION['flash_err'] = 'Bouquet not found.';
  header('Location: products-admin.php');
  exit;
}

$saved_flowers = [];
$saved_fillers = [];
$saved_addons  = [];

$bp_stmt = mysqli_prepare($conn,
  "SELECT bp.product_id, bp.quantity, bp.is_addons, p.product_type
   FROM bouquet_product bp
   JOIN product p ON p.product_id = bp.product_id
   WHERE bp.bouquet_id = ?"
);
mysqli_stmt_bind_param($bp_stmt, 'i', $bouquet_id);
mysqli_stmt_execute($bp_stmt);
$bp_res = mysqli_stmt_get_result($bp_stmt);
while ($row = $bp_res->fetch_assoc()) {
  if ($row['is_addons'])                      $saved_addons[$row['product_id']]  = (int)$row['quantity'];
  elseif ($row['product_type'] === 'filler')  $saved_fillers[$row['product_id']] = (int)$row['quantity'];
  else                                        $saved_flowers[$row['product_id']] = (int)$row['quantity'];
}
mysqli_stmt_close($bp_stmt);

$flowers = $fillers = $addons = [];
$prod_res = mysqli_query($conn, "SELECT * FROM product WHERE status='Active' ORDER BY product_name ASC");
while ($p = mysqli_fetch_assoc($prod_res)) {
  if      ($p['product_type'] === 'flower') $flowers[] = $p;
  elseif  ($p['product_type'] === 'filler') $fillers[] = $p;
  elseif  ($p['product_type'] === 'addon')  $addons[]  = $p;
}

$flash_ok  = $_SESSION['flash_ok']  ?? ''; unset($_SESSION['flash_ok']);
$flash_err = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);

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

$currentWrapper = null;
foreach ($wrappers as $w) {
  if ($w['val'] === ($b['wrapper'] ?? '')) { $currentWrapper = $w; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>FleurChase — Edit Bouquet</title>
<link rel="stylesheet" href="shared.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<style>
.ab-shell{display:grid;grid-template-columns:1fr 320px;gap:0;min-height:calc(100vh - var(--nh));align-items:start}
@media(max-width:1024px){.ab-shell{grid-template-columns:1fr}}
.ab-main{padding:2rem 2.5rem 3rem;border-right:1px solid var(--line)}
@media(max-width:1024px){.ab-main{border-right:none;padding:1.5rem}}
.ab-panel{position:sticky;top:var(--nh);height:calc(100vh - var(--nh));overflow-y:auto;padding:1.5rem;background:var(--soft);border-left:1px solid var(--line);display:flex;flex-direction:column}
@media(max-width:1024px){.ab-panel{position:static;height:auto;border-left:none;border-top:1px solid var(--line)}}
.ab-page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:2rem;padding-bottom:1.25rem;border-bottom:1px solid var(--line);gap:1rem;flex-wrap:wrap}
.ab-breadcrumb{font-size:11px;color:var(--muted);margin-bottom:4px;display:flex;align-items:center;gap:6px}
.ab-breadcrumb a{color:var(--muted);text-decoration:none}.ab-breadcrumb a:hover{color:var(--g2)}
.ab-page-title{font-family:var(--font-d);font-size:26px;font-weight:700;color:var(--g1);line-height:1.2;display:flex;align-items:center;flex-wrap:wrap;gap:8px}
.ab-page-sub{font-size:12px;color:var(--muted);margin-top:3px}
.ab-header-actions{display:flex;gap:8px;flex-shrink:0}
.ab-section{background:white;border:1px solid var(--line);border-radius:var(--rl);margin-bottom:1.25rem;overflow:hidden}
.ab-section-header{display:flex;align-items:center;gap:10px;padding:.9rem 1.25rem;border-bottom:1px solid var(--line);background:var(--soft)}
.ab-section-num{width:24px;height:24px;border-radius:6px;background:var(--g2);color:white;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ab-section-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--g1);flex:1}
.ab-section-badge{font-size:10px;font-weight:600;color:var(--muted);background:var(--line);padding:2px 8px;border-radius:20px}
.ab-section-body{padding:1.25rem}
.ab-section-body .ff label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted)}
.ab-section-body .ff input,.ab-section-body .ff select,.ab-section-body .ff textarea{font-size:13px;background:var(--soft);border:1px solid var(--line);transition:border-color .15s,box-shadow .15s}
.ab-section-body .ff input:focus,.ab-section-body .ff select:focus,.ab-section-body .ff textarea:focus{border-color:var(--g4);background:white;box-shadow:0 0 0 3px rgba(61,179,104,.08)}
.ab-section-body .ff.err input,.ab-section-body .ff.err select{border-color:var(--p3);box-shadow:0 0 0 3px rgba(199,37,88,.06)}
.ab-cols-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.ab-cols-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
@media(max-width:600px){.ab-cols-2,.ab-cols-3{grid-template-columns:1fr}}
.ab-stem-bar{display:flex;align-items:center;gap:12px;border-top:1px solid var(--line);padding:12px 1.25rem;background:white}
.ab-stem-track{flex:1;height:6px;border-radius:10px;background:var(--line);overflow:hidden}
.ab-stem-fill{height:100%;border-radius:10px;background:linear-gradient(90deg,var(--g5),var(--g3));transition:width .35s;width:0%}
.ab-stem-fill.ready{background:linear-gradient(90deg,var(--p5),var(--p3))}
.ab-stem-count{font-size:13px;font-weight:700;color:var(--g2);white-space:nowrap;min-width:60px;text-align:right}
.ab-stem-hint{font-size:11px;color:var(--muted);padding:6px 1.25rem 10px}
.picker-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px}
.pk-card{background:var(--soft);border:1.5px solid var(--line);border-radius:var(--r);padding:12px 10px;display:flex;flex-direction:column;align-items:center;text-align:center;gap:5px;position:relative;transition:border-color .15s,background .15s,box-shadow .15s}
.pk-card:hover{border-color:var(--g5);background:white}
.pk-card.sel{border-color:var(--g4);background:var(--g9);box-shadow:0 0 0 3px rgba(61,179,104,.10)}
.pk-card.sel .pk-check{opacity:1;transform:scale(1)}
.pk-check{position:absolute;top:7px;right:7px;width:16px;height:16px;border-radius:4px;background:var(--g4);color:white;font-size:9px;display:flex;align-items:center;justify-content:center;opacity:0;transform:scale(.7);transition:all .15s;font-weight:700}
.pk-img{width:64px;height:64px;object-fit:cover;border-radius:var(--r);border:1px solid var(--line);flex-shrink:0}
.pk-img-ph{width:64px;height:64px;border-radius:var(--r);background:var(--line);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0}
.pk-name{font-size:11px;font-weight:600;color:var(--ink);line-height:1.3;word-break:break-word}
.pk-price{font-size:11px;font-weight:700;color:var(--g3)}
.pk-stock{font-size:10px;color:var(--muted);background:var(--soft);border:1px solid var(--line);border-radius:20px;padding:1px 7px}
.pk-ctrl{display:flex;align-items:center;gap:6px;margin-top:4px}
.pk-btn{width:24px;height:24px;border-radius:5px;border:1.5px solid var(--line);background:white;cursor:pointer;font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:center;color:var(--ink);transition:all .12s;flex-shrink:0}
.pk-btn:hover{background:var(--g9);border-color:var(--g4);color:var(--g2)}
.pk-btn.rem:hover{background:var(--p9);border-color:var(--p4);color:var(--p2)}
.pk-btn:disabled{opacity:.3;cursor:not-allowed}
.pk-qty{font-size:13px;font-weight:700;min-width:20px;text-align:center;color:var(--ink)}
.pk-empty{grid-column:1/-1;text-align:center;padding:2rem;color:var(--muted);font-size:12px;border:1.5px dashed var(--line);border-radius:var(--r)}
.pk-tabs{display:flex;gap:5px;flex-wrap:wrap;margin-bottom:12px}
.pk-tab{padding:4px 12px;border-radius:20px;border:1.5px solid var(--line);background:white;font-family:var(--font-b);font-size:11px;font-weight:600;cursor:pointer;color:var(--muted);transition:all .15s}
.pk-tab:hover{border-color:var(--g5);color:var(--g2)}
.pk-tab.on{background:var(--g2);border-color:var(--g2);color:white}
.swatch-row{display:flex;gap:7px;flex-wrap:wrap}
.swatch{width:28px;height:28px;border-radius:50%;cursor:pointer;border:2px solid transparent;transition:all .15s;box-shadow:inset 0 0 0 1px rgba(0,0,0,.12),0 1px 3px rgba(0,0,0,.08)}
.swatch:hover{transform:scale(1.15)}
.swatch.sel{border-color:var(--g2);box-shadow:0 0 0 3px white,0 0 0 5px var(--g2);transform:scale(1.1)}
.wrapper-preview-row{align-items:center;gap:8px;margin-top:10px;padding:8px 12px;background:var(--soft);border:1px solid var(--line);border-radius:var(--r);font-size:12px}
.wrapper-dot{width:14px;height:14px;border-radius:50%;border:1.5px solid rgba(0,0,0,.10);flex-shrink:0}
.wrapper-name{font-weight:600;color:var(--ink);flex:1}
.wrapper-clear{background:none;border:none;font-size:11px;color:var(--muted);cursor:pointer;text-decoration:underline;padding:0}
.wrapper-clear:hover{color:var(--p3)}
.ab-upload{border:2px dashed var(--line);border-radius:var(--r);padding:1.5rem;text-align:center;cursor:pointer;transition:all .2s;background:var(--soft)}
.ab-upload:hover,.ab-upload.drag{border-color:var(--g5);background:var(--g9)}
.ab-upload-icon{font-size:28px;margin-bottom:8px;opacity:.6}
.ab-upload strong{font-size:13px;display:block;margin-bottom:3px;color:var(--ink)}
.ab-upload p{font-size:11px;color:var(--muted)}
.ab-existing-img{width:80px;height:80px;object-fit:cover;border-radius:var(--r);border:1px solid var(--line);display:block;margin-bottom:8px}
.ab-panel-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:.75rem;padding-bottom:.5rem;border-bottom:1px solid var(--line)}
.ab-preview-box{width:100%;aspect-ratio:4/3;border-radius:var(--r);border:1px solid var(--line);background:var(--soft);display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:1rem;flex-shrink:0;font-size:36px;color:var(--line)}
.ab-preview-box img{width:100%;height:100%;object-fit:cover}
.ab-sum-table{width:100%;border-collapse:collapse;font-size:12px;margin-bottom:1rem}
.ab-sum-table tr td{padding:5px 0;vertical-align:top}
.ab-sum-table tr td:first-child{color:var(--muted);white-space:nowrap;padding-right:12px;width:40%}
.ab-sum-table tr td:last-child{font-weight:500;color:var(--ink);text-align:right}
.ab-sum-sep{height:1px;background:var(--line);margin:.6rem 0}
.ab-sum-section-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin:.8rem 0 .4rem}
.ab-ing-list{list-style:none;margin:0 0 .5rem;padding:0}
.ab-ing-list li{display:flex;justify-content:space-between;align-items:center;font-size:11px;color:var(--ink);padding:4px 0;border-bottom:1px dashed var(--line)}
.ab-ing-list li:last-child{border-bottom:none}
.ab-ing-name{color:var(--ink)}
.ab-ing-qty{font-size:10px;font-weight:700;color:var(--g3);background:var(--g9);padding:1px 7px;border-radius:10px;white-space:nowrap}
.ab-sum-none{font-size:11px;color:var(--muted);padding:4px 0}
.ab-pricing{background:white;border:1px solid var(--line);border-radius:var(--r);overflow:hidden;margin-bottom:1rem}
.ab-pricing-row{display:flex;justify-content:space-between;font-size:12px;padding:7px 12px;border-bottom:1px solid var(--line)}
.ab-pricing-row:last-child{border-bottom:none}
.ab-pricing-row .pk{color:var(--muted)}.ab-pricing-row .pv{font-weight:600;color:var(--ink)}
.ab-pricing-total{display:flex;justify-content:space-between;padding:10px 12px;background:var(--g9);border-top:2px solid var(--g6);font-size:15px;font-weight:700;color:var(--g1);font-family:var(--font-d)}
.ab-cta{display:flex;flex-direction:column;gap:7px;margin-top:auto;padding-top:1rem}
.ab-cta .btn{justify-content:center;gap:7px}
.ab-cta-note{font-size:10px;color:var(--muted);text-align:center;line-height:1.6;padding-top:.5rem}
.ab-changed-badge{display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:600;color:#854F0B;background:#FFF8E1;border:1px solid #FFA000;padding:2px 8px;border-radius:20px;opacity:0;transition:opacity .2s}
.ab-changed-badge.show{opacity:1}
</style>
</head>
<body>
<div id="top-nav" class="app-nav"></div>
<aside class="sidebar" id="fc-sidebar"></aside>
<div class="main-area">
<div class="ab-shell">

  <!-- ══ LEFT COLUMN ══ -->
  <div class="ab-main">

    <?php if ($flash_ok): ?>
      <div class="alert alert-g" style="margin-bottom:1rem">✓ <?php echo htmlspecialchars($flash_ok); ?></div>
    <?php endif; ?>
    <?php if ($flash_err): ?>
      <div class="alert alert-r" style="margin-bottom:1rem">✕ <?php echo htmlspecialchars($flash_err); ?></div>
    <?php endif; ?>

    <div class="ab-page-header">
      <div>
        <div class="ab-breadcrumb">
          <a href="products-admin.php">Products</a>
          <span>›</span>
          <a href="products-admin.php">Bouquets</a>
          <span>›</span>
          <span>Edit</span>
        </div>
        <div class="ab-page-title">
          Edit Bouquet
          <span class="ab-changed-badge" id="changed-badge">
            <i class="ti ti-pencil" aria-hidden="true"></i> Unsaved changes
          </span>
        </div>
        <div class="ab-page-sub">
          Editing: <strong><?php echo htmlspecialchars($b['name']); ?></strong>
          &nbsp;·&nbsp; ID #<?php echo $b['bouquet_id']; ?>
        </div>
      </div>
      <div class="ab-header-actions">
        <a href="products-admin.php" class="btn btn-ghost btn-sm" style="text-decoration:none;">Discard</a>
        <button type="button" class="btn btn-green btn-sm" onclick="submitEdit()">
          <i class="ti ti-device-floppy" aria-hidden="true"></i> Save Changes
        </button>
      </div>
    </div>

    <!-- SECTION 1: Basic Info -->
    <div class="ab-section">
      <div class="ab-section-header">
        <div class="ab-section-num">1</div>
        <div class="ab-section-title">Basic Information</div>
      </div>
      <div class="ab-section-body">
        <div class="ab-cols-2">
          <div class="ff">
            <label>Category <span style="color:var(--p3)">*</span></label>
            <select id="f-cat" required>
              <?php foreach (['ready-made','seasonal','gift-set','promo','sale','customized'] as $opt): ?>
                <option value="<?php echo $opt; ?>" <?php echo ($b['category']===$opt)?'selected':''; ?>>
                  <?php echo ucfirst(str_replace('-',' ',$opt)); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="ff">
            <label>Variation / Size <span style="color:var(--p3)">*</span></label>
            <select id="f-variation" required onchange="updateStemLimits()">
              <?php
              $varMap = ['small'=>['label'=>'Small — 3 to 10 stems','min'=>3,'max'=>10],
                         'medium'=>['label'=>'Medium — 11 to 20 stems','min'=>11,'max'=>20],
                         'large'=>['label'=>'Large — 21 to 40 stems','min'=>21,'max'=>40]];
              foreach ($varMap as $val=>$info): ?>
                <option value="<?php echo $val; ?>"
                        data-min="<?php echo $info['min']; ?>"
                        data-max="<?php echo $info['max']; ?>"
                        <?php echo ($b['variation']===$val)?'selected':''; ?>>
                  <?php echo $info['label']; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="ff">
          <label>Bouquet Name <span style="color:var(--p3)">*</span></label>
          <input type="text" id="f-name" value="<?php echo htmlspecialchars($b['name']); ?>" required>
        </div>
        <div class="ff">
          <label>Description</label>
          <textarea id="f-desc" rows="2"
            style="border:1px solid var(--line);border-radius:var(--r);padding:10px 12px;font-family:var(--font-b);font-size:13px;color:var(--ink);outline:none;background:var(--soft);width:100%;resize:vertical;transition:border-color .15s"
            ><?php echo htmlspecialchars($b['description'] ?? ''); ?></textarea>
        </div>
        <div class="ab-cols-3">
          <div class="ff">
            <label>Stock <span style="color:var(--p3)">*</span></label>
            <input type="number" id="f-stock" min="0" value="<?php echo (int)$b['stock']; ?>" required>
          </div>
          <div class="ff">
            <label>Status</label>
            <select id="f-status">
              <?php foreach (['Active','Inactive','Out of Stock'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo ($b['status']===$s)?'selected':''; ?>><?php echo $s; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION 2: Dates -->
    <div class="ab-section">
      <div class="ab-section-header">
        <div class="ab-section-num">2</div>
        <div class="ab-section-title">Freshness &amp; Dates</div>
      </div>
      <div class="ab-section-body">
        <div class="ab-cols-2">
          <div class="ff">
            <label>Date Arrived <span style="color:var(--p3)">*</span></label>
            <input type="date" id="f-arrived" value="<?php echo htmlspecialchars($b['date_arrived'] ?? ''); ?>" required>
          </div>
          <div class="ff">
            <label>Best Before <span style="color:var(--p3)">*</span></label>
            <input type="date" id="f-bestbefore" value="<?php echo htmlspecialchars($b['best_before'] ?? ''); ?>" required>
            <div id="f-fresh-hint" style="font-size:11px;margin-top:4px"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION 3: Wrapper -->
    <div class="ab-section">
      <div class="ab-section-header">
        <div class="ab-section-num">3</div>
        <div class="ab-section-title">Wrapper Color</div>
        <span class="ab-section-badge">Optional</span>
      </div>
      <div class="ab-section-body">
        <div class="swatch-row" id="swatch-row">
          <?php foreach ($wrappers as $w): ?>
            <div class="swatch <?php echo (($b['wrapper']??'')===$w['val'])?'sel':''; ?>"
                 title="<?php echo $w['label']; ?>"
                 style="background:<?php echo $w['hex']; ?>"
                 data-val="<?php echo $w['val']; ?>"
                 data-label="<?php echo $w['label']; ?>"
                 onclick="pickWrapper(this)"></div>
          <?php endforeach; ?>
        </div>
        <div class="wrapper-preview-row" id="wrapper-preview"
             style="display:<?php echo $currentWrapper?'flex':'none'; ?>">
          <div class="wrapper-dot" id="wrapper-dot"
               style="background:<?php echo $currentWrapper?$currentWrapper['hex']:''; ?>"></div>
          <span class="wrapper-name" id="wrapper-label"><?php echo $currentWrapper?$currentWrapper['label']:''; ?></span>
          <button type="button" class="wrapper-clear" onclick="clearWrapper()">Clear</button>
        </div>
        <p style="font-size:11px;color:var(--muted);margin-top:10px">Select the wrapping paper colour for this bouquet.</p>
      </div>
    </div>

    <!-- SECTION 4: Flowers -->
    <div class="ab-section">
      <div class="ab-section-header">
        <div class="ab-section-num">4</div>
        <div class="ab-section-title">Flowers</div>
        <span class="ab-section-badge">Required</span>
      </div>
      <div class="ab-stem-bar">
        <div class="ab-stem-track"><div class="ab-stem-fill" id="stem-fill"></div></div>
        <div class="ab-stem-count" id="stem-count-lbl">0 stems</div>
      </div>
      <div class="ab-stem-hint" id="stem-hint-lbl">Loading…</div>
      <div class="ab-section-body">
        <?php if (empty($flowers)): ?>
          <div class="pk-empty">🌸 No flowers in inventory.</div>
        <?php else: ?>
        <div class="picker-grid" id="flower-picker">
          <?php foreach ($flowers as $f):
            $sq = $saved_flowers[$f['product_id']] ?? 0; ?>
          <div class="pk-card <?php echo $sq>0?'sel':''; ?>"
               id="fpk-<?php echo $f['product_id']; ?>"
               data-id="<?php echo $f['product_id']; ?>"
               data-price="<?php echo $f['price']; ?>">
            <div class="pk-check">✓</div>
            <?php if ($f['product_image']): ?>
              <img class="pk-img" src="images/<?php echo htmlspecialchars($f['product_image']); ?>"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="pk-img-ph" style="display:none">🌸</div>
            <?php else: ?><div class="pk-img-ph">🌸</div><?php endif; ?>
            <div class="pk-name"><?php echo htmlspecialchars($f['product_name']); ?></div>
            <div class="pk-price">₱<?php echo number_format($f['price'],2); ?>/stem</div>
            <div class="pk-stock">Stock: <?php echo $f['stock']; ?></div>
            <div class="pk-ctrl">
              <button class="pk-btn rem" type="button" id="fminus-<?php echo $f['product_id']; ?>"
                      onclick="changeQty('flower','<?php echo $f['product_id']; ?>',-1)"
                      <?php echo $sq===0?'disabled':''; ?>>−</button>
              <span class="pk-qty" id="fqty-<?php echo $f['product_id']; ?>"><?php echo $sq; ?></span>
              <button class="pk-btn" type="button" id="fplus-<?php echo $f['product_id']; ?>"
                      onclick="changeQty('flower','<?php echo $f['product_id']; ?>',1)">+</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- SECTION 5: Fillers -->
    <div class="ab-section">
      <div class="ab-section-header">
        <div class="ab-section-num">5</div>
        <div class="ab-section-title">Fillers</div>
        <span class="ab-section-badge">Optional</span>
      </div>
      <div class="ab-section-body">
        <?php if (empty($fillers)): ?>
          <div class="pk-empty">🌿 No fillers in inventory.</div>
        <?php else: ?>
        <div class="picker-grid" id="filler-picker">
          <?php foreach ($fillers as $f):
            $sq = $saved_fillers[$f['product_id']] ?? 0; ?>
          <div class="pk-card <?php echo $sq>0?'sel':''; ?>"
               id="lkpk-<?php echo $f['product_id']; ?>"
               data-id="<?php echo $f['product_id']; ?>"
               data-price="<?php echo $f['price']; ?>">
            <div class="pk-check">✓</div>
            <?php if ($f['product_image']): ?>
              <img class="pk-img" src="images/<?php echo htmlspecialchars($f['product_image']); ?>"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="pk-img-ph" style="display:none">🌿</div>
            <?php else: ?><div class="pk-img-ph">🌿</div><?php endif; ?>
            <div class="pk-name"><?php echo htmlspecialchars($f['product_name']); ?></div>
            <div class="pk-price">₱<?php echo number_format($f['price'],2); ?></div>
            <div class="pk-stock">Stock: <?php echo $f['stock']; ?></div>
            <div class="pk-ctrl">
              <button class="pk-btn rem" type="button" id="lminus-<?php echo $f['product_id']; ?>"
                      onclick="changeQty('filler','<?php echo $f['product_id']; ?>',-1)"
                      <?php echo $sq===0?'disabled':''; ?>>−</button>
              <span class="pk-qty" id="lqty-<?php echo $f['product_id']; ?>"><?php echo $sq; ?></span>
              <button class="pk-btn" type="button" id="lplus-<?php echo $f['product_id']; ?>"
                      onclick="changeQty('filler','<?php echo $f['product_id']; ?>',1)">+</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- SECTION 6: Add-ons -->
    <div class="ab-section">
      <div class="ab-section-header">
        <div class="ab-section-num">6</div>
        <div class="ab-section-title">Add-ons &amp; Extras</div>
        <span class="ab-section-badge">Optional</span>
      </div>
      <div class="ab-section-body">
        <?php if (empty($addons)): ?>
          <div class="pk-empty">No add-ons found.</div>
        <?php else: ?>
        <div class="pk-tabs" id="addon-tabs">
          <button class="pk-tab on" data-cat="all"        onclick="filterAddons(this)">All</button>
          <button class="pk-tab"    data-cat="chocolates"  onclick="filterAddons(this)">Chocolates</button>
          <button class="pk-tab"    data-cat="toys"        onclick="filterAddons(this)">Teddies</button>
          <button class="pk-tab"    data-cat="cards"       onclick="filterAddons(this)">Cards</button>
          <button class="pk-tab"    data-cat="balloons"    onclick="filterAddons(this)">Balloons</button>
          <button class="pk-tab"    data-cat="extras"      onclick="filterAddons(this)">Extras</button>
        </div>
        <div class="picker-grid" id="addon-picker">
          <?php foreach ($addons as $a):
            $sq = $saved_addons[$a['product_id']] ?? 0; ?>
          <div class="pk-card <?php echo $sq>0?'sel':''; ?>"
               id="apk-<?php echo $a['product_id']; ?>"
               data-id="<?php echo $a['product_id']; ?>"
               data-price="<?php echo $a['price']; ?>"
               data-cat="<?php echo htmlspecialchars($a['category']??'extras'); ?>">
            <div class="pk-check">✓</div>
            <?php if ($a['product_image']): ?>
              <img class="pk-img" src="images/<?php echo htmlspecialchars($a['product_image']); ?>"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="pk-img-ph" style="display:none">🎁</div>
            <?php else: ?><div class="pk-img-ph">🎁</div><?php endif; ?>
            <div class="pk-name"><?php echo htmlspecialchars($a['product_name']); ?></div>
            <div class="pk-price">₱<?php echo number_format($a['price'],2); ?></div>
            <div class="pk-ctrl">
              <button class="pk-btn rem" type="button" id="aminus-<?php echo $a['product_id']; ?>"
                      onclick="changeQty('addon','<?php echo $a['product_id']; ?>',-1)"
                      <?php echo $sq===0?'disabled':''; ?>>−</button>
              <span class="pk-qty" id="aqty-<?php echo $a['product_id']; ?>"><?php echo $sq; ?></span>
              <button class="pk-btn" type="button" id="aplus-<?php echo $a['product_id']; ?>"
                      onclick="changeQty('addon','<?php echo $a['product_id']; ?>',1)">+</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- SECTION 7: Photo -->
    <div class="ab-section">
      <div class="ab-section-header">
        <div class="ab-section-num">7</div>
        <div class="ab-section-title">Bouquet Photo</div>
        <span class="ab-section-badge">Optional</span>
      </div>
      <div class="ab-section-body">
        <?php if (!empty($b['image'])): ?>
          <div style="margin-bottom:12px">
            <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:8px">Current Photo</p>
            <img class="ab-existing-img" id="current-img"
                 src="images/<?php echo htmlspecialchars($b['image']); ?>"
                 alt="<?php echo htmlspecialchars($b['name']); ?>">
            <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);cursor:pointer;margin-top:4px">
              <input type="checkbox" id="remove-img" style="width:14px;height:14px;accent-color:var(--p3)">
              Remove current photo
            </label>
          </div>
        <?php endif; ?>
        <div class="ab-upload" id="upload-zone" onclick="document.getElementById('f-image').click()">
          <div id="upload-inner">
            <div class="ab-upload-icon">📷</div>
            <strong><?php echo !empty($b['image'])?'Replace with a new photo':'Click or drag to upload'; ?></strong>
            <p>JPG, PNG or WEBP &middot; max 5 MB</p>
          </div>
        </div>
        <input type="file" id="f-image" accept="image/*" style="display:none" onchange="previewPhoto(this)">
      </div>
    </div>

    <!-- Bottom bar -->
    <div style="display:flex;align-items:center;justify-content:space-between;padding-top:1rem;flex-wrap:wrap;gap:10px">
      <a href="products-admin.php" class="btn btn-ghost btn-sm" style="text-decoration:none;">Back to Products</a>
      <button type="button" class="btn btn-green" onclick="submitEdit()">
        <i class="ti ti-device-floppy" aria-hidden="true"></i> Save Changes
      </button>
    </div>
  </div><!-- /ab-main -->

  <!-- ══ RIGHT PANEL ══ -->
  <div class="ab-panel">
    <div class="ab-panel-title">Bouquet Summary</div>
    <div class="ab-preview-box" id="sum-preview">
      <?php if (!empty($b['image'])): ?>
        <img src="images/<?php echo htmlspecialchars($b['image']); ?>" alt="<?php echo htmlspecialchars($b['name']); ?>">
      <?php else: ?>🌸<?php endif; ?>
    </div>
    <table class="ab-sum-table">
      <tr><td>Name</td><td id="s-name"><?php echo htmlspecialchars($b['name']); ?></td></tr>
      <tr><td>Category</td><td id="s-cat"><?php echo htmlspecialchars($b['category']); ?></td></tr>
      <tr><td>Variation</td><td id="s-var"><?php echo htmlspecialchars($b['variation']); ?></td></tr>
      <tr><td>Wrapper</td><td id="s-wrap"><?php echo $currentWrapper?$currentWrapper['label']:'—'; ?></td></tr>
      <tr><td>Total stems</td><td id="s-stems">0</td></tr>
    </table>
    <div class="ab-sum-sep"></div>
    <div class="ab-sum-section-label">Flowers</div>
    <ul class="ab-ing-list" id="s-flower-list"><li><span class="ab-sum-none">Loading…</span></li></ul>
    <div class="ab-sum-section-label">Fillers</div>
    <ul class="ab-ing-list" id="s-filler-list"><li><span class="ab-sum-none">None</span></li></ul>
    <div class="ab-sum-section-label">Add-ons</div>
    <ul class="ab-ing-list" id="s-addon-list"><li><span class="ab-sum-none">None</span></li></ul>
    <div class="ab-sum-sep"></div>
    <div class="ab-pricing">
      <div class="ab-pricing-row"><span class="pk">Flower cost</span><span class="pv" id="s-flwcost">₱0.00</span></div>
      <div class="ab-pricing-row"><span class="pk">Filler cost</span><span class="pv" id="s-fillcost">₱0.00</span></div>
      <div class="ab-pricing-row"><span class="pk">Add-on cost</span><span class="pv" id="s-addcost">₱0.00</span></div>
      <div class="ab-pricing-total"><span>Est. Total</span><span id="s-total">₱0.00</span></div>
    </div>
    <div class="ab-cta">
      <button type="button" class="btn btn-green" onclick="submitEdit()">
        <i class="ti ti-device-floppy" aria-hidden="true"></i> Save Changes
      </button>
      <a href="products-admin.php" class="btn btn-ghost" style="text-decoration:none;">Discard &amp; Cancel</a>
    </div>
    <p class="ab-cta-note">
      Updates <code>bouquet</code> and replaces all rows<br>in <code>bouquet_product</code> for this bouquet.
    </p>
  </div>
</div><!-- /ab-shell -->
</div><!-- /main-area -->

<div id="fc-toast" class="toast"></div>
<script src="data.js"></script>
<script src="nav.js"></script>

<form id="real-form" action="db/update_bouquet.php" method="POST" enctype="multipart/form-data" style="display:none">
  <input type="hidden" name="bouquet_id"    value="<?php echo $b['bouquet_id']; ?>">
  <input type="hidden" name="old_image"     value="<?php echo htmlspecialchars($b['image']??''); ?>">
  <input type="hidden" name="category"      id="hf-cat">
  <input type="hidden" name="variation"     id="hf-var">
  <input type="hidden" name="name"          id="hf-name">
  <input type="hidden" name="description"   id="hf-desc">
  <input type="hidden" name="price"         id="hf-price" value="0">
  <input type="hidden" name="stock"         id="hf-stock">
  <input type="hidden" name="status"        id="hf-status">
  <input type="hidden" name="bouquet_type"  id="hf-btype">
  <input type="hidden" name="is_custom"     id="hf-iscustom">
  <input type="hidden" name="date_arrived"  id="hf-arrived">
  <input type="hidden" name="best_before"   id="hf-bestbefore">
  <input type="hidden" name="wrapper"       id="hf-wrapper">
  <input type="hidden" name="remove_image"  id="hf-remove-img" value="0">
  <input type="hidden" name="products_json" id="hf-products">
</form>

<script>
requireAuth('admin');
buildTopNav('products-admin');
buildAdminSidebar('products-admin.php');

const flowerQty = <?php echo json_encode((object)$saved_flowers); ?>;
const fillerQty = <?php echo json_encode((object)$saved_fillers); ?>;
const addonQty  = <?php echo json_encode((object)$saved_addons);  ?>;

let stemMin = 0, stemMax = 0;
let selWrapper = <?php echo $currentWrapper ? json_encode($currentWrapper['val']) : 'null'; ?>;
let isDirty = false;

function markDirty() {
  isDirty = true;
  document.getElementById('changed-badge').classList.add('show');
}

function updateStemLimits() {
  const sel = document.getElementById('f-variation');
  const opt = sel.options[sel.selectedIndex];
  stemMin = parseInt(opt.dataset.min || 0);
  stemMax = parseInt(opt.dataset.max || 0);
  updateStemBar(); updateSummary(); markDirty();
}

function totalStems() {
  return Object.values(flowerQty).reduce((s,v) => s+v, 0);
}

function updateStemBar() {
  const used = totalStems();
  const pct  = stemMax > 0 ? Math.min(100, Math.round(used/stemMax*100)) : 0;
  const fill = document.getElementById('stem-fill');
  fill.style.width = pct + '%';
  fill.className   = 'ab-stem-fill' + (used>=stemMin && stemMin>0 ? ' ready' : '');
  document.getElementById('stem-count-lbl').textContent = used + ' stem' + (used!==1?'s':'');
  let hint = '';
  if (!stemMax)             hint = 'Select a variation to set stem limits.';
  else if (used===0)        hint = 'Add at least ' + stemMin + ' stems.';
  else if (used<stemMin)    hint = 'Need ' + (stemMin-used) + ' more stem(s) — minimum is ' + stemMin + '.';
  else if (used>=stemMax)   hint = '✓ Maximum reached (' + stemMax + ' stems).';
  else                      hint = '✓ Good — up to ' + (stemMax-used) + ' more stem(s) allowed.';
  document.getElementById('stem-hint-lbl').textContent = hint;
  document.querySelectorAll('#flower-picker .pk-card').forEach(card => {
    const plus = document.getElementById('fplus-' + card.dataset.id);
    if (plus) plus.disabled = (used >= stemMax && stemMax > 0);
  });
}

function changeQty(type, id, delta) {
  const store  = type==='flower' ? flowerQty : (type==='filler' ? fillerQty : addonQty);
  const prefix = type==='flower' ? 'f' : (type==='filler' ? 'l' : 'a');
  if (type==='flower' && delta>0 && stemMax>0 && totalStems()>=stemMax) {
    toast('Maximum ' + stemMax + ' stems reached.', 'warn'); return;
  }
  const nxt = Math.max(0, (store[id]||0) + delta);
  if (nxt===0) delete store[id]; else store[id]=nxt;
  const qEl = document.getElementById(prefix+'qty-'+id);
  const mEl = document.getElementById(prefix+'minus-'+id);
  const cEl = document.getElementById((type==='flower'?'fpk-':(type==='filler'?'lkpk-':'apk-'))+id);
  if (qEl) qEl.textContent = nxt;
  if (mEl) mEl.disabled   = (nxt===0);
  if (cEl) cEl.classList.toggle('sel', nxt>0);
  if (type==='flower') updateStemBar();
  updateSummary(); markDirty();
}

function pickWrapper(el) {
  document.querySelectorAll('.swatch').forEach(s=>s.classList.remove('sel'));
  el.classList.add('sel');
  selWrapper = el.dataset.val;
  document.getElementById('wrapper-dot').style.background = el.style.background;
  document.getElementById('wrapper-label').textContent    = el.dataset.label;
  document.getElementById('wrapper-preview').style.display = 'flex';
  updateSummary(); markDirty();
}
function clearWrapper() {
  document.querySelectorAll('.swatch').forEach(s=>s.classList.remove('sel'));
  selWrapper = null;
  document.getElementById('wrapper-preview').style.display = 'none';
  updateSummary(); markDirty();
}

function filterAddons(btn) {
  document.querySelectorAll('#addon-tabs .pk-tab').forEach(t=>t.classList.remove('on'));
  btn.classList.add('on');
  const cat = btn.dataset.cat;
  document.querySelectorAll('#addon-picker .pk-card').forEach(card=>{
    card.style.display = (cat==='all'||card.dataset.cat===cat) ? '' : 'none';
  });
}

function previewPhoto(input) {
  if (!input.files||!input.files[0]) return;
  const url = URL.createObjectURL(input.files[0]);
  const zone = document.getElementById('upload-zone');
  zone.innerHTML = `<img src="${url}" style="max-height:150px;border-radius:var(--r);object-fit:cover;display:block;margin:0 auto">`;
  zone.style.padding = '.5rem';
  document.getElementById('sum-preview').innerHTML = `<img src="${url}" style="width:100%;height:100%;object-fit:cover">`;
  markDirty();
}

(function(){
  const zone = document.getElementById('upload-zone');
  zone.addEventListener('dragover', e=>{e.preventDefault();zone.classList.add('drag');});
  zone.addEventListener('dragleave', ()=>zone.classList.remove('drag'));
  zone.addEventListener('drop', e=>{
    e.preventDefault(); zone.classList.remove('drag');
    const file=e.dataTransfer.files[0]; if(!file) return;
    const dt=new DataTransfer(); dt.items.add(file);
    const inp=document.getElementById('f-image'); inp.files=dt.files;
    previewPhoto(inp);
  });
})();

function updateFreshHint() {
  const bb   = document.getElementById('f-bestbefore').value;
  const hint = document.getElementById('f-fresh-hint');
  if (!bb){hint.textContent='';return;}
  const today=new Date();today.setHours(0,0,0,0);
  const diff=Math.round((new Date(bb)-today)/86400000);
  if (diff<0)      {hint.textContent='⚠ Already expired.';         hint.style.color='var(--p3)';}
  else if(diff===0){hint.textContent='Expires today.';              hint.style.color='var(--p3)';}
  else if(diff<=3) {hint.textContent=diff+'d left — consider sale.';hint.style.color='#E65100';}
  else             {hint.textContent=diff+' day(s) remaining.';     hint.style.color='var(--g3)';}
}
document.getElementById('f-bestbefore').addEventListener('change', updateFreshHint);

const FLOWER_MAP={<?php foreach($flowers as $f):?>'<?php echo $f['product_id'];?>':{name:'<?php echo addslashes($f['product_name']);?>',price:<?php echo $f['price'];?>},<?php endforeach;?>};
const FILLER_MAP={<?php foreach($fillers as $f):?>'<?php echo $f['product_id'];?>':{name:'<?php echo addslashes($f['product_name']);?>',price:<?php echo $f['price'];?>},<?php endforeach;?>};
const ADDON_MAP ={<?php foreach($addons  as $a):?>'<?php echo $a['product_id'];?>':{name:'<?php echo addslashes($a['product_name']);?>',price:<?php echo $a['price'];?>},<?php endforeach;?>};

function fmtP(n){return '₱'+Number(n).toFixed(2);}
function buildIngList(store,map,unit){
  const e=Object.entries(store).filter(([,q])=>q>0);
  if(!e.length) return '<li><span class="ab-sum-none">None</span></li>';
  return e.map(([id,qty])=>{
    const name=map[id]?.name||id, price=map[id]?.price||0;
    return `<li><span class="ab-ing-name">${name}${unit?` <span style="color:var(--muted);font-size:10px">(${unit})</span>`:''}</span><span class="ab-ing-qty">×${qty} · ${fmtP(price*qty)}</span></li>`;
  }).join('');
}

function updateSummary(){
  document.getElementById('s-name').textContent=document.getElementById('f-name').value||'—';
  document.getElementById('s-cat').textContent =document.getElementById('f-cat').value ||'—';
  document.getElementById('s-var').textContent =document.getElementById('f-variation').value||'—';
  document.getElementById('s-wrap').textContent=selWrapper||'—';
  document.getElementById('s-stems').textContent=totalStems();
  document.getElementById('s-flower-list').innerHTML=buildIngList(flowerQty,FLOWER_MAP,'stem');
  document.getElementById('s-filler-list').innerHTML=buildIngList(fillerQty,FILLER_MAP,null);
  document.getElementById('s-addon-list').innerHTML =buildIngList(addonQty, ADDON_MAP, null);
  const flwC=Object.entries(flowerQty).reduce((s,[id,q])=>s+(FLOWER_MAP[id]?.price||0)*q,0);
  const filC=Object.entries(fillerQty).reduce((s,[id,q])=>s+(FILLER_MAP[id]?.price||0)*q,0);
  const addC=Object.entries(addonQty) .reduce((s,[id,q])=>s+(ADDON_MAP[id]?.price ||0)*q,0);
  document.getElementById('s-flwcost').textContent =fmtP(flwC);
  document.getElementById('s-fillcost').textContent=fmtP(filC);
  document.getElementById('s-addcost').textContent =fmtP(addC);
  document.getElementById('s-total').textContent   =fmtP(flwC+filC+addC);
}

['f-name','f-cat','f-variation'].forEach(id=>{
  const el=document.getElementById(id);
  if(el){el.addEventListener('change',()=>{updateSummary();markDirty();});
          el.addEventListener('input', ()=>{updateSummary();markDirty();});}
});

function submitEdit(){
  const required=[{id:'f-cat',label:'Category'},{id:'f-variation',label:'Variation'},
    {id:'f-name',label:'Name'},{id:'f-stock',label:'Stock'},
    {id:'f-arrived',label:'Date Arrived'},{id:'f-bestbefore',label:'Best Before'}];
  let errors=[];
  required.forEach(({id,label})=>{
    const el=document.getElementById(id);
    if(!el.value.trim()){el.closest('.ff')?.classList.add('err');errors.push(label);}
    else el.closest('.ff')?.classList.remove('err');
  });
  if(errors.length){toast('Required: '+errors.join(', '),'err');return;}
  if(new Date(document.getElementById('f-bestbefore').value)<new Date(document.getElementById('f-arrived').value)){
    document.getElementById('f-bestbefore').closest('.ff').classList.add('err');
    toast('Best Before cannot be earlier than Date Arrived.','err');return;
  }
  if(stemMin>0 && totalStems()<stemMin){
    toast('Add at least '+stemMin+' flower stems for a '+document.getElementById('f-variation').value+' bouquet.','warn');return;
  }
  const products=[];
  Object.entries(flowerQty).filter(([,q])=>q>0).forEach(([id,qty])=>products.push({product_id:id,quantity:qty,item_type:'flower',is_addons:0}));
  Object.entries(fillerQty).filter(([,q])=>q>0).forEach(([id,qty])=>products.push({product_id:id,quantity:qty,item_type:'filler',is_addons:0}));
  Object.entries(addonQty) .filter(([,q])=>q>0).forEach(([id,qty])=>products.push({product_id:id,quantity:qty,item_type:'addon', is_addons:1}));
  const flwC=Object.entries(flowerQty).reduce((s,[id,q])=>s+(FLOWER_MAP[id]?.price||0)*q,0);
  const filC=Object.entries(fillerQty).reduce((s,[id,q])=>s+(FILLER_MAP[id]?.price||0)*q,0);
  const addC=Object.entries(addonQty) .reduce((s,[id,q])=>s+(ADDON_MAP[id]?.price ||0)*q,0);
  document.getElementById('hf-cat').value       =document.getElementById('f-cat').value;
  document.getElementById('hf-var').value       =document.getElementById('f-variation').value;
  document.getElementById('hf-name').value      =document.getElementById('f-name').value;
  document.getElementById('hf-desc').value      =document.getElementById('f-desc').value;
  document.getElementById('hf-price').value     =(flwC+filC+addC).toFixed(2);
  document.getElementById('hf-stock').value     =document.getElementById('f-stock').value;
  document.getElementById('hf-status').value    =document.getElementById('f-status').value;
  document.getElementById('hf-btype').value     =document.getElementById('f-btype').value;
  document.getElementById('hf-iscustom').value  =document.getElementById('f-iscustom').value;
  document.getElementById('hf-arrived').value   =document.getElementById('f-arrived').value;
  document.getElementById('hf-bestbefore').value=document.getElementById('f-bestbefore').value;
  document.getElementById('hf-wrapper').value   =selWrapper||'';
  document.getElementById('hf-products').value  =JSON.stringify(products);
  const ri=document.getElementById('remove-img');
  document.getElementById('hf-remove-img').value=(ri&&ri.checked)?'1':'0';
  const fi=document.getElementById('f-image');
  if(fi.files.length>0){fi.name='image';document.getElementById('real-form').appendChild(fi);}
  isDirty=false;
  document.getElementById('real-form').submit();
}

document.querySelectorAll('input,select,textarea').forEach(el=>{
  el.addEventListener('change',()=>el.closest('.ff')?.classList.remove('err'));
  el.addEventListener('input', ()=>el.closest('.ff')?.classList.remove('err'));
});

window.addEventListener('beforeunload',e=>{if(isDirty){e.preventDefault();e.returnValue='';}});

updateStemLimits();
updateFreshHint();
updateSummary();
isDirty=false;
</script>
</body>
</html>