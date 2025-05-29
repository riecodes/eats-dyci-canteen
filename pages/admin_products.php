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
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $target = '../assets/imgs/products_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_url = $target;
        }
    }
    if (!$name || !$price || !$stall_id || !$seller_id) {
        $add_error = 'Name, price, seller, and stall are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image, category_id, stall_id, seller_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $description, $price, $image_url, $category_id, $stall_id, $seller_id])) {
            $add_success = 'Product added successfully!';
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
        $delete_success = 'Product deleted.';
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
    $edit_image_url = null;
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION);
        $target = '../assets/imgs/products_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $target)) {
            $edit_image_url = $target;
        }
    }
    $sql = "UPDATE products SET name=?, description=?, price=?, category_id=?, stall_id=?, seller_id=?";
    $params = [$edit_name, $edit_description, $edit_price, $edit_category_id, $edit_stall_id, $edit_seller_id];
    if ($edit_image_url) {
        $sql .= ", image=?";
        $params[] = $edit_image_url;
    }
    $sql .= " WHERE id=?";
    $params[] = $edit_id;
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        $edit_success = 'Product updated.';
    } else {
        $edit_error = 'Failed to update product.';
    }
}
// Fetch all products with joins
$prod_stmt = $pdo->query("SELECT p.*, c.name AS category_name, u.name AS seller_name, s.name AS stall_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN users u ON p.seller_id = u.id LEFT JOIN stalls s ON p.stall_id = s.id ORDER BY p.id DESC");
$products = $prod_stmt->fetchAll();
?>
<div class="container-fluid px-4 pt-4">
    <div class="dashboard-section-title mb-3">Product Management</div>
    <?php if ($add_success): ?><div class="alert alert-success"><?= $add_success ?></div><?php endif; ?>
    <?php if ($add_error): ?><div class="alert alert-danger"><?= $add_error ?></div><?php endif; ?>
    <?php if ($edit_success): ?><div class="alert alert-success"><?= $edit_success ?></div><?php endif; ?>
    <?php if ($edit_error): ?><div class="alert alert-danger"><?= $edit_error ?></div><?php endif; ?>
    <?php if ($delete_success): ?><div class="alert alert-success"><?= $delete_success ?></div><?php endif; ?>
    <?php if ($delete_error): ?><div class="alert alert-danger"><?= $delete_error ?></div><?php endif; ?>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">Add Product</button>
    <div class="dashboard-table">
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Price</th>
                <th>Category</th>
                <th>Seller</th>
                <th>Stall</th>
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
                <td><?php if ($prod['image']): ?><img src="<?= $prod['image'] ?>" alt="" style="max-width:60px;max-height:60px;object-fit:cover;"/><?php endif; ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editProductModal<?= $prod['id'] ?>">Edit</button>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="delete_product_id" value="<?= $prod['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')">Delete</button>
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
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="edit_name" value="<?= htmlspecialchars($prod['name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="edit_description" rows="2"><?= htmlspecialchars($prod['description']) ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" step="0.01" class="form-control" name="edit_price" value="<?= $prod['price'] ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" name="edit_category_id">
                                        <option value="">None</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?php if ($prod['category_id'] == $cat['id']) echo 'selected'; ?>><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Seller</label>
                                    <select class="form-select" name="edit_seller_id" required>
                                        <?php foreach ($sellers as $seller): ?>
                                            <option value="<?= $seller['id'] ?>" <?php if ($prod['seller_id'] == $seller['id']) echo 'selected'; ?>><?= htmlspecialchars($seller['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Stall</label>
                                    <select class="form-select" name="edit_stall_id" required>
                                        <?php foreach ($stalls as $stall): ?>
                                            <option value="<?= $stall['id'] ?>" <?php if ($prod['stall_id'] == $stall['id']) echo 'selected'; ?>><?= htmlspecialchars($stall['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Image</label>
                                    <input type="file" class="form-control" name="edit_image">
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
            <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="post" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="add_product" value="1">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" name="price" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category_id">
                        <option value="">None</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Seller</label>
                    <select class="form-select" name="seller_id" required>
                        <?php foreach ($sellers as $seller): ?>
                            <option value="<?= $seller['id'] ?>"><?= htmlspecialchars($seller['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Stall</label>
                    <select class="form-select" name="stall_id" required>
                        <?php foreach ($stalls as $stall): ?>
                            <option value="<?= $stall['id'] ?>"><?= htmlspecialchars($stall['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image</label>
                    <input type="file" class="form-control" name="image">
                </div>
                <button type="submit" class="btn btn-success w-100">Add Product</button>
            </form>
          </div>
        </div>
      </div>
    </div>
</div> 