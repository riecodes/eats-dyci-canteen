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

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $target = '../assets/imgs/category_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_url = $target;
        }
    }
    if (!$name) {
        $add_error = 'Name is required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, image, seller_id) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $description, $image_url, $seller_id])) {
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $add_error = 'Failed to add category.';
        }
    }
}
// Delete category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category_id'])) {
    $del_id = $_POST['delete_category_id'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt->execute([$del_id])) {
        $_SESSION['delete_success'] = 'Category deleted successfully!';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $_SESSION['delete_error'] = 'Failed to delete category.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}
// Show feedback after redirect
if (!empty($_SESSION['delete_success'])) { $delete_success = $_SESSION['delete_success']; unset($_SESSION['delete_success']); }
if (!empty($_SESSION['delete_error'])) { $delete_error = $_SESSION['delete_error']; unset($_SESSION['delete_error']); }
// Edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category_id'])) {
    $edit_id = $_POST['edit_category_id'];
    $edit_name = trim($_POST['edit_name'] ?? '');
    $edit_description = trim($_POST['edit_description'] ?? '');
    $edit_image_url = $_POST['current_image'] ?? null;
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION);
        $target = '../assets/imgs/category_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $target)) {
            $edit_image_url = $target;
        }
    }
    if (!$edit_name) {
        $edit_error = 'Name is required.';
    } else {
        $stmt = $pdo->prepare("UPDATE categories SET name=?, description=?, image=? WHERE id=? AND seller_id=?");
        if ($stmt->execute([$edit_name, $edit_description, $edit_image_url, $edit_id, $seller_id])) {
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $edit_error = 'Failed to update category.';
        }
    }
}
// Get all categories for this seller only
$cat_stmt = $pdo->prepare("SELECT * FROM categories WHERE seller_id = ?");
$cat_stmt->execute([$seller_id]);
$categories = $cat_stmt->fetchAll();
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
    <div class="dashboard-section-title mb-3">Category Management</div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="fw-bold">All Categories</div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">Add Category</button>
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
                <th>Icon</th>
                <th>Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?php if ($cat['image']): ?><img src="<?= htmlspecialchars($cat['image']) ?>" alt="" style="max-width:32px;max-height:32px;object-fit:cover;"/><?php endif; ?></td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td><?= htmlspecialchars($cat['description']) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?= $cat['id'] ?>">Edit</button>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="delete_category_id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">Delete</button>
                    </form>
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editCategoryModal<?= $cat['id'] ?>" tabindex="-1" aria-labelledby="editCategoryModalLabel<?= $cat['id'] ?>" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="editCategoryModalLabel<?= $cat['id'] ?>">Edit Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <form method="post" enctype="multipart/form-data" autocomplete="off">
                                <input type="hidden" name="edit_category_id" value="<?= $cat['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label" for="edit_name_<?= $cat['id'] ?>">Name</label>
                                    <input type="text" class="form-control" id="edit_name_<?= $cat['id'] ?>" name="edit_name" value="<?= htmlspecialchars($cat['name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="edit_description_<?= $cat['id'] ?>">Description</label>
                                    <textarea class="form-control" id="edit_description_<?= $cat['id'] ?>" name="edit_description" rows="2"><?= htmlspecialchars($cat['description']) ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="edit_image_<?= $cat['id'] ?>">Icon Image</label>
                                    <input type="file" class="form-control" id="edit_image_<?= $cat['id'] ?>" name="edit_image">
                                    <?php if ($cat['image']): ?><img src="<?= htmlspecialchars($cat['image']) ?>" alt="" style="max-width:32px;max-height:32px;object-fit:cover;"/><?php endif; ?>
                                    <input type="hidden" name="current_image" value="<?= htmlspecialchars($cat['image']) ?>">
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
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="post" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="add_category" value="1">
                <div class="mb-3">
                    <label class="form-label" for="add_category_name">Name</label>
                    <input type="text" class="form-control" id="add_category_name" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="add_category_description">Description</label>
                    <textarea class="form-control" id="add_category_description" name="description" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="add_category_image">Icon Image</label>
                    <input type="file" class="form-control" id="add_category_image" name="image">
                </div>
                <button type="submit" class="btn btn-primary">Add Category</button>
            </form>
          </div>
        </div>
      </div>
    </div>
</div> 