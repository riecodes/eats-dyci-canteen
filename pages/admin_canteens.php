<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/upload.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}
// Fetch all canteens
$canteens = $pdo->query('SELECT * FROM canteens ORDER BY id ASC')->fetchAll();
$max_canteens = 3;
$add_success = $add_error = $edit_success = $edit_error = $delete_success = $delete_error = '';
// Add canteen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_canteen'])) {
    $name = trim($_POST['name'] ?? '');
    $image_url = null;
    if (count($canteens) >= $max_canteens) {
        $add_error = 'Maximum of 3 canteens allowed.';
    } elseif (!$name) {
        $add_error = 'Canteen name is required.';
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            list($ok, $result) = secure_image_upload($_FILES['image']);
            if ($ok) {
                $image_url = $result;
            } else {
                $add_error = $result;
            }
        }
        if (!$add_error) {
            $stmt = $pdo->prepare('INSERT INTO canteens (name, image) VALUES (?, ?)');
            if ($stmt->execute([$name, $image_url])) {
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $add_error = 'Failed to add canteen.';
            }
        }
    }
}
// Edit canteen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_canteen_id'])) {
    $edit_id = intval($_POST['edit_canteen_id']);
    $edit_name = trim($_POST['edit_name'] ?? '');
    $edit_image_url = $_POST['current_image'] ?? null;
    if (!$edit_name) {
        $edit_error = 'Canteen name is required.';
    } else {
        if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
            list($ok, $result) = secure_image_upload($_FILES['edit_image']);
            if ($ok) {
                $edit_image_url = $result;
            } else {
                $edit_error = $result;
            }
        }
        if (!$edit_error) {
            $stmt = $pdo->prepare('UPDATE canteens SET name=?, image=? WHERE id=?');
            if ($stmt->execute([$edit_name, $edit_image_url, $edit_id])) {
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $edit_error = 'Failed to update canteen.';
            }
        }
    }
}
// Delete canteen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_canteen_id'])) {
    $del_id = intval($_POST['delete_canteen_id']);
    $stmt = $pdo->prepare('DELETE FROM canteens WHERE id=?');
    if ($stmt->execute([$del_id])) {
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $delete_error = 'Failed to delete canteen.';
    }
}
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
  <div class="dashboard-section-title mb-3">Canteen Management</div>
  <?php if ($add_success): ?><div class="alert alert-success mb-2"><?= $add_success ?></div><?php endif; ?>
  <?php if ($add_error): ?><div class="alert alert-danger mb-2"><?= $add_error ?></div><?php endif; ?>
  <?php if ($edit_success): ?><div class="alert alert-success mb-2"><?= $edit_success ?></div><?php endif; ?>
  <?php if ($edit_error): ?><div class="alert alert-danger mb-2"><?= $edit_error ?></div><?php endif; ?>
  <?php if ($delete_success): ?><div class="alert alert-success mb-2"><?= $delete_success ?></div><?php endif; ?>
  <?php if ($delete_error): ?><div class="alert alert-danger mb-2"><?= $delete_error ?></div><?php endif; ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="fw-bold">All Canteens</div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCanteenModal" <?= count($canteens) >= $max_canteens ? 'disabled' : '' ?>>Add Canteen</button>
  </div>
  <div class="dashboard-table mb-4">
    <table class="table mb-0">
      <thead>
        <tr>
          <th>Name</th>
          <th>Image</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($canteens as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['name']) ?></td>
          <td><?php if ($c['image']): ?><img src="<?= htmlspecialchars($c['image']) ?>" alt="Image" style="max-width:80px;max-height:80px;object-fit:cover;"/><?php endif; ?></td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editCanteenModal<?= $c['id'] ?>">Edit</button>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this canteen?')">
              <input type="hidden" name="delete_canteen_id" value="<?= $c['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
            <!-- Edit Modal -->
            <div class="modal fade" id="editCanteenModal<?= $c['id'] ?>" tabindex="-1" aria-labelledby="editCanteenModalLabel<?= $c['id'] ?>" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editCanteenModalLabel<?= $c['id'] ?>">Edit Canteen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form method="post" enctype="multipart/form-data">
                      <input type="hidden" name="edit_canteen_id" value="<?= $c['id'] ?>">
                      <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="edit_name" value="<?= htmlspecialchars($c['name']) ?>" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Image (optional)</label>
                        <?php if ($c['image']): ?><img src="<?= htmlspecialchars($c['image']) ?>" alt="Image" style="max-width:80px;max-height:80px;display:block;margin-bottom:5px;object-fit:cover;"/><?php endif; ?>
                        <input type="file" class="form-control" name="edit_image" accept="image/*">
                        <input type="hidden" name="current_image" value="<?= htmlspecialchars($c['image']) ?>">
                      </div>
                      <button type="submit" class="btn btn-primary">Save Changes</button>
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
  <!-- Add Canteen Modal -->
  <div class="modal fade" id="addCanteenModal" tabindex="-1" aria-labelledby="addCanteenModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addCanteenModalLabel">Add Canteen</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="add_canteen" value="1">
            <div class="mb-3">
              <label class="form-label">Name</label>
              <input type="text" class="form-control" name="name" required <?= count($canteens) >= $max_canteens ? 'disabled' : '' ?>>
            </div>
            <div class="mb-3">
              <label class="form-label">Image (optional)</label>
              <input type="file" class="form-control" name="image" accept="image/*" <?= count($canteens) >= $max_canteens ? 'disabled' : '' ?>>
            </div>
            <button type="submit" class="btn btn-primary" <?= count($canteens) >= $max_canteens ? 'disabled' : '' ?>>Add Canteen</button>
          </form>
          <?php if (count($canteens) >= $max_canteens): ?>
            <div class="alert alert-info mt-2">Maximum of 3 canteens reached. Delete a canteen to add a new one.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div> 