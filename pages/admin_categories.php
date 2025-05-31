<?php
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}
require_once __DIR__ . '/../includes/db.php';

// Handle delete
$delete_success = $delete_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category_id'])) {
    $del_id = $_POST['delete_category_id'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt->execute([$del_id])) {
        $delete_success = 'Category deleted successfully!';
    } else {
        $delete_error = 'Failed to delete category.';
    }
}

// Handle edit
$edit_success = $edit_error = '';
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
        $stmt = $pdo->prepare("UPDATE categories SET name=?, description=?, image=? WHERE id=?");
        if ($stmt->execute([$edit_name, $edit_description, $edit_image_url, $edit_id])) {
            $edit_success = 'Category updated successfully!';
        } else {
            $edit_error = 'Failed to update category.';
        }
    }
}

// Get all categories with seller info
$stmt = $pdo->query("SELECT c.*, u.name AS seller_name FROM categories c LEFT JOIN users u ON c.seller_id = u.id");
$categories = $stmt->fetchAll();
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
    <div class="dashboard-section-title mb-3">All Categories</div>
    <?php if ($delete_success): ?><div class="alert alert-success mb-2"><?= $delete_success ?></div><?php endif; ?>
    <?php if ($delete_error): ?><div class="alert alert-danger mb-2"><?= $delete_error ?></div><?php endif; ?>
    <?php if ($edit_success): ?><div class="alert alert-success mb-2"><?= $edit_success ?></div><?php endif; ?>
    <?php if ($edit_error): ?><div class="alert alert-danger mb-2"><?= $edit_error ?></div><?php endif; ?>
    <div class="dashboard-table mb-4">
    <table class="table mb-0">
        <thead class="table-light">
            <tr>
                <th>Icon</th>
                <th>Name</th>
                <th>Description</th>
                <th>Seller</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?php if ($cat['image']): ?><img src="<?= htmlspecialchars($cat['image']) ?>" alt="" style="max-width:32px;max-height:32px;object-fit:cover;"/><?php endif; ?></td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td><?= htmlspecialchars($cat['description']) ?></td>
                <td><?= $cat['seller_name'] ? htmlspecialchars($cat['seller_name']) : '<span class="text-muted">Admin</span>' ?></td>
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
</div> 