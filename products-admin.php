<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>FleurChase — Products & Add-ons</title>
<link rel="stylesheet" href="shared.css"/>
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
</style>
</head>
<body>
<div id="top-nav" class="app-nav"></div>
<aside class="sidebar" id="fc-sidebar"></aside>
<div class="main-area">
<div class="p-page">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;flex-wrap:wrap;gap:1rem">
    <div><div class="page-title">Products &amp; Add-ons</div></div>
    <button class="btn btn-green btn-sm" id="add-btn" onclick="openAddModal()">+ Add Product</button>
  </div>



  <!-- PRODUCTS TAB -->
  <div id="tab-products">
    <div class="filter-bar" id="prod-filter-bar">
      <button class="filt-btn active" onclick="setProdFilter('all',this)">All</button>
      <button class="filt-btn" onclick="setProdFilter('bouquet',this)">Bouquets</button>
      <button class="filt-btn" onclick="setProdFilter('flower',this)">Stems</button>
      <button class="filt-btn" onclick="setProdFilter('ready-made',this)">Ready-Made</button>
      <button class="filt-btn" onclick="setProdFilter('seasonal',this)">Seasonal</button>
      <button class="filt-btn" onclick="setProdFilter('gift-set',this)">Gift Sets</button>
      <button class="filt-btn" onclick="setProdFilter('promo',this)">Promos</button>
      <button class="filt-btn" onclick="setProdFilter('sale',this)">On Sale</button>
    </div>
 
    <?php include 'db/connection_db.php'; ?>

<div class="table-wrap">
  <div class="table-head">
    <div class="table-head">
      <?php
      $count_sql = "SELECT COUNT(*) AS total FROM bouquet";
      $count_result = mysqli_query($conn, $count_sql);
      $count_row = mysqli_fetch_assoc($count_result);
      $total_products = $count_row['total'];
      ?>

      <h3>Products (<span id="prod-count"><?php echo $total_products; ?></span>)</h3>

      <input id="prod-search" placeholder="Search..." style="border:1.5px solid var(--line);border-radius:var(--r);padding:7px 12px;font-family:var(--font-b);font-size:13px;outline:none;background:var(--soft);width:180px" />
    </div>
  </div>

  <div style="overflow-x:auto">
    <table class="data-table"> 
  <thead>
    <tr>
      <th>Image</th>
      <th>Product</th>
      <th>Category</th>
      <th>Price</th>
      <th>Stock</th>
      <th>Rating</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>

  <tbody id="prod-tbody">
    <?php
      $sql = "
        SELECT 
          b.*,
          COALESCE(br.avg_rating, 0) AS avg_rating,
          COALESCE(br.total_ratings, 0) AS total_ratings
        FROM bouquet b
        LEFT JOIN bouquet_ratings_view br
          ON b.bouquet_id = br.bouquet_id
      ";

      $result = mysqli_query($conn, $sql);

      while ($row = mysqli_fetch_assoc($result)) {
    ?>
      <tr>
        <td>
          <img 
            src="images/<?php echo htmlspecialchars($row['image']); ?>" 
            alt="<?php echo htmlspecialchars($row['name']); ?>"
            style="width:60px;height:60px;object-fit:cover;border-radius:8px;"
          >
        </td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['category']); ?></td>
        <td>₱<?php echo number_format($row['price'], 2); ?></td>
        <td><?php echo htmlspecialchars($row['stock']); ?></td>
        <td>
          <?php echo number_format($row['avg_rating'], 1); ?>
          (<?php echo $row['total_ratings']; ?>)
        </td>
        <td><?php echo htmlspecialchars($row['status']); ?></td>
        <td>
          <button class="btn btn-ghost btn-sm" onclick="openEditProd('<?php echo $row['bouquet_id']; ?>')">Edit</button>
          <button 
            class="btn btn-danger btn-sm" 
            onclick="openDeleteProd('<?php echo $row['bouquet_id']; ?>', '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>')">
            Delete
          </button>

        </td>
      </tr>
    <?php } ?>
  </tbody>
</table>

  </div>
</div>

  </div>

  <!-- ADD-ONS TAB -->
  <div id="tab-addons" style="display:none">
    <div class="filter-bar" id="addon-filter-bar">
      <button class="cf-btn active" onclick="setAddonFilter('all',this)">All</button>
      <button class="cf-btn" onclick="setAddonFilter('chocolates',this)">Chocolates</button>
      <button class="cf-btn" onclick="setAddonFilter('toys',this)">Teddies</button>
      <button class="cf-btn" onclick="setAddonFilter('cards',this)">Cards</button>
      <button class="cf-btn" onclick="setAddonFilter('balloons',this)">Balloons</button>
      <button class="cf-btn" onclick="setAddonFilter('extras',this)">Extras</button>
    </div>
    <div class="table-wrap">
      <div class="table-head">
        <h3>Add-ons (<span id="addon-count">0</span>)</h3>
        <input id="addon-search" placeholder="Search..." style="border:1.5px solid var(--line);border-radius:var(--r);padding:7px 12px;font-family:var(--font-b);font-size:13px;outline:none;background:var(--soft);width:180px" oninput="renderAddons()"/>
      </div>
      <table class="data-table">
        <thead><tr><th>Item</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="addon-tbody"></tbody>
      </table>
    </div>
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

<div id="fc-toast" class="toast"></div>

<script src="data.js"></script><script src="nav.js"></script>
<script src="shared.js"></script>
<script>
requireAuth('admin');
buildTopNav('products-admin');
buildAdminSidebar('products-admin.html');

let activeTab = 'products';
let editingProdId = null;
let pendingImg = null;
let deletingId = null;
let deleteType = null;

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
          <label>Variation</label>
          <input id="p-variation" name="variation" value="${pv.variation || ''}">
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

</script>
</body>
</html>
