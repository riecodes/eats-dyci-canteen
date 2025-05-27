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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stall'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $owner_id = $_POST['owner_id'] ?? null;
    if (!$name) {
        $add_error = 'Stall name is required.';
    } elseif ($owner_id && !in_array($owner_id, array_column($sellers, 'id'))) {
        $add_error = 'Invalid seller selected.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO stalls (name, description, owner_id) VALUES (?, ?, ?)');
        if ($stmt->execute([$name, $description, $owner_id ?: null])) {
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
    $edit_owner_id = $_POST['edit_owner_id'] ?? null;
    if (!$edit_name) {
        $edit_error = 'Stall name is required.';
    } elseif ($edit_owner_id && !in_array($edit_owner_id, array_column($sellers, 'id'))) {
        $edit_error = 'Invalid seller selected.';
    } else {
        $stmt = $pdo->prepare('UPDATE stalls SET name = ?, description = ?, owner_id = ? WHERE id = ?');
        if ($stmt->execute([$edit_name, $edit_description, $edit_owner_id ?: null, $edit_id])) {
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
// Fetch all stalls with owner name
$stmt = $pdo->query('SELECT stalls.id, stalls.name, stalls.description, users.name AS owner_name, stalls.owner_id FROM stalls LEFT JOIN users ON stalls.owner_id = users.id ORDER BY stalls.id ASC');
$stalls = $stmt->fetchAll();
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Stall Management</h2>
        <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStallModal">Add Stall</a>
    </div>
    <?php if ($add_success): ?>
        <div class="alert alert-success"><?php echo $add_success; ?></div>
    <?php elseif ($add_error): ?>
        <div class="alert alert-danger"><?php echo $add_error; ?></div>
    <?php endif; ?>
    <?php if ($edit_success): ?>
        <div class="alert alert-success"><?php echo $edit_success; ?></div>
    <?php elseif ($edit_error): ?>
        <div class="alert alert-danger"><?php echo $edit_error; ?></div>
    <?php endif; ?>
    <?php if ($delete_success): ?>
        <div class="alert alert-success"><?php echo $delete_success; ?></div>
    <?php elseif ($delete_error): ?>
        <div class="alert alert-danger"><?php echo $delete_error; ?></div>
    <?php endif; ?>
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Owner (Seller)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($stalls as $stall): ?>
            <tr>
                <td><?php echo $stall['id']; ?></td>
                <td><?php echo htmlspecialchars($stall['name']); ?></td>
                <td><?php echo htmlspecialchars($stall['description']); ?></td>
                <td><?php echo htmlspecialchars($stall['owner_name'] ?? 'Unassigned'); ?></td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editStallModal<?php echo $stall['id']; ?>">Edit</button>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="delete_stall_id" value="<?php echo $stall['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this stall?')">Delete</button>
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
                                    <label class="form-label">Owner (Seller)</label>
                                    <select class="form-select" name="edit_owner_id">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($sellers as $seller): ?>
                                            <option value="<?php echo $seller['id']; ?>" <?php if ($stall['owner_id'] == $seller['id']) echo 'selected'; ?>><?php echo htmlspecialchars($seller['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
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
                    <label for="add_stall_owner" class="form-label">Owner (Seller)</label>
                    <select class="form-select" id="add_stall_owner" name="owner_id">
                        <option value="">Unassigned</option>
                        <?php foreach ($sellers as $seller): ?>
                            <option value="<?php echo $seller['id']; ?>"><?php echo htmlspecialchars($seller['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success w-100">Add Stall</button>
            </form>
          </div>
        </div>
      </div>
    </div>
</div> 