<?php
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'seller') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}
require_once __DIR__ . '/../includes/db.php';

$seller_id = $_SESSION['user_id'];
// Get all stalls owned by this seller
$stall_stmt = $pdo->prepare("SELECT id, name FROM stalls WHERE seller_id = ?");
$stall_stmt->execute([$seller_id]);
$stalls = $stall_stmt->fetchAll();
$stall_ids = array_column($stalls, 'id');

if (empty($stall_ids)) {
    echo '<div class="alert alert-warning">You do not own any stalls. Please contact admin.</div>';
    return;
}

// Handle add/edit/delete
$add_success = $add_error = $edit_success = $edit_error = $delete_success = $delete_error = '';

// Add product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = $_POST['category_id'] ?? null;
    $stall_id = $_POST['stall_id'] ?? null;
    $image_url = null;
    $stock = intval($_POST['stock'] ?? 0);
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $target = '../assets/imgs/products_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_url = $target;
        }
    }
    if (!$name || !$price || !$stall_id) {
        $add_error = 'Name, price, and stall are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image, category_id, stall_id, seller_id, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $description, $price, $image_url, $category_id, $stall_id, $seller_id, $stock])) {
            // POST-Redirect-GET
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
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND stall_id IN (" . implode(',', $stall_ids) . ")");
    if ($stmt->execute([$del_id])) {
        // POST-Redirect-GET
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $delete_error = 'Failed to delete product.';
    }
}
// Edit product (simple version: only name, desc, price, category, image)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product_id'])) {
    $edit_id = intval($_POST['edit_product_id']);
    $edit_name = trim($_POST['edit_name'] ?? '');
    $edit_description = trim($_POST['edit_description'] ?? '');
    $edit_price = floatval($_POST['edit_price'] ?? 0);
    $edit_category_id = $_POST['edit_category_id'] ?? null;
    $edit_image_url = null;
    $edit_stock = intval($_POST['edit_stock'] ?? 0);
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION);
        $target = '../assets/imgs/products_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $target)) {
            $edit_image_url = $target;
        }
    }
    $sql = "UPDATE products SET name=?, description=?, price=?, category_id=?, image=?, stock=? WHERE id=? AND stall_id IN (" . implode(',', $stall_ids) . ")";
    $params = [$edit_name, $edit_description, $edit_price, $edit_category_id, $edit_image_url, $edit_stock, $edit_id];
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        // POST-Redirect-GET
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $edit_error = 'Failed to update product.';
    }
}

// Get all categories globally
$cat_stmt = $pdo->prepare("SELECT * FROM categories");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll();
// Get all products for the seller's stalls
$prod_stmt = $pdo->prepare("SELECT * FROM products WHERE stall_id IN (" . implode(',', $stall_ids) . ")");
$prod_stmt->execute();
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
                <td><?= htmlspecialchars($prod['category_id']) ?></td>
                <td><?= htmlspecialchars($prod['stock']) ?>
<?php if ($prod['stock'] == 0): ?>
  <span class="badge bg-danger ms-2">No Stock</span>
<?php elseif ($prod['stock'] <= 5): ?>
  <span class="badge bg-warning text-dark ms-2">Low Stock</span>
<?php endif; ?>
</td>
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
                                    <label class="form-label" for="edit_image_<?= $prod['id'] ?>">Image</label>
                                    <input type="file" class="form-control" id="edit_image_<?= $prod['id'] ?>" name="edit_image">
                                    <?php if ($prod['image']): ?><img src="<?= $prod['image'] ?>" alt="" style="max-width:60px;max-height:60px;object-fit:cover;"/><?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Stock</label>
                                    <input type="number" class="form-control" name="edit_stock" min="0" value="<?= $prod['stock'] ?>" required>
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
            <form method="post" enctype="multipart/form-data" autocomplete="off">
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
                    <select class="form-select" id="add_product_category_id" name="category_id">
                        <option value="">None</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="add_product_stall_id">Stall</label>
                    <select class="form-select" id="add_product_stall_id" name="stall_id" required>
                        <?php foreach ($stalls as $stall): ?>
                            <option value="<?= $stall['id'] ?>"><?= htmlspecialchars($stall['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="add_product_image">Image</label>
                    <input type="file" class="form-control" id="add_product_image" name="image">
                </div>
                <div class="mb-3">
                    <label class="form-label">Stock</label>
                    <input type="number" class="form-control" name="stock" min="0" value="0" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </form>
          </div>
        </div>
      </div>
    </div>
</div> 