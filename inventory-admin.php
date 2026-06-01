<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>FleurChase — Inventory</title>
<link rel="stylesheet" href="shared.css"/>
<style>
  /* --- Freshness Progress Bars --- */
  .fresh-bar {
    height: 6px;
    border-radius: 3px;
    background: var(--line);
    overflow: hidden;
    width: 80px;
    display: inline-block;
    vertical-align: middle;
    margin-right: 6px;
  }

 .fresh-fill{
  height:100%;
  display:block;
  border-radius:3px;
  transition:width .4s;
  }


  .fresh-ok  { background: var(--g4); }
  .fresh-mid { background: #FFA000; }
  .fresh-low { background: var(--p4); }

  /* --- Inventory Alerts --- */
  .alert {
    border-radius: var(--r);
    padding: 10px 12px;
    font-size: 12px;
    line-height: 1.6;
    margin-bottom: 6px;
    border-left: 3px solid;
  }

  .alert-r { background: var(--p9); border-color: var(--p3); } /* Critical */
  .alert-a { background: #FFF8E1; border-color: #FFA000; }     /* Warning */
  .alert-g { background: var(--g9); border-color: var(--g4); } /* Good */

  /* --- Dashboard Metrics (Top Cards) --- */
  .metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 1.5rem;
  }

  .metric-card {
    background: white;
    border-radius: var(--rl);
    border: 1px solid var(--line);
    padding: 1.2rem;
  }

  .metric-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--muted);
    margin-bottom: .5rem;
  }

  .metric-val {
    font-size: 26px;
    font-weight: 700;
    color: var(--ink);
    font-family: var(--font-d);
  }

  .metric-sub {
    font-size: 11px;
    margin-top: 4px;
  }

  .metric-up { color: var(--g3); }
  .metric-dn { color: var(--p3); }
  .metric-n  { color: var(--muted); }

  /* --- Bottom Layout & Restock List --- */
  .inv-two {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-top: 1.5rem;
  }

  .restock-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--line);
    font-size: 13px;
  }

  .restock-item:last-child {
    border-bottom: none;
  }

  /* --- Inventory Images --- */
.inv-img-thumb {
    width: 36px;
    height: 36px;
    background: var(--g9); 
    border-radius: var(--r);
    border: 1px solid var(--line);
    object-fit: contain; 
    padding: 2px; 
  }

.inv-img-large {
  width: 60px; 
    height: 60px;
    background: var(--g9);
    border-radius: var(--r);
    border: 1px solid var(--line);
    object-fit: contain;
    padding: 4px;
}

/* --- Updated Drag & Drop UI --- */
.dropzone {
  border: 2px dashed var(--line);
  border-radius: var(--rl);
  padding: 1.5rem;
  text-align: center;
  background: var(--soft);
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
  min-height: 160px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

/* Green border when a photo is uploaded or dragging over */
.dropzone.has-photo, .dropzone.dragover {
  border-color: var(--g4);
  background: var(--g9);
}

.dz-preview-img {
  width: 110px;
  height: 110px;
  object-fit: contain;
  border-radius: 12px;
  background: white;
  border: 1px solid var(--line);
}

.dz-status-text {
  font-size: 13px;
  font-weight: 600;
  color: var(--g2);
}

.dz-remove-link {
  display: block;
  margin-top: 8px;
  font-size: 12px;
  color: var(--p3);
  text-decoration: underline;
  cursor: pointer;
  border: none;
  background: none;
}

.dropzone input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}

.inventory-search {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.inventory-search input {
  flex: 1;
  min-width: 240px;
  border: 1.5px solid var(--line);
  background: white;
  border-radius: var(--r);
  padding: 10px 12px;
  font-family: var(--font-b);
  font-size: 13px;
  outline: none;
}

.inventory-search input:focus {
  border-color: var(--g3);
}


</style>
</head>
<body>
<div id="top-nav" class="app-nav"></div>
<aside class="sidebar" id="fc-sidebar"></aside>
<div class="main-area">
<div class="p-page">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <div><div class="page-title">Products</div><div class="page-sub" style="margin-bottom:0">Track flower stock, freshness, and base price per stem</div></div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-ghost btn-sm" onclick="location.reload()">↻ Refresh</button>
      <button class="btn btn-green btn-sm" onclick="openAddModal()">+ Add Product</button>
    </div>
  </div>

  <!-- METRICS -->
  <div class="metrics-grid" id="inv-metrics"></div>

  <div class="inventory-search">
      <input id="inventory-search-input" type="search" placeholder="Search product, type, stock, price, date, freshness, status..." oninput="filterInventory()">
      <button class="btn btn-ghost btn-sm" onclick="clearInventorySearch()">Clear</button>
    </div>
  <!-- MAIN TABLE -->
  <div class="table-wrap">
    <div class="table-head">
      <h3>Product Inventory</h3>
    </div>
    <div style="overflow-x:auto">
      <table class="data-table" id="inv-table">
        <thead>
          <tr>
            <th>Image</th>
            <th>Product</th>
            <th>Stock</th>
            <th>Price</th>
            <th>Arrived</th>
            <th>Shelf Life</th>
            <th>Best Before</th>
            <th>Freshness</th>
            <th>%</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
  <?php
    include 'db/connection_db.php';

    $sql = "SELECT * FROM product ORDER BY date_arrived DESC";
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
      $date_arrived = new DateTime($row['date_arrived']);
      $best_before  = new DateTime($row['best_before_date']);
      $today        = new DateTime('today');

      $total_shelf_life = max(1, $date_arrived->diff($best_before)->days);

      $remaining_days = (int)$today->diff($best_before)->format('%r%a');

      if ($remaining_days <= 0) {
        $remaining_days = 0;
        $fresh_percent = 0;
      } else {
        $fresh_percent = round(($remaining_days / $total_shelf_life) * 100);
      }

      $fresh_percent = max(0, min(100, $fresh_percent));

      if ($fresh_percent >= 60) {
        $fresh_class = 'fresh-ok';
      } elseif ($fresh_percent >= 30) {
        $fresh_class = 'fresh-mid';
      } else {
        $fresh_class = 'fresh-low';
      }

      $shelf_life_text = $total_shelf_life . ' ' . ($total_shelf_life == 1 ? 'day' : 'days');
    ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <img 
            src="images/<?php echo htmlspecialchars($row['product_image']); ?>" 
            alt="<?php echo htmlspecialchars($row['product_name']); ?>"
            style="width:42px;height:42px;object-fit:cover;border-radius:8px;"
          >
          <span><?php echo htmlspecialchars($row['product_name']); ?></span>
        </div>
      </td>

      <td><?php echo htmlspecialchars($row['product_type']); ?></td>
      <td><?php echo htmlspecialchars($row['stock']); ?></td>
      <td>₱<?php echo number_format($row['price'], 2); ?></td>
      <td><?php echo htmlspecialchars($row['date_arrived']); ?></td>
      <td><?php echo $shelf_life_text; ?></td>
      <td><?php echo htmlspecialchars($row['best_before_date']); ?></td>

      <td>
        <span class="fresh-bar">
          <span 
            class="fresh-fill <?php echo $fresh_class; ?>" 
            style="width:<?php echo $fresh_percent; ?>%">
          </span>
        </span>
      </td>

      <td><?php echo $fresh_percent; ?>%</td>

      <td>
        <span class="tag <?php echo $row['status'] === 'Active' ? 'tag-g' : 'tag-p'; ?>">
          <?php echo htmlspecialchars($row['status']); ?>
        </span>
      </td>

      <td>
        <button class="btn btn-ghost btn-sm" onclick="openEditModal('<?php echo $row['product_id']; ?>')">
          Edit
        </button>
        <button 
          class="btn btn-danger btn-sm" 
          onclick="openDeleteModal('<?php echo $row['product_id']; ?>', '<?php echo htmlspecialchars($row['product_name'], ENT_QUOTES); ?>')">
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
</div>

<!-- ADD STOCK MODAL -->
<div class="modal-overlay" id="add-modal">
  <div class="modal-box" style="max-width:520px">
    <div class="modal-head"><h3 id="add-modal-title">Add Stock Entry</h3><button class="modal-close" onclick="closeModal('add-modal')">✕</button></div>
    <div id="add-modal-body"></div>
  </div>
</div>

<div class="modal-overlay" id="delete-modal">
  <div class="modal-box" style="max-width:360px">
    <div class="modal-head">
      <h3>Confirm Delete</h3>
      <button class="modal-close" onclick="closeModal('delete-modal')">✕</button>
    </div>

    <p style="font-size:13px;color:var(--muted);margin-bottom:1.5rem">
      Delete <strong id="delete-product-name"></strong>? This cannot be undone.
    </p>

    <div style="display:flex;gap:8px">
      <button class="btn btn-danger" style="flex:1;justify-content:center" onclick="confirmDeleteProduct()">Delete</button>
      <button class="btn btn-ghost" style="flex:1;justify-content:center" onclick="closeModal('delete-modal')">Cancel</button>
    </div>
  </div>
</div>


<div id="fc-toast" class="toast"></div>
<script src="data.js"></script><script src="nav.js"></script>
<script>
requireAuth('admin');
buildTopNav('inventory-admin');
buildAdminSidebar('inventory-admin.html');

let editingInvId = null;
let deletingProductId = null;
let inventorySearchQuery = '';

function filterInventory() {
  const input = document.getElementById('inventory-search-input');
  const tbody = document.querySelector('#inv-table tbody');
  if (!input || !tbody) return;

  inventorySearchQuery = input.value.trim().toLowerCase();

  const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.id !== 'inventory-empty-row');
  let visibleCount = 0;

  rows.forEach(row => {
    const matches = !inventorySearchQuery || row.textContent.toLowerCase().includes(inventorySearchQuery);
    row.style.display = matches ? '' : 'none';
    if (matches) visibleCount++;
  });

  let emptyRow = document.getElementById('inventory-empty-row');
  if (!emptyRow) {
    emptyRow = document.createElement('tr');
    emptyRow.id = 'inventory-empty-row';
    emptyRow.innerHTML = '<td colspan="11" style="text-align:center;padding:2rem;color:var(--muted)">No inventory items match your search.</td>';
    tbody.appendChild(emptyRow);
  }

  emptyRow.style.display = visibleCount ? 'none' : '';
}

function clearInventorySearch() {
  const input = document.getElementById('inventory-search-input');
  if (input) input.value = '';
  inventorySearchQuery = '';
  filterInventory();
}

//DELETE PRODUCT
function openDeleteModal(id, name) {
  deletingProductId = id;
  document.getElementById('delete-product-name').textContent = name;
  openModal('delete-modal');
}

function confirmDeleteProduct() {
  if (!deletingProductId) return;
  window.location.href = 'db/delete_product.php?id=' + encodeURIComponent(deletingProductId);
}

//ADD PRODUCT
function openAddModal() {
  document.getElementById('add-modal-title').textContent = 'Add Product';
  document.getElementById('add-modal-body').innerHTML = getAddModalBody();
  openModal('add-modal');
}

function getAddModalBody() {
  return `
    <form id="add-product-form" action="db/add_product.php" method="POST" enctype="multipart/form-data">
      <div class="ff" style="margin-bottom:1rem">
        <label>PRODUCT NAME *</label>
        <input name="product_name" required placeholder="e.g. Red Roses">
      </div>

      <div class="ff" style="margin-bottom:1rem">
        <label>PRODUCT TYPE *</label>
        <select name="product_type" required>
          <option value="flower">Flower</option>
          <option value="filler">Filler</option>
          <option value="addon">Add on</option>
        </select>
      </div>

      <div class="ff" style="margin-bottom:1rem">
        <label>PRODUCT IMAGE *</label>
        <input type="file" name="product_image" accept="image/*" required>
      </div>

      <div class="fg2">
        <div class="ff">
          <label>STOCK *</label>
          <input name="stock" placeholder="e.g. 50" type="number" min="0" required>
        </div>

        <div class="ff">
          <label>PRICE *</label>
          <input name="price" placeholder="e.g. 25.99" type="number" min="1" step="0.01" required>
        </div>
      </div>

      <div class="fg2">
        <div class="ff">
          <label>DATE ARRIVED *</label>
          <input name="date_arrived" type="date" required>
        </div>

        <div class="ff">
          <label>BEST BEFORE *</label>
          <input name="best_before_date" type="date" required>
        </div>
      </div>

      <div class="ff" style="margin-top:1rem">
        <label>STATUS</label>
        <select name="status">
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
          <option value="Out of Stock">Out of Stock</option>
        </select>
      </div>

      <button type="submit" class="btn btn-green" style="width:100%;justify-content:center;margin-top:1.5rem;padding:14px">
        Add Product
      </button>
    </form>
  `;
}

//EDIT MODAL
function openEditModal(id) {
  fetch('db/get_product.php?id=' + encodeURIComponent(id))
    .then(response => response.json())
    .then(product => {
      if (product.error) {
        toast(product.error, 'err');
        return;
      }

      document.getElementById('add-modal-title').textContent = 'Edit: ' + product.product_name;
      document.getElementById('add-modal-body').innerHTML = getEditModalBody(product);
      openModal('add-modal');
    })
    .catch(() => {
      toast('Failed to load product.', 'err');
    });
}

function getEditModalBody(p) {
  return `
    <form id="edit-product-form" action="db/update_product.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="product_id" value="${p.product_id}">
      <input type="hidden" name="old_image" value="${p.product_image || ''}">

      <div class="ff" style="margin-bottom:1rem">
        <label>PRODUCT NAME *</label>
        <input name="product_name" value="${p.product_name || ''}" required>
      </div>

      <div class="ff" style="margin-bottom:1rem">
        <label>PRODUCT TYPE *</label>
        <select name="product_type" required>
          <option value="flower" ${p.product_type === 'flower' ? 'selected' : ''}>Flower</option>
          <option value="filler" ${p.product_type === 'filler' ? 'selected' : ''}>Filler</option>
          <option value="addon" ${p.product_type === 'addon' ? 'selected' : ''}>Add on</option>
        </select>
      </div>

      <div class="ff" style="margin-bottom:1rem">
        <label>PRODUCT IMAGE</label>
        ${p.product_image ? `<img src="images/${p.product_image}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;margin-bottom:8px">` : ''}
        <input type="file" name="product_image" accept="image/*">
      </div>

      <div class="fg2">
        <div class="ff">
          <label>STOCK *</label>
          <input name="stock" type="number" value="${p.stock || 0}" min="0" required>
        </div>

        <div class="ff">
          <label>PRICE *</label>
          <input name="price" type="number" value="${p.price || ''}" min="1" step="0.01" required>
        </div>
      </div>

      <div class="fg2">
        <div class="ff">
          <label>DATE ARRIVED *</label>
          <input name="date_arrived" type="date" value="${p.date_arrived || ''}" required>
        </div>

        <div class="ff">
          <label>BEST BEFORE *</label>
          <input name="best_before_date" type="date" value="${p.best_before_date || ''}" required>
        </div>
      </div>

      <div class="ff" style="margin-top:1rem">
        <label>STATUS</label>
        <select name="status">
          <option value="Active" ${p.status === 'Active' ? 'selected' : ''}>Active</option>
          <option value="Inactive" ${p.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
          <option value="Out of Stock" ${p.status === 'Out of Stock' ? 'selected' : ''}>Out of Stock</option>
        </select>
      </div>

      <button type="submit" class="btn btn-green" style="width:100%;justify-content:center;margin-top:1.5rem;padding:14px">
        Save Changes
      </button>
    </form>
  `;
}




</script>
</body>
</html>
