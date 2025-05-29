<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}
require_once __DIR__ . '/../includes/db.php';

$add_success = $add_error = '';
$edit_success = $edit_error = '';
$delete_success = $delete_error = '';
// Fetch all sellers for owner dropdown
$sellers = $pdo->query("SELECT id, name FROM users WHERE role = 'seller' ORDER BY name ASC")->fetchAll();
// Fetch all canteens for canteen dropdown
$canteens = $pdo->query("SELECT id, name FROM canteens ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stall'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $user_id = $_POST['user_id'] ?? null;
    $canteen_id = $_POST['canteen_id'] ?? null;
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $target = '../assets/imgs/stall_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_url = $target;
        }
    }
    if (!$name) {
        $add_error = 'Stall name is required.';
    } elseif (!$canteen_id || !in_array($canteen_id, array_column($canteens, 'id'))) {
        $add_error = 'Canteen is required.';
    } elseif ($user_id && !in_array($user_id, array_column($sellers, 'id'))) {
        $add_error = 'Invalid seller selected.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO stalls (name, description, seller_id, canteen_id, image) VALUES (?, ?, ?, ?, ?)');
        if ($stmt->execute([$name, $description, $user_id ?: null, $canteen_id, $image_url])) {
            $add_success = 'Stall added successfully!';
        } else {
            $add_error = 'Failed to add stall.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_stall_id'])) {
    $edit_id = intval($_POST['edit_stall_id']);
    $edit_name = trim($_POST['edit_name'] ?? '');
    $edit_description = trim($_POST['edit_description'] ?? '');
    $edit_user_id = $_POST['edit_user_id'] ?? null;
    $edit_canteen_id = $_POST['edit_canteen_id'] ?? null;
    $edit_image_url = $_POST['current_image'] ?? null;
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION);
        $target = '../assets/imgs/stall_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $target)) {
            $edit_image_url = $target;
        }
    }
    if (!$edit_name) {
        $edit_error = 'Stall name is required.';
    } elseif (!$edit_canteen_id || !in_array($edit_canteen_id, array_column($canteens, 'id'))) {
        $edit_error = 'Canteen is required.';
    } elseif ($edit_user_id && !in_array($edit_user_id, array_column($sellers, 'id'))) {
        $edit_error = 'Invalid seller selected.';
    } else {
        $stmt = $pdo->prepare('UPDATE stalls SET name = ?, description = ?, seller_id = ?, canteen_id = ?, image = ? WHERE id = ?');
        if ($stmt->execute([$edit_name, $edit_description, $edit_user_id ?: null, $edit_canteen_id, $edit_image_url, $edit_id])) {
            $edit_success = 'Stall updated successfully!';
        } else {
            $edit_error = 'Failed to update stall.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_stall_id'])) {
    $delete_id = intval($_POST['delete_stall_id']);
    $stmt = $pdo->prepare('DELETE FROM stalls WHERE id = ?');
    if ($stmt->execute([$delete_id])) {
        $delete_success = 'Stall deleted successfully!';
    } else {
        $delete_error = 'Failed to delete stall.';
    }
}
// Fetch all stalls with owner name and canteen name
$stmt = $pdo->query('SELECT stalls.id, stalls.name, stalls.description, users.name AS seller_name, stalls.seller_id, stalls.canteen_id, canteens.name AS canteen_name, stalls.image FROM stalls LEFT JOIN users ON stalls.seller_id = users.id LEFT JOIN canteens ON stalls.canteen_id = canteens.id ORDER BY stalls.id ASC');
$stalls = $stmt->fetchAll();
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
  <div class="dashboard-section-title mb-3">Stall Management</div>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="fw-bold">All Stalls</div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStallModal">Add Stall</button>
  </div>
  <?php if ($add_success): ?><div class="alert alert-success mb-2"><?php echo $add_success; ?></div><?php endif; ?>
  <?php if ($add_error): ?><div class="alert alert-danger mb-2"><?php echo $add_error; ?></div><?php endif; ?>
  <?php if ($edit_success): ?><div class="alert alert-success mb-2"><?php echo $edit_success; ?></div><?php endif; ?>
  <?php if ($edit_error): ?><div class="alert alert-danger mb-2"><?php echo $edit_error; ?></div><?php endif; ?>
  <?php if ($delete_success): ?><div class="alert alert-success mb-2"><?php echo $delete_success; ?></div><?php endif; ?>
  <?php if ($delete_error): ?><div class="alert alert-danger mb-2"><?php echo $delete_error; ?></div><?php endif; ?>
  <div class="dashboard-table mb-4">
    <table class="table mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Description</th>
          <th>Canteen</th>
          <th>Owner (Seller)</th>
          <th>Image</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($stalls as $stall): ?>
        <tr>
          <td><?php echo $stall['id']; ?></td>
          <td><?php echo htmlspecialchars($stall['name']); ?></td>
          <td><?php echo htmlspecialchars($stall['description']); ?></td>
          <td><?php echo htmlspecialchars($stall['canteen_name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($stall['seller_name'] ?? 'Unassigned'); ?></td>
          <td>
            <?php if (!empty($stall['image'])): ?>
              <img src="<?= htmlspecialchars($stall['image']) ?>" alt="Stall Image" style="max-width:60px;max-height:60px;object-fit:cover;" />
            <?php else: ?>
              <span class="text-muted">No image</span>
            <?php endif; ?>
          </td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editStallModal<?php echo $stall['id']; ?>">Edit</button>
            <form method="post" style="display:inline">
              <input type="hidden" name="delete_stall_id" value="<?php echo $stall['id']; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this stall?')">Delete</button>
            </form>
            <!-- Edit Stall Modal -->
            <div class="modal fade" id="editStallModal<?php echo $stall['id']; ?>" tabindex="-1" aria-labelledby="editStallModalLabel<?php echo $stall['id']; ?>" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editStallModalLabel<?php echo $stall['id']; ?>">Edit Stall</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form method="post" autocomplete="off">
                      <input type="hidden" name="edit_stall_id" value="<?php echo $stall['id']; ?>">
                      <div class="mb-3">
                        <label class="form-label">Stall Name</label>
                        <input type="text" class="form-control" name="edit_name" value="<?php echo htmlspecialchars($stall['name']); ?>" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="edit_description" rows="2"><?php echo htmlspecialchars($stall['description']); ?></textarea>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Canteen</label>
                        <select class="form-select" name="edit_canteen_id" required>
                          <option value="">Select Canteen</option>
                          <?php foreach ($canteens as $canteen): ?>
                            <option value="<?php echo $canteen['id']; ?>" <?php if ($stall['canteen_id'] == $canteen['id']) echo 'selected'; ?>><?php echo htmlspecialchars($canteen['name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Owner (Seller)</label>
                        <select class="form-select" name="edit_user_id">
                          <option value="">Unassigned</option>
                          <?php foreach ($sellers as $seller): ?>
                            <option value="<?php echo $seller['id']; ?>" <?php if ($stall['seller_id'] == $seller['id']) echo 'selected'; ?>><?php echo htmlspecialchars($seller['name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Image (optional)</label>
                        <input type="file" class="form-control" name="edit_image" accept="image/*">
                        <?php if (!empty($stall['image'])): ?>
                          <img src="<?= htmlspecialchars($stall['image']) ?>" alt="Current Image" style="max-width:60px;max-height:60px;object-fit:cover;margin-top:5px;" />
                        <?php endif; ?>
                        <input type="hidden" name="current_image" value="<?= htmlspecialchars($stall['image'] ?? '') ?>">
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
  <!-- Add Stall Modal -->
  <div class="modal fade" id="addStallModal" tabindex="-1" aria-labelledby="addStallModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addStallModalLabel">Add New Stall</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="post" autocomplete="off">
            <input type="hidden" name="add_stall" value="1">
            <div class="mb-3">
              <label for="add_stall_name" class="form-label">Stall Name</label>
              <input type="text" class="form-control" id="add_stall_name" name="name" required>
            </div>
            <div class="mb-3">
              <label for="add_stall_description" class="form-label">Description</label>
              <textarea class="form-control" id="add_stall_description" name="description" rows="2"></textarea>
            </div>
            <div class="mb-3">
              <label for="add_stall_canteen" class="form-label">Canteen</label>
              <select class="form-select" id="add_stall_canteen" name="canteen_id" required>
                <option value="">Select Canteen</option>
                <?php foreach ($canteens as $canteen): ?>
                  <option value="<?php echo $canteen['id']; ?>"><?php echo htmlspecialchars($canteen['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="add_stall_owner" class="form-label">Owner (Seller)</label>
              <select class="form-select" id="add_stall_owner" name="user_id">
                <option value="">Unassigned</option>
                <?php foreach ($sellers as $seller): ?>
                  <option value="<?php echo $seller['id']; ?>"><?php echo htmlspecialchars($seller['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="add_stall_image" class="form-label">Image (optional)</label>
              <input type="file" class="form-control" id="add_stall_image" name="image" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Add Stall</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div> 