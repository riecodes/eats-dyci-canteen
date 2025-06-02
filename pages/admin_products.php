<?php
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}
require_once __DIR__ . '/../includes/db.php';

// Fetch all sellers
$sellers = $pdo->query("SELECT id, name FROM users WHERE role = 'seller' ORDER BY name ASC")->fetchAll();
// Fetch all stalls
$stalls = $pdo->query("SELECT id, name, seller_id FROM stalls ORDER BY name ASC")->fetchAll();
// Fetch all categories (global)
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$add_success = $add_error = $edit_success = $edit_error = $delete_success = $delete_error = '';

// Add product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = $_POST['category_id'] ?? null;
    $stall_id = $_POST['stall_id'] ?? null;
    $seller_id = $_POST['seller_id'] ?? null;
    $stock = intval($_POST['stock'] ?? 0);
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $target = '../assets/imgs/products_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_url = $target;
        }
    }
    if (!$name || !$price || !$stall_id || !$seller_id || !$category_id) {
        $add_error = 'Name, price, seller, stall, and category are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image, category_id, stall_id, seller_id, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $description, $price, $image_url, $category_id, $stall_id, $seller_id, $stock])) {
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $add_error = 'Failed to add product.';
        }
    }
}
// Delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_id'])) {
    $del_id = intval($_POST['delete_product_id']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$del_id])) {
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $delete_error = 'Failed to delete product.';
    }
}
// Edit product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product_id'])) {
    $edit_id = intval($_POST['edit_product_id']);
    $edit_name = trim($_POST['edit_name'] ?? '');
    $edit_description = trim($_POST['edit_description'] ?? '');
    $edit_price = floatval($_POST['edit_price'] ?? 0);
    $edit_category_id = $_POST['edit_category_id'] ?? null;
    $edit_stall_id = $_POST['edit_stall_id'] ?? null;
    $edit_seller_id = $_POST['edit_seller_id'] ?? null;
    $edit_stock = intval($_POST['edit_stock'] ?? 0);
    $edit_image_url = null;
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION);
        $target = '../assets/imgs/products_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $target)) {
            $edit_image_url = $target;
        }
    }
    $sql = "UPDATE products SET name=?, description=?, price=?, category_id=?, stall_id=?, seller_id=?, stock=?";
    $params = [$edit_name, $edit_description, $edit_price, $edit_category_id, $edit_stall_id, $edit_seller_id, $edit_stock];
    if ($edit_image_url) {
        $sql .= ", image=?";
        $params[] = $edit_image_url;
    }
    $sql .= " WHERE id=?";
    $params[] = $edit_id;
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $edit_error = 'Failed to update product.';
    }
}
// Fetch all products with joins
$prod_stmt = $pdo->query("SELECT p.*, c.name AS category_name, u.name AS seller_name, s.name AS stall_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN users u ON p.seller_id = u.id LEFT JOIN stalls s ON p.stall_id = s.id ORDER BY p.id DESC");
$products = $prod_stmt->fetchAll();
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
    <div class="dashboard-section-title mb-3">Product Management</div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="fw-bold">All Products</div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">Add Product</button>
    </div>
    <?php if ($add_success): ?><div class="alert alert-success mb-2"><?= $add_success ?></div><?php endif; ?>
    <?php if ($add_error): ?><div class="alert alert-danger mb-2"><?= $add_error ?></div><?php endif; ?>
    <?php if ($edit_success): ?><div class="alert alert-success mb-2"><?= $edit_success ?></div><?php endif; ?>
    <?php if ($edit_error): ?><div class="alert alert-danger mb-2"><?= $edit_error ?></div><?php endif; ?>
    <?php if ($delete_success): ?><div class="alert alert-success mb-2"><?= $delete_success ?></div><?php endif; ?>
    <?php if ($delete_error): ?><div class="alert alert-danger mb-2"><?= $delete_error ?></div><?php endif; ?>
    <div class="dashboard-table mb-4">
    <table class="table mb-0">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Price</th>
                <th>Category</th>
                <th>Seller</th>
                <th>Stall</th>
                <th>Stock</th>
                <th>Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $prod): ?>
            <tr>
                <td><?= $prod['id'] ?></td>
                <td><?= htmlspecialchars($prod['name']) ?></td>
                <td><?= htmlspecialchars($prod['description']) ?></td>
                <td>₱<?= number_format($prod['price'],2) ?></td>
                <td><?= htmlspecialchars($prod['category_name']) ?></td>
                <td><?= htmlspecialchars($prod['seller_name']) ?></td>
                <td><?= htmlspecialchars($prod['stall_name']) ?></td>
                <td><?= $prod['stock'] ?></td>
                <td><?php if ($prod['image']): ?><img src="<?= $prod['image'] ?>" alt="" style="max-width:60px;max-height:60px;object-fit:cover;"/><?php endif; ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editProductModal<?= $prod['id'] ?>">Edit</button>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="delete_product_id" value="<?= $prod['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product?')">Delete</button>
                    </form>
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editProductModal<?= $prod['id'] ?>" tabindex="-1" aria-labelledby="editProductModalLabel<?= $prod['id'] ?>" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="editProductModalLabel<?= $prod['id'] ?>">Edit Product</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <form method="post" enctype="multipart/form-data" autocomplete="off">
                                <input type="hidden" name="edit_product_id" value="<?= $prod['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label" for="edit_name_<?= $prod['id'] ?>">Name</label>
                                    <input type="text" class="form-control" id="edit_name_<?= $prod['id'] ?>" name="edit_name" value="<?= htmlspecialchars($prod['name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="edit_description_<?= $prod['id'] ?>">Description</label>
                                    <textarea class="form-control" id="edit_description_<?= $prod['id'] ?>" name="edit_description" rows="2"><?= htmlspecialchars($prod['description']) ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="edit_price_<?= $prod['id'] ?>">Price</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_price_<?= $prod['id'] ?>" name="edit_price" value="<?= $prod['price'] ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="edit_category_id_<?= $prod['id'] ?>">Category</label>
                                    <select class="form-select" id="edit_category_id_<?= $prod['id'] ?>" name="edit_category_id">
                                        <option value="">None</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?php if ($prod['category_id'] == $cat['id']) echo 'selected'; ?>><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="edit_seller_id_<?= $prod['id'] ?>">Seller</label>
                                    <select class="form-select" id="edit_seller_id_<?= $prod['id'] ?>" name="edit_seller_id" required onchange="updateStallDropdown(this.value, 'edit_stall_id_<?= $prod['id'] ?>', <?= $prod['stall_id'] ?>)">
                                        <?php foreach ($sellers as $seller): ?>
                                            <option value="<?= $seller['id'] ?>" <?php if ($prod['seller_id'] == $seller['id']) echo 'selected'; ?>><?= htmlspecialchars($seller['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="edit_stall_id_<?= $prod['id'] ?>">Stall</label>
                                    <select class="form-select" id="edit_stall_id_<?= $prod['id'] ?>" name="edit_stall_id" required></select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="edit_stock_<?= $prod['id'] ?>">Stock</label>
                                    <input type="number" class="form-control" id="edit_stock_<?= $prod['id'] ?>" name="edit_stock" min="0" value="<?= $prod['stock'] ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="edit_image_<?= $prod['id'] ?>">Image</label>
                                    <input type="file" class="form-control" id="edit_image_<?= $prod['id'] ?>" name="edit_image">
                                    <?php if ($prod['image']): ?><img src="<?= $prod['image'] ?>" alt="" style="max-width:60px;max-height:60px;object-fit:cover;"/><?php endif; ?>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                            </form>
                          </div>
                        </div>
                      </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <!-- Add Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="post" enctype="multipart/form-data" autocomplete="off" id="addProductForm">
                <input type="hidden" name="add_product" value="1">
                <div class="mb-3">
                    <label class="form-label" for="add_product_name">Name</label>
                    <input type="text" class="form-control" id="add_product_name" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="add_product_description">Description</label>
                    <textarea class="form-control" id="add_product_description" name="description" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="add_product_price">Price</label>
                    <input type="number" step="0.01" class="form-control" id="add_product_price" name="price" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="add_product_category_id">Category</label>
                    <select class="form-select" id="add_product_category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="add_product_seller_id">Seller</label>
                    <select class="form-select" id="add_product_seller_id" name="seller_id" required onchange="updateStallDropdown(this.value, 'add_product_stall_id')">
                        <option value="">Select Seller</option>
                        <?php foreach ($sellers as $seller): ?>
                            <option value="<?= $seller['id'] ?>"><?= htmlspecialchars($seller['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="add_product_stall_id">Stall</label>
                    <select class="form-select" id="add_product_stall_id" name="stall_id" required></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Stock</label>
                    <input type="number" class="form-control" name="stock" min="0" value="0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="add_product_image">Image</label>
                    <input type="file" class="form-control" id="add_product_image" name="image">
                </div>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </form>
            <script>
            // On add modal open, reset and update stalls
            document.getElementById('addProductModal').addEventListener('show.bs.modal', function () {
                document.getElementById('add_product_seller_id').selectedIndex = 0;
                document.getElementById('add_product_stall_id').innerHTML = '';
            });
            document.getElementById('add_product_seller_id').addEventListener('change', function() {
                updateStallDropdown(this.value, 'add_product_stall_id');
            });
            </script>
          </div>
        </div>
      </div>
    </div>
</div>
<script>
// Build a mapping of seller_id to their stalls
const sellerStalls = {};
<?php foreach ($sellers as $seller): ?>
    sellerStalls[<?= $seller['id'] ?>] = [
        <?php foreach ($stalls as $stall): if ($stall['seller_id'] == $seller['id']): ?>
            {id: <?= $stall['id'] ?>, name: "<?= htmlspecialchars($stall['name'], ENT_QUOTES) ?>"},
        <?php endif; endforeach; ?>
    ];
<?php endforeach; ?>

function updateStallDropdown(sellerId, stallSelectId, selectedStallId = null) {
    const select = document.getElementById(stallSelectId);
    select.innerHTML = '';
    if (sellerStalls[sellerId]) {
        sellerStalls[sellerId].forEach(stall => {
            const opt = document.createElement('option');
            opt.value = stall.id;
            opt.textContent = stall.name;
            if (selectedStallId && stall.id == selectedStallId) opt.selected = true;
            select.appendChild(opt);
        });
    }
}
</script>
<?php foreach ($products as $prod): ?>
<script>
// On edit modal open, update stalls for the current seller
(function() {
    var modal = document.getElementById('editProductModal<?= $prod['id'] ?>');
    modal.addEventListener('show.bs.modal', function () {
        var sellerId = document.getElementById('edit_seller_id_<?= $prod['id'] ?>').value;
        updateStallDropdown(sellerId, 'edit_stall_id_<?= $prod['id'] ?>', <?= $prod['stall_id'] ?>);
    });
    // Also update on seller change
    document.getElementById('edit_seller_id_<?= $prod['id'] ?>').addEventListener('change', function() {
        updateStallDropdown(this.value, 'edit_stall_id_<?= $prod['id'] ?>');
    });
})();
</script>
<?php endforeach; ?> 