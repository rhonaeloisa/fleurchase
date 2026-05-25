<?php
session_start();
include 'db/connection_db.php';

/* ── Flash messages ── */
$flash_ok  = $_SESSION['flash_ok']  ?? ''; unset($_SESSION['flash_ok']);
$flash_err = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);

/* ── Load settings ── */
$settings = [];
$res = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key ASC");
while ($row = mysqli_fetch_assoc($res)) {
  $settings[$row['setting_key']] = $row;
}

/* ── Load delivery zones ── */
$zones = [];
$res2 = mysqli_query($conn, "SELECT * FROM delivery_zones ORDER BY min_km ASC");
while ($row = mysqli_fetch_assoc($res2)) {
  $zones[] = $row;
}

function sv(array $settings, string $key): string {
  return htmlspecialchars($settings[$key]['setting_value'] ?? '');
}
function sl(array $settings, string $key): string {
  return htmlspecialchars($settings[$key]['label'] ?? $key);
}
function sd(array $settings, string $key): string {
  return htmlspecialchars($settings[$key]['description'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>FleurChase — Fees & Pricing</title>
<link rel="stylesheet" href="shared.css"/>
<style>
.fees-layout{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start}
@media(max-width:860px){.fees-layout{grid-template-columns:1fr}}

/* Section card */
.fee-card{background:white;border-radius:var(--rl);border:1px solid var(--line);overflow:hidden;margin-bottom:1.5rem}
.fee-card-head{display:flex;align-items:center;gap:12px;padding:1.1rem 1.4rem;border-bottom:1px solid var(--line);background:var(--soft)}
.fee-card-icon{width:36px;height:36px;border-radius:var(--r);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.fee-card-head h3{font-family:var(--font-d);font-size:20px;font-weight:700;color:var(--g1);margin:0}
.fee-card-head p{font-size:11px;color:var(--muted);margin:2px 0 0}
.fee-card-body{padding:1.4rem}

/* Settings rows */
.setting-row{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--line);gap:1rem}
.setting-row:last-child{border-bottom:none}
.setting-info{flex:1}
.setting-label{font-size:13px;font-weight:600;color:var(--ink)}
.setting-desc{font-size:11px;color:var(--muted);margin-top:2px;line-height:1.5}
.setting-ctrl{display:flex;align-items:center;gap:8px;flex-shrink:0}
.fee-input{width:110px;padding:8px 10px;border:1.5px solid var(--line);border-radius:var(--r);font-family:var(--font-b);font-size:13px;font-weight:600;color:var(--ink);background:var(--soft);outline:none;text-align:right;transition:border-color .18s}
.fee-input:focus{border-color:var(--g4);background:white}
.fee-prefix{font-size:13px;font-weight:600;color:var(--muted)}
.fee-suffix{font-size:12px;color:var(--muted)}

/* Zone table */
.zone-table{width:100%;border-collapse:collapse;font-size:13px}
.zone-table th{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);padding:8px 10px;text-align:left;border-bottom:1px solid var(--line);background:var(--soft);white-space:nowrap}
.zone-table td{padding:8px 10px;border-bottom:1px solid var(--line);vertical-align:middle}
.zone-table tr:last-child td{border-bottom:none}
.zone-table tr:hover td{background:var(--soft)}
.zone-input{width:100%;padding:6px 8px;border:1.5px solid var(--line);border-radius:var(--r);font-family:var(--font-b);font-size:12px;color:var(--ink);background:var(--soft);outline:none;transition:border-color .18s}
.zone-input:focus{border-color:var(--g4);background:white}
.zone-input.fee-col{width:90px;text-align:right;font-weight:600}
.zone-input.km-col{width:70px;text-align:right}
.toggle-wrap{display:flex;align-items:center;gap:6px}
.toggle{position:relative;width:36px;height:20px;flex-shrink:0}
.toggle input{opacity:0;width:0;height:0;position:absolute}
.toggle-track{position:absolute;inset:0;background:var(--line);border-radius:20px;cursor:pointer;transition:background .2s}
.toggle input:checked + .toggle-track{background:var(--g4)}
.toggle-thumb{position:absolute;top:2px;left:2px;width:16px;height:16px;background:white;border-radius:50%;transition:left .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.toggle input:checked ~ .toggle-thumb{left:18px}
.add-zone-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--line)}
.del-zone-btn{width:28px;height:28px;border-radius:var(--r);border:1.5px solid var(--line);background:white;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);font-size:13px;transition:all .15s;flex-shrink:0}
.del-zone-btn:hover{background:var(--p9);border-color:var(--p4);color:var(--p2)}

/* Last updated badge */
.updated-badge{font-size:10px;color:var(--muted);font-style:italic}

/* Preview pill */
.fee-preview{display:inline-flex;align-items:center;gap:6px;background:var(--g9);border:1px solid var(--g6);border-radius:var(--r);padding:6px 12px;font-size:12px;color:var(--g2);font-weight:500;margin-top:1rem}
.fee-preview strong{font-weight:700}
</style>
</head>
<body>
<div id="top-nav" class="app-nav"></div>
<aside class="sidebar" id="fc-sidebar"></aside>

<div class="main-area">
<div class="p-page">

  <!-- Header -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <div>
      <div class="page-title">Fees &amp; <em style="color:var(--p3);font-style:italic">Pricing</em></div>
      <p class="page-sub">Manage service fees, delivery zones, and other pricing settings.</p>
    </div>
    <button class="btn btn-green" onclick="saveAll()">
      <i class="ti ti-device-floppy" aria-hidden="true"></i> Save All Changes
    </button>
  </div>

  <?php if ($flash_ok): ?>
    <div class="alert alert-g" style="margin-bottom:1rem">✓ <?php echo htmlspecialchars($flash_ok); ?></div>
  <?php endif; ?>
  <?php if ($flash_err): ?>
    <div class="alert alert-r" style="margin-bottom:1rem">✕ <?php echo htmlspecialchars($flash_err); ?></div>
  <?php endif; ?>

  <div class="fees-layout">

    <!-- ══ LEFT: SERVICE FEE & OTHER SETTINGS ══ -->
    <div>
      <div class="fee-card">
        <div class="fee-card-head">
          <div class="fee-card-icon" style="background:var(--g9)">💳</div>
          <div>
            <h3>Order Fees</h3>
            <p>Applied to every order at checkout</p>
          </div>
        </div>
        <div class="fee-card-body">

          <!-- Service Fee -->
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label"><?php echo sl($settings,'service_fee'); ?></div>
              <div class="setting-desc"><?php echo sd($settings,'service_fee'); ?></div>
              <?php if (!empty($settings['service_fee']['updated_at'])): ?>
                <div class="updated-badge">Last updated: <?php echo date('M j, Y g:i A', strtotime($settings['service_fee']['updated_at'])); ?></div>
              <?php endif; ?>
            </div>
            <div class="setting-ctrl">
              <span class="fee-prefix">₱</span>
              <input class="fee-input" type="number" id="sv-service_fee"
                     value="<?php echo sv($settings,'service_fee'); ?>"
                     min="0" step="0.01" oninput="updatePreview()">
            </div>
          </div>

          <!-- Minimum Order -->
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label"><?php echo sl($settings,'min_order_amount'); ?></div>
              <div class="setting-desc"><?php echo sd($settings,'min_order_amount'); ?></div>
              <?php if (!empty($settings['min_order_amount']['updated_at'])): ?>
                <div class="updated-badge">Last updated: <?php echo date('M j, Y g:i A', strtotime($settings['min_order_amount']['updated_at'])); ?></div>
              <?php endif; ?>
            </div>
            <div class="setting-ctrl">
              <span class="fee-prefix">₱</span>
              <input class="fee-input" type="number" id="sv-min_order_amount"
                     value="<?php echo sv($settings,'min_order_amount'); ?>"
                     min="0" step="0.01">
            </div>
          </div>

          <!-- Advance Notice -->
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label"><?php echo sl($settings,'advance_hours'); ?></div>
              <div class="setting-desc"><?php echo sd($settings,'advance_hours'); ?></div>
              <?php if (!empty($settings['advance_hours']['updated_at'])): ?>
                <div class="updated-badge">Last updated: <?php echo date('M j, Y g:i A', strtotime($settings['advance_hours']['updated_at'])); ?></div>
              <?php endif; ?>
            </div>
            <div class="setting-ctrl">
              <input class="fee-input" type="number" id="sv-advance_hours"
                     value="<?php echo sv($settings,'advance_hours'); ?>"
                     min="0" step="1" style="width:80px">
              <span class="fee-suffix">hrs</span>
            </div>
          </div>

          <!-- Live preview -->
          <div class="fee-preview" id="fee-preview">
            A ₱450 bouquet → subtotal <strong id="prev-sub">₱450.00</strong> + service fee <strong id="prev-sf">₱50.00</strong> = <strong id="prev-total">₱500.00</strong>
          </div>

        </div>
      </div>
    </div>

    <!-- ══ RIGHT: DELIVERY ZONES ══ -->
    <div>
      <div class="fee-card">
        <div class="fee-card-head">
          <div class="fee-card-icon" style="background:var(--p9)">🛵</div>
          <div>
            <h3>Delivery Zones</h3>
            <p>Set fees per area or distance range</p>
          </div>
        </div>
        <div class="fee-card-body" style="padding:0">

          <div style="overflow-x:auto">
            <table class="zone-table">
              <thead>
                <tr>
                  <th>Zone Name</th>
                  <th>Fee (₱)</th>
                  <th>Min km</th>
                  <th>Max km</th>
                  <th>Active</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="zone-tbody">
                <?php foreach ($zones as $z): ?>
                <tr data-zone-id="<?php echo $z['zone_id']; ?>">
                  <td>
                    <input class="zone-input" type="text"
                           name="zone_name" value="<?php echo htmlspecialchars($z['zone_name']); ?>"
                           placeholder="Zone name">
                  </td>
                  <td>
                    <input class="zone-input fee-col" type="number"
                           name="zone_fee" value="<?php echo $z['fee']; ?>"
                           min="0" step="0.01">
                  </td>
                  <td>
                    <input class="zone-input km-col" type="number"
                           name="zone_min_km" value="<?php echo $z['min_km']; ?>"
                           min="0" step="0.01">
                  </td>
                  <td>
                    <input class="zone-input km-col" type="number"
                           name="zone_max_km" value="<?php echo $z['max_km']; ?>"
                           min="0" step="0.01">
                  </td>
                  <td>
                    <label class="toggle">
                      <input type="checkbox" <?php echo $z['is_active'] ? 'checked' : ''; ?>
                             name="zone_active">
                      <div class="toggle-track"></div>
                      <div class="toggle-thumb"></div>
                    </label>
                  </td>
                  <td>
                    <button class="del-zone-btn" type="button"
                            onclick="deleteZone(this, <?php echo $z['zone_id']; ?>)"
                            title="Delete zone">
                      <i class="ti ti-trash" aria-hidden="true"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Add new zone -->
          <div style="padding:1rem 1.4rem;border-top:1px solid var(--line)">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:8px">Add New Zone</div>
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:6px;align-items:end">
              <div class="ff" style="margin:0">
                <label style="font-size:10px">Zone Name</label>
                <input class="zone-input" type="text" id="new-zone-name" placeholder="e.g. Within Naga City">
              </div>
              <div class="ff" style="margin:0">
                <label style="font-size:10px">Fee (₱)</label>
                <input class="zone-input fee-col" type="number" id="new-zone-fee" min="0" step="0.01" placeholder="0">
              </div>
              <div class="ff" style="margin:0">
                <label style="font-size:10px">Min km</label>
                <input class="zone-input km-col" type="number" id="new-zone-min" min="0" step="0.01" placeholder="0">
              </div>
              <div class="ff" style="margin:0">
                <label style="font-size:10px">Max km</label>
                <input class="zone-input km-col" type="number" id="new-zone-max" min="0" step="0.01" placeholder="0">
              </div>
              <button class="btn btn-green btn-sm" type="button" onclick="addZoneRow()" style="margin-bottom:0;white-space:nowrap">
                <i class="ti ti-plus" aria-hidden="true"></i> Add
              </button>
            </div>
          </div>

        </div>
      </div>

      <!-- Tip card -->
      <div class="card-sm" style="background:var(--g9);border-color:var(--g6)">
        <div style="font-size:12px;color:var(--g2);line-height:1.7">
          <strong>💡 How delivery zones work</strong><br>
          At checkout, the customer's selected area is matched to a zone. If no zone matches, the highest-fee zone is used as fallback. Inactive zones are hidden from customers.
        </div>
      </div>
    </div>

  </div><!-- /fees-layout -->
</div>
</div>

<div id="fc-toast" class="toast"></div>
<script src="data.js"></script>
<script src="nav.js"></script>

<script>
requireAuth('admin');
buildTopNav('fees-admin');
buildAdminSidebar('fees-admin.php');

/* ── Live preview ── */
function updatePreview() {
  const sf    = parseFloat(document.getElementById('sv-service_fee').value) || 0;
  const sub   = 450;
  const total = sub + sf;
  document.getElementById('prev-sf').textContent    = '₱' + sf.toFixed(2);
  document.getElementById('prev-total').textContent = '₱' + total.toFixed(2);
}
updatePreview();

/* ── Add a new zone row (client-side, saved on Save All) ── */
let newZoneIdx = 0;
function addZoneRow() {
  const name = document.getElementById('new-zone-name').value.trim();
  const fee  = document.getElementById('new-zone-fee').value  || '0';
  const minK = document.getElementById('new-zone-min').value  || '0';
  const maxK = document.getElementById('new-zone-max').value  || '0';
  if (!name) { toast('Enter a zone name.', 'warn'); return; }

  const tr = document.createElement('tr');
  tr.dataset.zoneId = 'new-' + (newZoneIdx++);
  tr.innerHTML = `
    <td><input class="zone-input" type="text" name="zone_name" value="${name}"></td>
    <td><input class="zone-input fee-col" type="number" name="zone_fee" value="${fee}" min="0" step="0.01"></td>
    <td><input class="zone-input km-col" type="number" name="zone_min_km" value="${minK}" min="0" step="0.01"></td>
    <td><input class="zone-input km-col" type="number" name="zone_max_km" value="${maxK}" min="0" step="0.01"></td>
    <td>
      <label class="toggle">
        <input type="checkbox" name="zone_active" checked>
        <div class="toggle-track"></div>
        <div class="toggle-thumb"></div>
      </label>
    </td>
    <td>
      <button class="del-zone-btn" type="button" onclick="deleteZone(this, null)" title="Delete zone">
        <i class="ti ti-trash" aria-hidden="true"></i>
      </button>
    </td>`;
  document.getElementById('zone-tbody').appendChild(tr);

  /* clear inputs */
  ['new-zone-name','new-zone-fee','new-zone-min','new-zone-max'].forEach(id => {
    document.getElementById(id).value = '';
  });
  toast('Zone added — click Save All to persist.');
}

/* ── Delete a zone row ── */
const pendingDeletes = [];
function deleteZone(btn, zoneId) {
  const row = btn.closest('tr');
  if (zoneId) pendingDeletes.push(zoneId); // existing DB row
  row.style.opacity = '.4';
  row.style.pointerEvents = 'none';
  row.dataset.deleted = '1';
  toast('Zone removed — click Save All to confirm.');
}

/* ── Collect all data and POST to save_fees.php ── */
function saveAll() {
  /* 1. Settings */
  const settings = {};
  document.querySelectorAll('[id^="sv-"]').forEach(el => {
    const key = el.id.replace('sv-', '');
    settings[key] = el.value;
  });

  /* 2. Zones */
  const zones = [];
  document.querySelectorAll('#zone-tbody tr:not([data-deleted])').forEach(row => {
    const zoneId  = row.dataset.zoneId;
    const name    = row.querySelector('[name="zone_name"]').value.trim();
    const fee     = row.querySelector('[name="zone_fee"]').value;
    const minKm   = row.querySelector('[name="zone_min_km"]').value;
    const maxKm   = row.querySelector('[name="zone_max_km"]').value;
    const active  = row.querySelector('[name="zone_active"]').checked ? 1 : 0;
    if (!name) return;
    zones.push({ zone_id: zoneId, zone_name: name, fee, min_km: minKm, max_km: maxKm, is_active: active });
  });

  fetch('db/save_fees.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ settings, zones, delete_zone_ids: pendingDeletes })
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      toast('Fees & pricing saved!');
      /* clear pending deletes after confirmed save */
      pendingDeletes.length = 0;
      /* reload after short delay so updated_at timestamps refresh */
      setTimeout(() => location.reload(), 1200);
    } else {
      toast(d.error || 'Save failed.', 'err');
    }
  })
  .catch(() => toast('Could not reach server.', 'err'));
}
</script>
</body>
</html>
