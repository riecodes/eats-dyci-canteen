<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}
require_once __DIR__ . '/../includes/db.php';

$add_success = $add_error = '';
$delete_success = $delete_error = '';
$edit_success = $edit_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (!$name || !$email || !$role || !$password || !$confirm_password) {
        $add_error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $add_error = 'Invalid email address.';
    } elseif ($password !== $confirm_password) {
        $add_error = 'Passwords do not match.';
    } elseif (!in_array($role, ['seller','buyer'])) {
        $add_error = 'Invalid role.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $add_error = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            if ($stmt->execute([$name, $email, $hash, $role])) {
                $add_success = 'User added successfully!';
            } else {
                $add_error = 'Failed to add user.';
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $delete_id = intval($_POST['delete_user_id']);
    if ($delete_id === $_SESSION['user_id']) {
        $delete_error = 'You cannot delete your own account.';
    } else {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        if ($stmt->execute([$delete_id])) {
            $delete_success = 'User deleted successfully!';
        } else {
            $delete_error = 'Failed to delete user.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_id'])) {
    $edit_id = intval($_POST['edit_user_id']);
    $edit_name = trim($_POST['edit_name'] ?? '');
    $edit_email = trim($_POST['edit_email'] ?? '');
    $edit_role = $_POST['edit_role'] ?? '';
    $edit_password = $_POST['edit_password'] ?? '';
    $edit_department = $_POST['edit_department'] ?? null;
    $edit_position = $_POST['edit_position'] ?? null;
    if (!$edit_name || !$edit_email || !$edit_role) {
        $edit_error = 'All fields are required.';
    } elseif (!filter_var($edit_email, FILTER_VALIDATE_EMAIL)) {
        $edit_error = 'Invalid email address.';
    } elseif (!in_array($edit_role, ['admin','seller','buyer'])) {
        $edit_error = 'Invalid role.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$edit_email, $edit_id]);
        if ($stmt->fetch()) {
            $edit_error = 'Email already in use by another user.';
        } else {
            if ($edit_password) {
                $hash = password_hash($edit_password, PASSWORD_DEFAULT);
                if ($edit_role === 'buyer') {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, password = ?, department = ?, position = ? WHERE id = ?');
                    if ($stmt->execute([$edit_name, $edit_email, $edit_role, $hash, $edit_department, $edit_position, $edit_id])) {
                        $edit_success = 'User updated successfully!';
                    } else {
                        $edit_error = 'Failed to update user.';
                    }
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?');
                    if ($stmt->execute([$edit_name, $edit_email, $edit_role, $hash, $edit_id])) {
                        $edit_success = 'User updated successfully!';
                    } else {
                        $edit_error = 'Failed to update user.';
                    }
                }
            } else {
                if ($edit_role === 'buyer') {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, department = ?, position = ? WHERE id = ?');
                    if ($stmt->execute([$edit_name, $edit_email, $edit_role, $edit_department, $edit_position, $edit_id])) {
                        $edit_success = 'User updated successfully!';
                    } else {
                        $edit_error = 'Failed to update user.';
                    }
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
                    if ($stmt->execute([$edit_name, $edit_email, $edit_role, $edit_id])) {
                        $edit_success = 'User updated successfully!';
                    } else {
                        $edit_error = 'Failed to update user.';
                    }
                }
            }
        }
    }
}
$stmt = $pdo->query('SELECT id, name, email, role FROM users ORDER BY id ASC');
$users = $stmt->fetchAll();

// Remove the currently logged-in admin from the user management list and actions
$admin_id = $_SESSION['user_id'];
$users = array_filter($users, function($u) use ($admin_id) { return $u['id'] != $admin_id; });
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
  <div class="dashboard-section-title mb-3">User Management</div>
  <?php if ($add_success): ?><div class="alert alert-success mb-2"><?= $add_success ?></div><?php endif; ?>
  <?php if ($add_error): ?><div class="alert alert-danger mb-2"><?= $add_error ?></div><?php endif; ?>
  <?php if ($delete_success): ?><div class="alert alert-success mb-2"><?= $delete_success ?></div><?php endif; ?>
  <?php if ($delete_error): ?><div class="alert alert-danger mb-2"><?= $delete_error ?></div><?php endif; ?>
  <?php if ($edit_success): ?><div class="alert alert-success mb-2"><?= $edit_success ?></div><?php endif; ?>
  <?php if ($edit_error): ?><div class="alert alert-danger mb-2"><?= $edit_error ?></div><?php endif; ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="fw-bold">All Users</div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
  </div>
  <div class="dashboard-table mb-4">
    <table class="table mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
          <td><?= $user['id'] ?></td>
          <td><?= htmlspecialchars($user['name']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td class="text-capitalize"><?= htmlspecialchars($user['role']) ?></td>
          <td>
            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $user['id'] ?>">Edit</button>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this user?')">
              <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal<?= $user['id'] ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?= $user['id'] ?>" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel<?= $user['id'] ?>">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="post" autocomplete="off">
                  <input type="hidden" name="edit_user_id" value="<?= $user['id'] ?>">
                  <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="edit_name" value="<?= htmlspecialchars($user['name']) ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" class="form-control" name="edit_email" value="<?= htmlspecialchars($user['email']) ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="edit_role" required>
                      <option value="seller" <?= $user['role'] === 'seller' ? 'selected' : '' ?>>Seller</option>
                      <option value="buyer" <?= $user['role'] === 'buyer' ? 'selected' : '' ?>>Buyer</option>
                    </select>
                  </div>
                  <?php if ($user['role'] === 'buyer'): ?>
                    <div class="mb-3">
                      <label class="form-label">Department</label>
                      <select class="form-select" name="edit_department">
                        <option value="CPE" <?= ($user['department'] ?? '') === 'CPE' ? 'selected' : '' ?>>CPE</option>
                        <option value="CS" <?= ($user['department'] ?? '') === 'CS' ? 'selected' : '' ?>>CS</option>
                        <option value="IT" <?= ($user['department'] ?? '') === 'IT' ? 'selected' : '' ?>>IT</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Position</label>
                      <select class="form-select" name="edit_position">
                        <option value="Student" <?= ($user['position'] ?? '') === 'Student' ? 'selected' : '' ?>>Student</option>
                        <option value="Staff" <?= ($user['position'] ?? '') === 'Staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="Teacher" <?= ($user['position'] ?? '') === 'Teacher' ? 'selected' : '' ?>>Teacher</option>
                      </select>
                    </div>
                  <?php endif; ?>
                  <div class="mb-3">
                    <label class="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" class="form-control" name="edit_password" autocomplete="new-password">
                  </div>
                  <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <!-- Add User Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="post" autocomplete="off">
            <input type="hidden" name="add_user" value="1">
            <div class="mb-3">
              <label for="add_name" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="add_name" name="name" required>
            </div>
            <div class="mb-3">
              <label for="add_email" class="form-label">Email address</label>
              <input type="email" class="form-control" id="add_email" name="email" required>
            </div>
            <div class="mb-3">
              <label for="add_role" class="form-label">Role</label>
              <select class="form-select" id="add_role" name="role" required>
                <option value="">Select role</option>
                <option value="seller">Seller</option>
                <option value="buyer">Buyer</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="add_password" class="form-label">Password</label>
              <input type="password" class="form-control" id="add_password" name="password" required>
            </div>
            <div class="mb-3">
              <label for="add_confirm_password" class="form-label">Confirm Password</label>
              <input type="password" class="form-control" id="add_confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Add User</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div> 