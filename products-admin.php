<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<?php include 'db/connection_db.php'; ?>

<?php
$count_sql    = "SELECT COUNT(*) AS total FROM bouquet";
$count_result = mysqli_query($conn, $count_sql);
$count_row    = mysqli_fetch_assoc($count_result);
$total_products = $count_row['total'];

$sql = "
  SELECT
    b.*,
    COALESCE(br.avg_rating, 0)    AS avg_rating,
    COALESCE(br.total_ratings, 0) AS total_ratings
  FROM bouquet b
  LEFT JOIN bouquet_ratings_view br ON b.bouquet_id = br.bouquet_id
  ORDER BY b.best_before ASC
";
$result   = mysqli_query($conn, $sql);
$bouquets = [];
while ($row = mysqli_fetch_assoc($result)) {
  $bouquets[] = $row;
}

$today = new DateTime('today');

function freshnessData(string $best_before, DateTime $today): array {
  if (!$best_before) return ['days' => null, 'label' => 'No date set', 'bar_pct' => 0, 'bar_cls' => '', 'label_cls' => 'text-muted', 'nudge' => false];
  $bb   = new DateTime($best_before);
  $diff = (int)$today->diff($bb)->format('%r%a');
  if ($diff < 0)   return ['days' => $diff, 'label' => 'Expired',       'bar_pct' => 0,                                  'bar_cls' => 'fresh-low', 'label_cls' => 'text-pink',  'nudge' => true];
  if ($diff === 0) return ['days' => 0,     'label' => 'Expires today', 'bar_pct' => 4,                                  'bar_cls' => 'fresh-low', 'label_cls' => 'text-pink',  'nudge' => true];
  if ($diff <= 3)  return ['days' => $diff, 'label' => $diff.'d left',  'bar_pct' => (int)round($diff/14*100),           'bar_cls' => 'fresh-mid', 'label_cls' => 'text-pink',  'nudge' => true];
  if ($diff <= 6)  return ['days' => $diff, 'label' => $diff.'d left',  'bar_pct' => (int)round($diff/14*100),           'bar_cls' => 'fresh-mid', 'label_cls' => 'text-muted', 'nudge' => false];
  return                  ['days' => $diff, 'label' => $diff.'d left',  'bar_pct' => min(100,(int)round($diff/14*100)),  'bar_cls' => 'fresh-ok',  'label_cls' => 'text-green', 'nudge' => false];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>FleurChase — Products & Add-ons</title>
<link rel="stylesheet" href="shared.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

<style>
.page-tabs{display:flex;background:var(--soft);border-radius:var(--r);padding:3px;gap:3px;width:fit-content;margin-bottom:1.5rem}
.ptab{padding:8px 22px;border-radius:8px;border:none;font-family:var(--font-b);font-size:13px;font-weight:500;cursor:pointer;background:none;color:var(--muted);transition:all .2s}
.ptab.active{background:white;color:var(--g2);box-shadow:0 1px 6px rgba(0,0,0,.08)}
.filter-bar{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:1rem}
.filt-btn{padding:5px 13px;border-radius:18px;border:1.5px solid var(--line);background:white;font-family:var(--font-b);font-size:12px;font-weight:500;cursor:pointer;color:var(--muted);transition:all .2s}
.filt-btn:hover{border-color:var(--g5);color:var(--g2)}.filt-btn.active{background:var(--g9);border-color:var(--g5);color:var(--g2);font-weight:600}
.star-rating{color:#D4891A;font-size:12px}
/* Upload zone — the hidden file input lives OUTSIDE this zone so innerHTML rewrites never destroy it */
.img-upload-zone{border:2px dashed var(--line);border-radius:var(--r);padding:1rem;text-align:center;cursor:pointer;transition:all .2s;background:var(--soft);position:relative}
.img-upload-zone:hover{border-color:var(--g5);background:var(--g9)}
.img-upload-zone.has-img{border-color:var(--g4);background:var(--g9);padding:.5rem}
.img-preview{max-width:100%;max-height:140px;border-radius:var(--r);object-fit:cover;display:block;margin:0 auto}
.prod-img{width:42px;height:42px;border-radius:var(--r);object-fit:cover;flex-shrink:0;display:block}
.prod-thumb-wrap{width:42px;height:42px;border-radius:var(--r);background:var(--soft);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden}
.cf-btn{padding:5px 12px;border-radius:16px;border:1.5px solid var(--line);background:white;font-family:var(--font-b);font-size:11px;cursor:pointer;color:var(--muted);transition:all .2s}
.cf-btn:hover{border-color:var(--g5);color:var(--g2)}.cf-btn.active{background:var(--g9);border-color:var(--g5);color:var(--g2);font-weight:600}


/* ── filter pills ── */
.bc-filter-row{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:1rem}
.bc-pill{padding:5px 14px;border-radius:20px;border:1.5px solid var(--line);background:var(--white);font-family:var(--font-b);font-size:12px;font-weight:500;cursor:pointer;color:var(--muted);transition:all .18s}
.bc-pill:hover{border-color:var(--g5);color:var(--g2)}
.bc-pill.on{background:var(--g9);border-color:var(--g4);color:var(--g2);font-weight:600}
.bc-pill.on-warn{background:#FFF8E1;border-color:#FFA000;color:#E65100;font-weight:600}

/* ── view toggle ── */
.view-toggle{display:flex;background:var(--soft);border-radius:var(--r);padding:3px;gap:3px}
.view-toggle button{width:32px;height:32px;border:none;border-radius:7px;background:none;cursor:pointer;font-size:16px;color:var(--muted);transition:all .18s;display:flex;align-items:center;justify-content:center;line-height:1}
.view-toggle button:hover{background:var(--line);color:var(--ink)}
.view-toggle button.active{background:var(--white);color:var(--g2);box-shadow:0 1px 5px rgba(0,0,0,.08)}

/* ── card grid ── */
.bc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-top:1rem}
.bc-card{background:var(--white);border-radius:var(--rl);border:1px solid var(--line);overflow:hidden;transition:box-shadow .2s,border-color .2s;display:flex;flex-direction:column}
.bc-card:hover{box-shadow:0 6px 24px rgba(15,61,31,.10);border-color:var(--g6)}
.bc-card.bc-warn{border-color:#FFA000}
.bc-card.bc-critical{border-color:var(--p4)}
.bc-card.hidden{display:none}

/* image */
.bc-img-wrap{position:relative;width:100%;height:148px;background:var(--soft);flex-shrink:0;overflow:hidden}
.bc-img-wrap img{width:100%;height:100%;object-fit:cover;display:block}
.bc-img-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:36px;color:var(--line)}
.bc-status-chip{position:absolute;top:9px;right:9px;font-size:10px;font-weight:700;padding:3px 9px;border-radius:8px;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap}
.bc-s-active  {background:var(--g9);color:var(--g2)}
.bc-s-oos     {background:#FFF8E1;color:#E65100}
.bc-s-inactive{background:var(--soft);color:var(--muted)}
.bc-s-sale    {background:var(--p9);color:var(--p2)}

/* card body */
.bc-body{padding:12px 14px 14px;display:flex;flex-direction:column;flex:1}
.bc-name{font-family:var(--font-d);font-size:17px;font-weight:700;color:var(--g1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:5px}
.bc-tags{display:flex;gap:5px;flex-wrap:wrap;margin-bottom:8px}
.bc-tag-cat {font-size:10px;font-weight:700;padding:3px 9px;border-radius:8px;text-transform:uppercase;letter-spacing:.4px;background:var(--g9);color:var(--g2)}
.bc-tag-type{font-size:10px;font-weight:700;padding:3px 9px;border-radius:8px;text-transform:uppercase;letter-spacing:.4px;background:#E3F2FD;color:#1565C0}
.bc-price-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.bc-price{font-family:var(--font-d);font-size:20px;font-weight:700;color:var(--g2)}
.bc-rating{font-size:11px;color:var(--muted)}
.bc-rating .star{color:#D4891A}

/* freshness */
.bc-fresh{margin-bottom:6px}
.bc-fresh-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px}
.bc-fresh-label{font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px}
.bc-fresh-arrived{font-size:10px;color:var(--muted)}
.bc-nudge{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:8px;background:var(--p9);color:var(--p2);font-size:10px;font-weight:700;letter-spacing:.3px;margin-bottom:7px}

/* creator */
.bc-creator{display:flex;align-items:center;gap:7px;margin-top:auto;padding-top:8px}
.bc-av{width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,var(--g4),var(--g2));display:flex;align-items:center;justify-content:center;font-size:9px;color:white;font-weight:700;flex-shrink:0}
.bc-creator-name{font-size:11px;color:var(--muted)}
.bc-div{height:1px;background:var(--line);margin:10px -14px}

a.btn-green,
a.btn-green:hover,
a.btn-green:focus {
  text-decoration: none;
}

/* card action buttons */
.bc-actions {
  display: flex;
  gap: 6px;
  align-items: center;
}

.bc-act {
  width: 34px;
  height: 34px;
  padding: 0;
  border: 0;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.bc-act i {
  font-size: 18px;
  line-height: 1;
}

.bc-act.view { background: #e8f1ff; color: #1d4ed8; }
.bc-act.sale { background: #fff4d6; color: #b45309; }
.bc-act.edit { background: #e9f8ef; color: #15803d; }
.bc-act.del  { background: #ffe8e8; color: #b91c1c; }

.bc-act:hover {
  filter: brightness(0.95);
}

/* ── list view table row extras ── */
.bc-list-row.hidden{display:none}
.bc-list-row.bc-warn td:first-child{border-left:3px solid #FFA000}
.bc-list-row.bc-critical td:first-child{border-left:3px solid var(--p4)}
.bc-list-img{width:48px;height:48px;object-fit:cover;border-radius:var(--r);display:block}
.bc-list-img-ph{width:48px;height:48px;border-radius:var(--r);background:var(--soft);display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--line)}
.tbl-acts{display:flex;gap:5px;align-items:center}
.tbl-act{display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:var(--r);border:1.5px solid var(--line);background:var(--white);font-family:var(--font-b);font-size:11px;font-weight:600;color:var(--muted);cursor:pointer;transition:all .15s;white-space:nowrap}
.tbl-act i{font-size:13px}
.tbl-act:hover{transform:translateY(-1px)}
.tbl-act.view:hover{background:var(--g9);border-color:var(--g4);color:var(--g2)}
.tbl-act.sale:hover{background:var(--p9);border-color:var(--p4);color:var(--p2)}
.tbl-act.edit:hover{background:#FFF8E1;border-color:#FFA000;color:#E65100}
.tbl-act.del:hover {background:var(--p9);border-color:var(--p3);color:var(--p2)}
.bc-fresh-inline{display:flex;align-items:center;gap:6px}
.fresh-bar-sm{height:5px;border-radius:3px;background:var(--line);overflow:hidden;width:60px;display:inline-block;vertical-align:middle;flex-shrink:0}
.fresh-fill-sm{height:100%;border-radius:3px}

/* legend & footer */
.bc-legend{display:flex;align-items:center;gap:14px;flex-wrap:wrap;font-size:11px;color:var(--muted)}
.bc-leg{display:flex;align-items:center;gap:5px}
.bc-leg-dot{width:8px;height:8px;border-radius:50%}
.bc-footer-row{display:flex;align-items:center;justify-content:space-between;margin-top:12px;flex-wrap:wrap;gap:8px}
.bc-count-label{font-size:12px;color:var(--muted)}
.bc-empty-card{display:none;grid-column:1/-1;text-align:center;padding:3rem 2rem}
.bc-empty-list{display:none}

#saleModalOverlay {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.5);
  z-index: 999;
}

/* Modal */
#saleModal {
  position: fixed;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  z-index: 1000;
}

.modal-content {
  background: #fff;
  padding: 20px 30px;
  border-radius: 8px;
  width: 300px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
  text-align: center;
  font-family: Arial, sans-serif;
}

.modal-content h3 {
  margin-bottom: 15px;
  color: #333;
}

.modal-content input {
  width: 80%;
  padding: 8px;
  margin: 10px 0;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.modal-actions {
  margin-top: 15px;
}

.btn-apply {
  background: #28a745;
  color: white;
  border: none;
  padding: 8px 15px;
  margin-right: 10px;
  border-radius: 5px;
  cursor: pointer;
}

.btn-cancel {
  background: #dc3545;
  color: white;
  border: none;
  padding: 8px 15px;
  border-radius: 5px;
  cursor: pointer;
}

.btn-apply:hover { background: #218838; }
.btn-cancel:hover { background: #c82333; }
</style>
</head>
<body>
<div id="top-nav" class="app-nav"></div>
<aside class="sidebar" id="fc-sidebar"></aside>
<div class="main-area">
<div class="p-page">
   <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;flex-wrap:wrap;gap:1rem">
    <div><div class="page-title">Bouquets</div></div>
    <a href="add_bouquet.php" class="btn btn-green btn-sm">+ Add Bouquet</a>
  </div>
  <!-- ── TOOLBAR ── -->
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:1rem">
  <h3 style="font-family:var(--font-d);font-size:22px;font-weight:700;color:var(--g1)">
    Products (<span id="prod-count"><?php echo $total_products; ?></span>)
  </h3>
  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <!-- search -->
    <div style="position:relative">
      <i class="ti ti-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:14px;color:var(--muted);pointer-events:none" aria-hidden="true"></i>
      <input id="bc-search" placeholder="Search bouquets..." oninput="bcApplyFilter()"
        style="padding:7px 12px 7px 32px;border:1.5px solid var(--line);border-radius:var(--r);font-family:var(--font-b);font-size:13px;background:var(--soft);color:var(--ink);outline:none;width:200px">
    </div>
    <!-- view toggle -->
    <div class="view-toggle">
      <button onclick="setView('cards',this)" class="active" id="vbtn-cards" title="Card view">⊞</button>
      <button onclick="setView('table',this)" id="vbtn-table" title="List view">☰</button>
    </div>
  </div>
</div>

 


<!-- ── FILTER PILLS ── -->
<div class="bc-filter-row" id="bc-pills">
  <button class="bc-pill on"      data-f="all"        onclick="bcSetFilter(this)">All</button>
  <button class="bc-pill"         data-f="customized"  onclick="bcSetFilter(this)">Customized</button>
  <button class="bc-pill"         data-f="ready-made"  onclick="bcSetFilter(this)">Ready-made</button>
  <button class="bc-pill"         data-f="seasonal"    onclick="bcSetFilter(this)">Seasonal</button>
  <button class="bc-pill"         data-f="gift-set"    onclick="bcSetFilter(this)">Gift sets</button>
  <button class="bc-pill"         data-f="promo"       onclick="bcSetFilter(this)">Promos</button>
  <button class="bc-pill"         data-f="sale"        onclick="bcSetFilter(this)">On sale</button>
  <button class="bc-pill on-warn" data-f="expiring"    onclick="bcSetFilter(this)">⚠ Expiring soon</button>
</div>
 
<!-- ── LEGEND ── -->
<div class="bc-legend" style="margin-bottom:12px">
  <span class="bc-leg"><span class="bc-leg-dot" style="background:var(--g4)"></span>Fresh (7d+)</span>
  <span class="bc-leg"><span class="bc-leg-dot" style="background:#FFA000"></span>Expiring (1–6d)</span>
  <span class="bc-leg"><span class="bc-leg-dot" style="background:var(--p4)"></span>Critical / expired</span>
</div>

<!-- ════════════════════════════════
     CARD VIEW
     ════════════════════════════════ -->
<div class="bc-grid" id="bc-grid">

<?php foreach ($bouquets as $row):
  $cat        = htmlspecialchars($row['category']);
  $name       = htmlspecialchars($row['name']);
  $price      = number_format($row['price'], 2);
  $status     = htmlspecialchars($row['status']);
  $type       = htmlspecialchars($row['variation'] ?? 'bouquet');
  $image      = htmlspecialchars($row['image'] ?? '');
  $id         = $row['bouquet_id'];
  $nameEsc    = htmlspecialchars($row['name'], ENT_QUOTES);
  $rating     = number_format($row['avg_rating'], 1);
  $ratingCnt  = (int)$row['total_ratings'];
  $creator    = htmlspecialchars($row['created_by'] ?? 'Admin');
  $parts      = explode(' ', trim($creator));
  $initials   = strtoupper(substr($parts[0],0,1).(isset($parts[1])?substr($parts[1],0,1):''));
  $arrived    = $row['date_arrived'] ?? '';
  $bestBefore = $row['best_before']  ?? '';
  $fresh      = freshnessData((string)$bestBefore, $today);
  $chipClass  = match(strtolower($status)) {
    'active'         => 'bc-s-active',
    'out of stock'   => 'bc-s-oos',
    'inactive'       => 'bc-s-inactive',
    'on sale','sale' => 'bc-s-sale',
    default          => 'bc-s-inactive',
  };
  $cardExtra      = '';
  if ($fresh['days'] !== null && $fresh['days'] <= 0)     $cardExtra = 'bc-critical';
  elseif ($fresh['days'] !== null && $fresh['days'] <= 3) $cardExtra = 'bc-warn';
  $isExpiring     = ($fresh['days'] !== null && $fresh['days'] <= 3) ? 'true' : 'false';
  $arrivedDisplay = $arrived ? date('M j', strtotime($arrived)) : '—';
?>

  <div class="bc-card <?php echo $cardExtra; ?>"
       data-cat="<?php echo $cat; ?>"
       data-name="<?php echo strtolower($name); ?>"
       data-creator="<?php echo strtolower($creator); ?>"
       data-expiring="<?php echo $isExpiring; ?>">

    <div class="bc-img-wrap">
      <?php if ($image): ?>
        <img src="images/<?php echo $image; ?>" alt="<?php echo $name; ?>">
      <?php else: ?>
        <div class="bc-img-ph"><i class="ti ti-photo" aria-hidden="true"></i></div>
      <?php endif; ?>
      <span class="bc-status-chip <?php echo $chipClass; ?>"><?php echo $status; ?></span>
    </div>

    <div class="bc-body">
      <div class="bc-name"><?php echo $name; ?></div>
      <div class="bc-tags">
        <span class="bc-tag-cat"><?php echo ucfirst($cat); ?></span>
        <span class="bc-tag-type"><?php echo ucfirst($type); ?></span>
      </div>
      <div class="bc-price-row">
        <span class="bc-price">₱<?php echo $price; ?></span>
        <span class="bc-rating"><span class="star">★</span> <?php echo $rating; ?> (<?php echo $ratingCnt; ?>)</span>
      </div>

      <div class="bc-fresh">
        <div class="bc-fresh-row">
          <span class="bc-fresh-label <?php echo $fresh['label_cls']; ?>">
            <i class="ti ti-leaf" aria-hidden="true"></i>
            <?php echo htmlspecialchars($fresh['label']); ?>
          </span>
          <span class="bc-fresh-arrived">in <?php echo $arrivedDisplay; ?></span>
        </div>
        <div class="fresh-bar" style="width:100%">
          <div class="fresh-fill <?php echo $fresh['bar_cls']; ?>" style="width:<?php echo $fresh['bar_pct']; ?>%"></div>
        </div>
      </div>

      <?php if ($fresh['nudge']): ?>
        <div class="bc-nudge">
          <i class="ti ti-alert-triangle" aria-hidden="true"></i>
          Consider marking on sale
        </div>
      <?php endif; ?>

      <div class="bc-creator">
        <div class="bc-av"><?php echo $initials; ?></div>
        <span class="bc-creator-name"><?php echo $creator; ?></span>
      </div>
      <div class="bc-div"></div>

      <div class="bc-actions">
        <button class="bc-act view"
                type="button"
                title="View inventory"
                aria-label="View inventory"
                onclick="openInventoryProd('<?php echo $id; ?>')">
          <i class="ti ti-box" aria-hidden="true"></i>
        </button>

        <button class="bc-act sale"
                type="button"
                title="Mark as sale"
                aria-label="Mark as sale"
                onclick="openSaleModal('<?php echo $id; ?>')">
          <i class="ti ti-tag" aria-hidden="true"></i>
        </button>
        

        <button class="bc-act edit"
        type="button"
        title="Edit"
        aria-label="Edit"
        onclick="window.location.href='edit-bouquet.php?id=<?php echo $id; ?>'">
          <i class="ti ti-edit" aria-hidden="true"></i>
        </button>


        <button class="bc-act del"
                type="button"
                title="Delete"
                aria-label="Delete"
                onclick="openDeleteProd('<?php echo $id; ?>', '<?php echo $nameEsc; ?>')">
          <i class="ti ti-trash" aria-hidden="true"></i>
        </button>
      </div> 
    </div>
  </div>

<?php endforeach; ?>

  <div class="bc-empty-card" id="bc-empty-card">
    <div class="empty-state">
      <div class="ei">🌸</div>
      <h3>No bouquets found</h3>
      <p>Try adjusting your search or filter.</p>
    </div>
  </div>
</div><!-- /.bc-grid -->

<!-- ════════════════════════════════
     LIST VIEW
     ════════════════════════════════ -->
<div class="table-wrap" id="bc-list" style="display:none">
  <table class="data-table">
    <thead>
      <tr>
        <th>Image</th>
        <th>Product</th>
        <th>Category</th>
        <th>Price</th>
        <th>Rating</th>
        <th>Freshness</th>
        <th>Status</th>
        <th>Created by</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="bc-list-tbody">

    <?php foreach ($bouquets as $row):
      $cat        = htmlspecialchars($row['category']);
      $name       = htmlspecialchars($row['name']);
      $price      = number_format($row['price'], 2);
      $status     = htmlspecialchars($row['status']);
      $type       = htmlspecialchars($row['variation'] ?? 'bouquet');
      $image      = htmlspecialchars($row['image'] ?? '');
      $id         = $row['bouquet_id'];
      $nameEsc    = htmlspecialchars($row['name'], ENT_QUOTES);
      $rating     = number_format($row['avg_rating'], 1);
      $ratingCnt  = (int)$row['total_ratings'];
      $creator    = htmlspecialchars($row['created_by'] ?? 'Admin');
      $parts      = explode(' ', trim($creator));
      $initials   = strtoupper(substr($parts[0],0,1).(isset($parts[1])?substr($parts[1],0,1):''));
      $arrived    = $row['date_arrived'] ?? '';
      $bestBefore = $row['best_before']  ?? '';
      $fresh      = freshnessData((string)$bestBefore, $today);
      $chipClass  = match(strtolower($status)) {
        'active'         => 'bc-s-active',
        'out of stock'   => 'bc-s-oos',
        'inactive'       => 'bc-s-inactive',
        'on sale','sale' => 'bc-s-sale',
        default          => 'bc-s-inactive',
      };
      $rowExtra       = '';
      if ($fresh['days'] !== null && $fresh['days'] <= 0)     $rowExtra = 'bc-critical';
      elseif ($fresh['days'] !== null && $fresh['days'] <= 3) $rowExtra = 'bc-warn';
      $isExpiring     = ($fresh['days'] !== null && $fresh['days'] <= 3) ? 'true' : 'false';
    ?>

      <tr class="bc-list-row <?php echo $rowExtra; ?>"
          data-cat="<?php echo $cat; ?>"
          data-name="<?php echo strtolower($name); ?>"
          data-creator="<?php echo strtolower($creator); ?>"
          data-expiring="<?php echo $isExpiring; ?>">

        <td>
          <?php if ($image): ?>
            <img class="bc-list-img" src="images/<?php echo $image; ?>" alt="<?php echo $name; ?>">
          <?php else: ?>
            <div class="bc-list-img-ph"><i class="ti ti-photo" aria-hidden="true"></i></div>
          <?php endif; ?>
        </td>

        <td>
          <div style="font-family:var(--font-d);font-size:15px;font-weight:700;color:var(--g1)"><?php echo $name; ?></div>
          <span class="bc-tag-type" style="margin-top:3px;display:inline-block"><?php echo ucfirst($type); ?></span>
        </td>

        <td><span class="bc-tag-cat"><?php echo ucfirst($cat); ?></span></td>

        <td>
          <span style="font-family:var(--font-d);font-size:16px;font-weight:700;color:var(--g2)">₱<?php echo $price; ?></span>
        </td>

        <td>
          <span style="color:#D4891A">★</span>
          <span style="font-size:12px;color:var(--muted)"><?php echo $rating; ?> (<?php echo $ratingCnt; ?>)</span>
        </td>

        <td>
          <div class="bc-fresh-inline">
            <div class="fresh-bar-sm">
              <div class="fresh-fill-sm <?php echo $fresh['bar_cls']; ?>" style="width:<?php echo $fresh['bar_pct']; ?>%"></div>
            </div>
            <span class="<?php echo $fresh['label_cls']; ?>" style="font-size:11px;font-weight:600;white-space:nowrap">
              <?php echo htmlspecialchars($fresh['label']); ?>
            </span>
          </div>
          <?php if ($fresh['nudge']): ?>
            <div style="margin-top:3px;font-size:10px;color:var(--p2);font-weight:600">
              <i class="ti ti-alert-triangle" aria-hidden="true"></i> Consider sale
            </div>
          <?php endif; ?>
        </td>

        <td><span class="bc-status-chip <?php echo $chipClass; ?>" style="position:static"><?php echo $status; ?></span></td>

        <td>
          <div style="display:flex;align-items:center;gap:6px">
            <div class="bc-av"><?php echo $initials; ?></div>
            <span style="font-size:12px;color:var(--muted)"><?php echo $creator; ?></span>
          </div>
        </td>

        <td>
          <div class="tbl-acts">
            <button class="tbl-act view" aria-label="View inventory" onclick="openInventoryProd('<?php echo $id; ?>')">
              <i class="ti ti-box" aria-hidden="true"></i> Inventory
            </button>
            <button class="tbl-act sale" aria-label="Mark as sale" onclick="openSaleModal('<?php echo $id; ?>')">
              <i class="ti ti-tag" aria-hidden="true"></i> Sale
            </button>
            <button class="tbl-act edit" aria-label="Edit" onclick="window.location.href='edit-bouquet.php?id=<?php echo $id; ?>'">
              <i class="ti ti-edit" aria-hidden="true"></i> Edit
            </button>
            <button class="tbl-act del" aria-label="Delete" onclick="openDeleteProd('<?php echo $id; ?>', '<?php echo $nameEsc; ?>')">
              <i class="ti ti-trash" aria-hidden="true"></i> Delete
            </button>
          </div>
        </td>
      </tr>

    <?php endforeach; ?>

      <tr class="bc-empty-list" id="bc-empty-list">
        <td colspan="9">
          <div class="empty-state">
            <div class="ei">🌸</div>
            <h3>No bouquets found</h3>
            <p>Try adjusting your search or filter.</p>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div><!-- /#bc-list -->


  </div>

</div>
</div>


<!-- PRODUCT MODAL -->
<div class="modal-overlay" id="prod-modal">
  <div class="modal-box" style="max-width:580px">
    <div class="modal-head"><h3 id="prod-modal-title">Add Product</h3><button class="modal-close" onclick="closeModal('prod-modal')">✕</button></div>
    <div id="prod-modal-body"></div>
  </div>
</div>

<!-- ADDON MODAL -->
<div class="modal-overlay" id="addon-modal">
  <div class="modal-box" style="max-width:460px">
    <div class="modal-head"><h3 id="addon-modal-title">Add Add-on</h3><button class="modal-close" onclick="closeModal('addon-modal')">✕</button></div>
    <div id="addon-modal-body"></div>
  </div>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="del-modal">
  <div class="modal-box" style="max-width:360px">
    <div class="modal-head"><h3>Confirm Delete</h3><button class="modal-close" onclick="closeModal('del-modal')">✕</button></div>
    <p style="font-size:13px;color:var(--muted);margin-bottom:1.5rem">Delete <strong id="del-name"></strong>? This cannot be undone.</p>
    <div style="display:flex;gap:8px">
      <button class="btn btn-danger" style="flex:1;justify-content:center" onclick="confirmDelete()">Delete</button>
      <button class="btn btn-ghost" style="flex:1;justify-content:center" onclick="closeModal('del-modal')">Cancel</button>
    </div>
  </div>
</div>
<!-- Modal Overlay -->
<div id="saleModalOverlay" style="display:none;"></div>

<!-- Modal -->
<div id="saleModal" style="display:none;">
  <div class="modal-content">
    <h3>Apply Discount</h3>
    <label for="discount">Enter discount % (10–70):</label>
    <input type="number" id="discount" min="10" max="70" step="1">
    <div class="modal-actions">
      <button class="btn-apply" onclick="applySale()">Apply</button>
      <button class="btn-cancel" onclick="closeSaleModal()">Cancel</button>
    </div>
  </div>
</div>

<div id="fc-toast" class="toast"></div>

<script src="data.js"></script>
<script src="nav.js"></script>

<script>
requireAuth('admin');
buildTopNav('products-admin');
buildAdminSidebar('products-admin.php');

let activeTab = 'products';
let editingProdId = null;
let pendingImg = null;
let deletingId = null;
let deleteType = null;
let bcActiveFilter = 'all';
let bcCurrentView  = 'cards';
let currentBouquetId = null;

function openSaleModal(id) {
  currentBouquetId = id;
  document.getElementById('saleModal').style.display = 'block';
  document.getElementById('saleModalOverlay').style.display = 'block';
}

function closeSaleModal() {
  document.getElementById('saleModal').style.display = 'none';
  document.getElementById('saleModalOverlay').style.display = 'none';
  document.getElementById('discount').value = '';
  currentBouquetId = null;
}

function applySale() {
  const discount = parseInt(document.getElementById('discount').value, 10);
  if (isNaN(discount) || discount < 10 || discount > 70) {
    alert("Discount must be between 10% and 70%.");
    return;
  }

  fetch('db/market_sale.php?id=' + encodeURIComponent(currentBouquetId) + '&discount=' + discount)
    .then(r => r.json())
    .then(d => {
      if (d.success) { 
        toast('Marked as sale!'); location.reload(); }
      else toast(d.error || 'Something went wrong.', 'err');
    })
    .catch(() => alert('Could not reach server.'));
}

function setView(v, btn) {
  bcCurrentView = v;
  document.querySelectorAll('.view-toggle button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  const grid = document.getElementById('bc-grid');
  const list = document.getElementById('bc-list');

  if (v === 'cards') {
    grid.style.display = 'grid';
    list.style.display = 'none';
  } else {
    grid.style.display = 'none';
    list.style.display = 'block';
  }

  bcApplyFilter();
}

/* ── FILTER PILLS ── */
function bcSetFilter(btn) {
  bcActiveFilter = btn.dataset.f;
  document.querySelectorAll('.bc-pill').forEach(p => {
    p.classList.remove('on');
    p.classList.remove('on-warn');
  });
  btn.classList.add(bcActiveFilter === 'expiring' ? 'on-warn' : 'on');
  bcApplyFilter();
}

/* ── APPLY FILTER + SEARCH ── */
function bcApplyFilter() {
  const q       = document.getElementById('bc-search').value.toLowerCase();
  const cards   = document.querySelectorAll('.bc-card');
  const rows    = document.querySelectorAll('.bc-list-row');
  let visible   = 0;

  function matches(el) {
    const matchCat = bcActiveFilter === 'all'
      || el.dataset.cat === bcActiveFilter
      || (bcActiveFilter === 'expiring' && el.dataset.expiring === 'true');
    const matchQ = !q
      || el.dataset.name.includes(q)
      || el.dataset.creator.includes(q)
      || el.dataset.cat.includes(q);
    return matchCat && matchQ;
  }

  cards.forEach(c => {
    if (matches(c)) { c.classList.remove('hidden'); visible++; }
    else            { c.classList.add('hidden'); }
  });

  rows.forEach(r => {
    if (matches(r)) { r.classList.remove('hidden'); visible++; }
    else            { r.classList.add('hidden'); }
  });

  /* each item exists in both views — divide by 2 for real count */
  const realCount = visible / 2;

  document.getElementById('prod-count').textContent     = realCount;
  document.getElementById('bc-count-label').textContent = realCount;

  const emptyCard = document.getElementById('bc-empty-card');
  const emptyList = document.getElementById('bc-empty-list');
  emptyCard.style.display = realCount === 0 ? 'block' : 'none';
  emptyList.style.display = realCount === 0 ? 'table-row' : 'none';
}

function openInventoryProd(id) {
  window.location.href = 'inventory-admin.php?id=' + encodeURIComponent(id);
}

function markAsSale(id) {
  fetch('db/mark_sale.php?id=' + encodeURIComponent(id))
    .then(r => r.json())
    .then(d => {
      if (d.success) { toast('Marked as sale!'); location.reload(); }
      else toast(d.error || 'Something went wrong.', 'err');
    })
    .catch(() => toast('Could not reach server.', 'err'));
}

function renderProducts() { bcApplyFilter(); }
// ── OPEN / SAVE PRODUCT ──────────────────────────────────────
function openAddModal() {
  editingProdId = null;
  pendingImg = null;

  document.getElementById('prod-modal-title').textContent = 'Add Product';
  document.getElementById('prod-modal-body').innerHTML = getProdModalBody(null);

  openModal('prod-modal');
}

function saveProd() {
  const form = document.getElementById('prod-form');
  if (!form) return;

  const name = document.getElementById('p-name').value.trim();
  const price = parseFloat(document.getElementById('p-price').value);
  const stock = parseInt(document.getElementById('p-stock').value);

  if (!name || isNaN(price) || isNaN(stock)) {
    toast('Please fill in the required fields.', 'err');
    return;
  }

  form.submit();
}

//EDIT MODAL
function openEditProd(id) {
  fetch('db/get_bouquet.php?id=' + encodeURIComponent(id))
    .then(response => response.json())
    .then(p => {
      if (p.error) {
        toast(p.error, 'err');
        return;
      }

      editingProdId = p.bouquet_id;
      pendingImg = p.image || null;

      document.getElementById('prod-modal-title').textContent = 'Edit: ' + p.name;
      document.getElementById('prod-modal-body').innerHTML = getProdModalBody(p);

      openModal('prod-modal');
    })
    .catch(() => {
      toast('Failed to load bouquet details.', 'err');
    });
}
//MODAL BODY
function getProdModalBody(p) {
  const pv = p || {};
  const isEdit = !!pv.bouquet_id;

  return `
    <form id="prod-form" action="${isEdit ? 'db/update_bouquet.php' : 'db/add_bouquet.php'}" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="bouquet_id" value="${pv.bouquet_id || ''}">
      <input type="hidden" name="old_image" value="${pv.image || ''}">

      <div class="fg2">
        <div class="ff">
          <label>Category *</label>
          <select id="p-cat" name="category" required>
            <option value="ready-made" ${pv.category === 'ready-made' ? 'selected' : ''}>Ready-Made</option>
            <option value="seasonal" ${pv.category === 'seasonal' ? 'selected' : ''}>Seasonal</option>
            <option value="customized" ${pv.category === 'customized' ? 'selected' : ''}>Customized</option>
            <option value="gift-set" ${pv.category === 'gift-set' ? 'selected' : ''}>Gift Set</option>
            <option value="promo" ${pv.category === 'promo' ? 'selected' : ''}>Promo</option>
            <option value="sale" ${pv.category === 'sale' ? 'selected' : ''}>Sale</option>
          </select>
        </div>

        <div class="ff">
          <label>Variation*</label>
          <select id="p-variation" name="variation" required>
            <option value="small" ${pv.variation === 'small' ? 'selected' : ''}>Small</option>
            <option value="medium" ${pv.variation === 'medium' ? 'selected' : ''}>Medium</option>
            <option value="large" ${pv.variation === 'large' ? 'selected' : ''}>Large</option>
          </select>
        </div>

        
      </div>

      <div class="ff">
        <label>Name *</label>
        <input id="p-name" name="name" value="${pv.name || ''}" required>
      </div>

      <div class="ff">
        <label>Description</label>
        <textarea id="p-desc" name="description" style="border:1.5px solid var(--line);border-radius:var(--r);padding:9px 12px;font-family:var(--font-b);font-size:13px;width:100%;resize:none;height:55px;outline:none;background:var(--soft)">${pv.description || ''}</textarea>
      </div>

      <div class="ff">
        <label>Product Photo</label>
        ${pv.image ? `<img src="images/${pv.image}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;margin-bottom:8px">` : ''}
        <input type="file" id="prod-img" name="image" accept="image/*">
      </div>

      <div class="fg3">
        <div class="ff">
          <label>Price (₱) *</label>
          <input id="p-price" name="price" type="number" value="${pv.price || ''}" min="1" step="0.01" required>
        </div>

        <div class="ff">
          <label>Stock *</label>
          <input id="p-stock" name="stock" type="number" value="${pv.stock || ''}" min="0" required>
        </div>

        <div class="ff">
          <label>Status</label>
          <select id="p-status" name="status">
            <option value="Active" ${pv.status === 'Active' ? 'selected' : ''}>Active</option>
            <option value="Out of Stock" ${pv.status === 'Out of Stock' ? 'selected' : ''}>Out of Stock</option>
            <option value="Inactive" ${pv.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
          </select>
        </div>
      </div>

      <div class="fg2">
        

        <div class="ff">
          <label>Custom Bouquet?</label>
          <select id="p-is-custom" name="is_custom">
            <option value="0" ${pv.is_custom == 0 ? 'selected' : ''}>No</option>
            <option value="1" ${pv.is_custom == 1 ? 'selected' : ''}>Yes</option>
          </select>
        </div>
      </div>

      <button type="button" class="btn btn-green" style="width:100%;justify-content:center;margin-top:.5rem" onclick="saveProd()">
        ${isEdit ? 'Save Changes' : 'Add Product'}
      </button>
    </form>
  `;
}

//DELETE MODAL
function openDeleteProd(id, name) {
  deletingId = id;
  deleteType = 'prod';

  document.getElementById('del-name').textContent = name;
  openModal('del-modal');
}

function confirmDelete() {
  if (deleteType === 'prod') {
    window.location.href = 'db/delete_bouquet.php?id=' + encodeURIComponent(deletingId);
  }
}


//FILTER PRODUCTS
let prodFilter = 'all';

function setProdFilter(filter, btn) {
  prodFilter = filter;

  document.querySelectorAll('#prod-filter-bar .filt-btn').forEach(button => {
    button.classList.remove('active');
  });

  btn.classList.add('active');

  renderProducts();
}

function renderProducts() {
  const q = document.getElementById('prod-search').value.toLowerCase();
  const rows = document.querySelectorAll('#prod-tbody tr');

  let visibleCount = 0;

  rows.forEach(row => {
    const category = row.dataset.category || '';
    const rowText = row.textContent.toLowerCase();

    const matchesCategory = prodFilter === 'all' || category === prodFilter;
    const matchesSearch = !q || rowText.includes(q);

    if (matchesCategory && matchesSearch) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });

  document.getElementById('prod-count').textContent = visibleCount;
}


</script>

<!-- ════════════════════════════════
     ADD BOUQUET MODAL
     ════════════════════════════════ -->
<div class="modal-overlay" id="add-bouquet-modal">
  <div class="modal-box" style="max-width:640px;width:100%">

    <div class="modal-head">
      <h3><em style="color:var(--p4);font-style:italic">New</em> Bouquet</h3>
      <button class="modal-close" onclick="closeAddModal()" aria-label="Close">✕</button>
    </div>

    <form id="add-bouquet-form" action="db/add_bouquet.php" method="POST" enctype="multipart/form-data">

      <!-- ── Row 1: Category + Variation ── -->
      <div class="fg2">
        <div class="ff">
          <label>Category *</label>
          <select name="category" id="ab-category" required>
            <option value="" disabled selected>Select category</option>
            <option value="customized">Customized</option>
            <option value="ready-made">Ready-made</option>
            <option value="seasonal">Seasonal</option>
            <option value="gift-set">Gift sets</option>
            <option value="promo">Promos</option>
            <option value="sale">On sale</option>
          </select>
        </div>
        <div class="ff">
          <label>Variation *</label>
          <select name="variation" id="ab-variation" required>
            <option value="" disabled selected>Select size</option>
            <option value="small">Small</option>
            <option value="medium">Medium</option>
            <option value="large">Large</option>
          </select>
        </div>
      </div>

      <!-- ── Name ── -->
      <div class="ff">
        <label>Bouquet Name *</label>
        <input type="text" name="name" id="ab-name" placeholder="e.g. Rosé Reverie" required>
      </div>

      <!-- ── Description ── -->
      <div class="ff">
        <label>Description</label>
        <textarea name="description" id="ab-desc" rows="2"
          style="border:1.5px solid var(--line);border-radius:var(--r);padding:10px 12px;font-family:var(--font-b);font-size:13px;color:var(--ink);outline:none;transition:border-color .2s;background:var(--soft);width:100%;resize:vertical"
          placeholder="Brief description of this bouquet…"></textarea>
      </div>

      <!-- ── Row 2: Price + Stock ── -->
      <div class="fg2">
        <div class="ff">
          <label>Price (₱) *</label>
          <input type="number" name="price" id="ab-price" min="1" step="0.01" placeholder="0.00" required>
        </div>
        <div class="ff">
          <label>Stock *</label>
          <input type="number" name="stock" id="ab-stock" min="0" placeholder="0" required>
        </div>
      </div>

      <!-- ── Row 3: Arrived + Best Before ── -->
      <div class="fg2">
        <div class="ff">
          <label>Date Arrived *</label>
          <input type="date" name="date_arrived" id="ab-arrived" required>
        </div>
        <div class="ff">
          <label>Best Before *</label>
          <input type="date" name="best_before" id="ab-bestbefore" required>
        </div>
      </div>

      <!-- ── Row 4: Status + Is Customized ── -->
      <div class="fg2">
        <div class="ff">
          <label>Status</label>
          <select name="status" id="ab-status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Out of Stock">Out of Stock</option>
          </select>
        </div>
        <div class="ff">
          <label>Customized Order?</label>
          <select name="is_custom" id="ab-iscustom">
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
      </div>

      <!-- ── Wrapper Color ── -->
      <div class="ff">
        <label>Wrapper Color</label>
        <div id="ab-wrapper-picker" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:2px">
          <?php
          $wrapperColors = [
            ['val'=>'white',       'hex'=>'#FFFFFF', 'label'=>'White'],
            ['val'=>'ivory',       'hex'=>'#FFFFF0', 'label'=>'Ivory'],
            ['val'=>'blush',       'hex'=>'#FFB6C1', 'label'=>'Blush'],
            ['val'=>'rose',        'hex'=>'#FF007F', 'label'=>'Rose'],
            ['val'=>'red',         'hex'=>'#E63946', 'label'=>'Red'],
            ['val'=>'coral',       'hex'=>'#FF6B6B', 'label'=>'Coral'],
            ['val'=>'peach',       'hex'=>'#FFCBA4', 'label'=>'Peach'],
            ['val'=>'gold',        'hex'=>'#FFD700', 'label'=>'Gold'],
            ['val'=>'sage',        'hex'=>'#B2C9A4', 'label'=>'Sage'],
            ['val'=>'forest',      'hex'=>'#2E8B57', 'label'=>'Forest'],
            ['val'=>'teal',        'hex'=>'#008080', 'label'=>'Teal'],
            ['val'=>'sky',         'hex'=>'#87CEEB', 'label'=>'Sky'],
            ['val'=>'navy',        'hex'=>'#1B2A6B', 'label'=>'Navy'],
            ['val'=>'lavender',    'hex'=>'#E6DEFF', 'label'=>'Lavender'],
            ['val'=>'purple',      'hex'=>'#7B2FBE', 'label'=>'Purple'],
            ['val'=>'kraft',       'hex'=>'#C4A35A', 'label'=>'Kraft'],
            ['val'=>'black',       'hex'=>'#1C1A17', 'label'=>'Black'],
          ];
          foreach ($wrapperColors as $wc): ?>
            <label title="<?php echo $wc['label']; ?>" style="cursor:pointer">
              <input type="radio" name="wrapper" value="<?php echo $wc['val']; ?>"
                     style="display:none"
                     onchange="selectWrapper(this)">
              <span class="ab-swatch"
                    style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:<?php echo $wc['hex']; ?>;border:2px solid var(--line);transition:all .15s;box-shadow:inset 0 0 0 1px rgba(0,0,0,.08)"
                    data-hex="<?php echo $wc['hex']; ?>">
              </span>
            </label>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="wrapper_color" id="ab-wrapper-val" value="">
        <div id="ab-wrapper-preview" style="display:none;align-items:center;gap:8px;margin-top:8px;font-size:12px;color:var(--muted)">
          <span id="ab-wrapper-dot" style="display:inline-block;width:14px;height:14px;border-radius:50%;border:1.5px solid var(--line)"></span>
          <span id="ab-wrapper-name"></span>
          <button type="button" onclick="clearWrapper()" style="background:none;border:none;font-size:11px;color:var(--muted);cursor:pointer;text-decoration:underline">Clear</button>
        </div>
        <p class="hint">Choose the wrapping paper colour for this bouquet.</p>
      </div>

      <!-- ── Product Image ── -->
      <div class="ff">
        <label>Product Photo</label>
        <div class="upload-zone" id="ab-upload-zone" onclick="document.getElementById('ab-image-input').click()">
          <div id="ab-upload-inner">
            <div class="uz-icon">📷</div>
            <strong>Click to upload photo</strong>
            <p>JPG, PNG or WEBP · max 5 MB</p>
          </div>
        </div>
        <input type="file" name="image" id="ab-image-input" accept="image/*"
               style="display:none" onchange="previewImage(this)">
        <div class="upload-ok" id="ab-upload-ok">✓ Photo selected</div>
      </div>

      <!-- ── Freshness preview ── -->
      <div id="ab-freshness-preview"
           style="display:none;background:var(--soft);border:1px solid var(--line);border-radius:var(--r);padding:10px 14px;margin-bottom:12px">
        <div style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Freshness Preview</div>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="fresh-bar" style="width:160px;flex-shrink:0">
            <div class="fresh-fill" id="ab-fresh-fill" style="width:0%"></div>
          </div>
          <span id="ab-fresh-label" style="font-size:12px;font-weight:600"></span>
        </div>
        <div id="ab-fresh-nudge" style="display:none;margin-top:6px;font-size:11px;color:var(--p2);font-weight:600">
          ⚠ This bouquet is expiring very soon — consider marking it on sale.
        </div>
      </div>

      <!-- ── Submit ── -->
      <div style="display:flex;gap:8px;margin-top:.5rem">
        <button type="button" class="btn btn-green" style="flex:1;justify-content:center" onclick="submitAddBouquet()">
          <i class="ti ti-plus" aria-hidden="true"></i> Add Bouquet
        </button>
        <button type="button" class="btn btn-ghost" style="flex:1;justify-content:center" onclick="closeAddModal()">
          Cancel
        </button>
      </div>

    </form>
  </div>
</div>

<style>
/* swatch selected state */
.ab-swatch.selected{border-color:var(--g3)!important;box-shadow:0 0 0 3px rgba(46,150,82,.22),inset 0 0 0 1px rgba(0,0,0,.08)!important;transform:scale(1.15)}
.ab-swatch:hover{transform:scale(1.1);border-color:var(--muted)!important}
/* upload zone drag state */
#ab-upload-zone.drag{border-color:var(--g5);background:var(--g9)}
</style>

<script>
/* ── MODAL OPEN / CLOSE ── */
function openAddModal() {
  /* reset form */
  document.getElementById('add-bouquet-form').reset();
  document.getElementById('ab-wrapper-val').value = '';
  document.getElementById('ab-wrapper-preview').style.display = 'none';
  document.getElementById('ab-upload-ok').style.display = 'none';
  document.getElementById('ab-freshness-preview').style.display = 'none';
  document.getElementById('ab-upload-inner').innerHTML =
    '<div class="uz-icon">📷</div><strong>Click to upload photo</strong><p>JPG, PNG or WEBP · max 5 MB</p>';
  document.querySelectorAll('.ab-swatch').forEach(s => s.classList.remove('selected'));

  /* default today for date_arrived */
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('ab-arrived').value = today;

  openModal('add-bouquet-modal');
}

function closeAddModal() {
  closeModal('add-bouquet-modal');
}

/* ── WRAPPER COLOR ── */
function selectWrapper(radio) {
  document.querySelectorAll('.ab-swatch').forEach(s => s.classList.remove('selected'));
  const swatch = radio.nextElementSibling;
  swatch.classList.add('selected');
  document.getElementById('ab-wrapper-val').value = radio.value;

  const label = radio.closest('label').getAttribute('title');
  const hex   = swatch.dataset.hex;
  document.getElementById('ab-wrapper-dot').style.background  = hex;
  document.getElementById('ab-wrapper-name').textContent      = label;
  document.getElementById('ab-wrapper-preview').style.display = 'flex';
}

function clearWrapper() {
  document.querySelectorAll('input[name="wrapper"]').forEach(r => r.checked = false);
  document.querySelectorAll('.ab-swatch').forEach(s => s.classList.remove('selected'));
  document.getElementById('ab-wrapper-val').value = '';
  document.getElementById('ab-wrapper-preview').style.display = 'none';
}

/* ── IMAGE PREVIEW ── */
function previewImage(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  const url  = URL.createObjectURL(file);
  const zone = document.getElementById('ab-upload-zone');
  zone.innerHTML = `<img src="${url}" style="max-height:140px;border-radius:var(--r);object-fit:cover;display:block;margin:0 auto">`;
  zone.style.padding = '.5rem';
  document.getElementById('ab-upload-ok').style.display = 'block';
}

/* ── FRESHNESS PREVIEW ── */
(function() {
  function updateFreshPreview() {
    const arrived    = document.getElementById('ab-arrived').value;
    const bestBefore = document.getElementById('ab-bestbefore').value;
    const preview    = document.getElementById('ab-freshness-preview');
    const fill       = document.getElementById('ab-fresh-fill');
    const label      = document.getElementById('ab-fresh-label');
    const nudge      = document.getElementById('ab-fresh-nudge');

    if (!bestBefore) { preview.style.display = 'none'; return; }

    const today = new Date(); today.setHours(0,0,0,0);
    const bb    = new Date(bestBefore); bb.setHours(0,0,0,0);
    const diff  = Math.round((bb - today) / 86400000);

    preview.style.display = 'block';

    let pct = 0, cls = '', txt = '';
    if (diff < 0)       { pct = 0;                         cls = 'fresh-low'; txt = 'Expired'; }
    else if (diff === 0){ pct = 4;                          cls = 'fresh-low'; txt = 'Expires today'; }
    else if (diff <= 3) { pct = Math.round(diff/14*100);   cls = 'fresh-mid'; txt = diff + 'd left'; }
    else if (diff <= 6) { pct = Math.round(diff/14*100);   cls = 'fresh-mid'; txt = diff + 'd left'; }
    else                { pct = Math.min(100,Math.round(diff/14*100)); cls = 'fresh-ok'; txt = diff + 'd left'; }

    fill.style.width = pct + '%';
    fill.className   = 'fresh-fill ' + cls;
    label.textContent = txt;
    label.className   = diff <= 3 ? 'text-pink' : (diff <= 6 ? 'text-muted' : 'text-green');
    label.style.fontWeight = '600';
    label.style.fontSize   = '12px';
    nudge.style.display    = diff <= 3 ? 'block' : 'none';
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('ab-bestbefore').addEventListener('change', updateFreshPreview);
    document.getElementById('ab-arrived').addEventListener('change', updateFreshPreview);
  });
})();

/* ── DRAG & DROP on upload zone ── */
(function() {
  document.addEventListener('DOMContentLoaded', function() {
    const zone = document.getElementById('ab-upload-zone');
    if (!zone) return;
    zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag'));
    zone.addEventListener('drop', e => {
      e.preventDefault();
      zone.classList.remove('drag');
      const file = e.dataTransfer.files[0];
      if (!file) return;
      const input = document.getElementById('ab-image-input');
      const dt    = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;
      previewImage(input);
    });
  });
})();

/* ── SUBMIT ── */
function submitAddBouquet() {
  const required = ['ab-category','ab-variation','ab-name','ab-price','ab-stock','ab-arrived','ab-bestbefore'];
  let ok = true;
  required.forEach(id => {
    const el = document.getElementById(id);
    if (!el.value.trim()) {
      el.closest('.ff').classList.add('err');
      ok = false;
    } else {
      el.closest('.ff').classList.remove('err');
    }
  });

  if (!ok) { toast('Please fill in all required fields.', 'err'); return; }

  /* validate best_before >= date_arrived */
  const arrived    = new Date(document.getElementById('ab-arrived').value);
  const bestBefore = new Date(document.getElementById('ab-bestbefore').value);
  if (bestBefore < arrived) {
    document.getElementById('ab-bestbefore').closest('.ff').classList.add('err');
    toast('Best Before cannot be earlier than Date Arrived.', 'err');
    return;
  }

  document.getElementById('add-bouquet-form').submit();
}

/* clear err state on input */
document.querySelectorAll('#add-bouquet-form input, #add-bouquet-form select').forEach(el => {
  el.addEventListener('input', () => el.closest('.ff') && el.closest('.ff').classList.remove('err'));
  el.addEventListener('change', () => el.closest('.ff') && el.closest('.ff').classList.remove('err'));
});
</script>

</body>
</html>
