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
    $stall_id = $_POST['stall_id'] ?? null;
    if (!$name || !$stall_id) {
        $add_error = 'Name and stall are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, stall_id) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $description, $stall_id])) {
            $add_success = 'Category added successfully!';
        } else {
            $add_error = 'Failed to add category.';
        }
    }
}
// Delete category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category_id'])) {
    $del_id = $_POST['delete_category_id'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND stall_id IN (" . implode(',', $stall_ids) . ")");
    if ($stmt->execute([$del_id])) {
        $delete_success = 'Category deleted.';
    } else {
        $delete_error = 'Failed to delete category.';
    }
}
// Edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category_id'])) {
    $edit_id = $_POST['edit_category_id'];
    $edit_name = trim($_POST['edit_name'] ?? '');
    $edit_description = trim($_POST['edit_description'] ?? '');
    if (!$edit_name) {
        $edit_error = 'Name is required.';
    } else {
        $stmt = $pdo->prepare("UPDATE categories SET name=?, description=? WHERE id=? AND stall_id IN (" . implode(',', $stall_ids) . ")");
        if ($stmt->execute([$edit_name, $edit_description, $edit_id])) {
            $edit_success = 'Category updated.';
        } else {
            $edit_error = 'Failed to update category.';
        }
    }
}
// Get all categories globally
$cat_stmt = $pdo->prepare("SELECT * FROM categories");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll();
?>
<div class="container-fluid">
    <h2>Category Management</h2>
    <?php if ($add_success): ?><div class="alert alert-success"><?= $add_success ?></div><?php endif; ?>
    <?php if ($add_error): ?><div class="alert alert-danger"><?= $add_error ?></div><?php endif; ?>
    <?php if ($edit_success): ?><div class="alert alert-success"><?= $edit_success ?></div><?php endif; ?>
    <?php if ($edit_error): ?><div class="alert alert-danger"><?= $edit_error ?></div><?php endif; ?>
    <?php if ($delete_success): ?><div class="alert alert-success"><?= $delete_success ?></div><?php endif; ?>
    <?php if ($delete_error): ?><div class="alert alert-danger"><?= $delete_error ?></div><?php endif; ?>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">Add Category</button>
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td><?= htmlspecialchars($cat['description']) ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?= $cat['id'] ?>">Edit</button>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="delete_category_id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">Delete</button>
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
                            <form method="post" autocomplete="off">
                                <input type="hidden" name="edit_category_id" value="<?= $cat['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="edit_name" value="<?= htmlspecialchars($cat['name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="edit_description" rows="2"><?= htmlspecialchars($cat['description']) ?></textarea>
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
    <!-- Add Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="post" autocomplete="off">
                <input type="hidden" name="add_category" value="1">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-success w-100">Add Category</button>
            </form>
          </div>
        </div>
      </div>
    </div>
</div> 