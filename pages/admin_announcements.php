<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/upload.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}

// Handle add announcement
$add_success = $add_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = $_POST['type'] ?? 'info';
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        list($ok, $result) = secure_image_upload($_FILES['image']);
        if ($ok) {
            $image_url = $result;
        } else {
            $add_error = $result;
        }
    }
    if (!$title || !$message) {
        $add_error = 'Title and message are required.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO announcements (title, message, type, image) VALUES (?, ?, ?, ?)');
        if ($stmt->execute([$title, $message, $type, $image_url])) {
            $add_success = 'Announcement added!';
        } else {
            $add_error = 'Failed to add announcement.';
        }
    }
}
// Handle edit announcement
$edit_success = $edit_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_announcement_id'])) {
    $edit_id = intval($_POST['edit_announcement_id']);
    $edit_title = trim($_POST['edit_title'] ?? '');
    $edit_message = trim($_POST['edit_message'] ?? '');
    $edit_type = $_POST['edit_type'] ?? 'info';
    $edit_image_url = $_POST['current_image'] ?? null;
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
        list($ok, $result) = secure_image_upload($_FILES['edit_image']);
        if ($ok) {
            $edit_image_url = $result;
        } else {
            $edit_error = $result;
        }
    }
    if (!$edit_title || !$edit_message) {
        $edit_error = 'Title and message are required.';
    } else {
        $stmt = $pdo->prepare('UPDATE announcements SET title=?, message=?, type=?, image=? WHERE id=?');
        if ($stmt->execute([$edit_title, $edit_message, $edit_type, $edit_image_url, $edit_id])) {
            $edit_success = 'Announcement updated!';
        } else {
            $edit_error = 'Failed to update announcement.';
        }
    }
}
// Handle delete announcement
$delete_success = $delete_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement_id'])) {
    $del_id = intval($_POST['delete_announcement_id']);
    $stmt = $pdo->prepare('DELETE FROM announcements WHERE id=?');
    if ($stmt->execute([$del_id])) {
        $delete_success = 'Announcement deleted!';
    } else {
        $delete_error = 'Failed to delete announcement.';
    }
}
// Fetch all announcements
$announcements = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC')->fetchAll();
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
  <div class="dashboard-section-title mb-3">Announcement Management</div>
  <?php if ($add_success): ?><div class="alert alert-success mb-2"><?= $add_success ?></div><?php endif; ?>
  <?php if ($add_error): ?><div class="alert alert-danger mb-2"><?= $add_error ?></div><?php endif; ?>
  <?php if ($edit_success): ?><div class="alert alert-success mb-2"><?= $edit_success ?></div><?php endif; ?>
  <?php if ($edit_error): ?><div class="alert alert-danger mb-2"><?= $edit_error ?></div><?php endif; ?>
  <?php if ($delete_success): ?><div class="alert alert-success mb-2"><?= $delete_success ?></div><?php endif; ?>
  <?php if ($delete_error): ?><div class="alert alert-danger mb-2"><?= $delete_error ?></div><?php endif; ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="fw-bold">All Announcements</div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">Add Announcement</button>
  </div>
  <div class="dashboard-table mb-4">
    <table class="table mb-0">
      <thead>
        <tr>
          <th>Title</th>
          <th>Message</th>
          <th>Type</th>
          <th>Image</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($announcements as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['title']) ?></td>
          <td><?= nl2br(htmlspecialchars($a['message'])) ?></td>
          <td><?= htmlspecialchars($a['type']) ?></td>
          <td>
            <?php if ($a['image']): ?>
              <img src="<?= htmlspecialchars($a['image']) ?>" alt="Image" style="max-width:80px;max-height:80px;">
            <?php endif; ?>
          </td>
          <td><?= date('Y-m-d H:i', strtotime($a['created_at'])) ?></td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editAnnouncementModal<?= $a['id'] ?>">Edit</button>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this announcement?')">
              <input type="hidden" name="delete_announcement_id" value="<?= $a['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
            <!-- Edit Modal -->
            <div class="modal fade" id="editAnnouncementModal<?= $a['id'] ?>" tabindex="-1" aria-labelledby="editAnnouncementModalLabel<?= $a['id'] ?>" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editAnnouncementModalLabel<?= $a['id'] ?>">Edit Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form method="post" enctype="multipart/form-data">
                      <input type="hidden" name="edit_announcement_id" value="<?= $a['id'] ?>">
                      <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="edit_title" value="<?= htmlspecialchars($a['title']) ?>" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="edit_message" rows="3" required><?= htmlspecialchars($a['message']) ?></textarea>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="edit_type">
                          <option value="info" <?= $a['type'] === 'info' ? 'selected' : '' ?>>Info</option>
                          <option value="warning" <?= $a['type'] === 'warning' ? 'selected' : '' ?>>Warning</option>
                          <option value="promo" <?= $a['type'] === 'promo' ? 'selected' : '' ?>>Promo</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Image (optional)</label>
                        <?php if ($a['image']): ?>
                          <img src="<?= htmlspecialchars($a['image']) ?>" alt="Image" style="max-width:80px;max-height:80px;display:block;margin-bottom:5px;">
                        <?php endif; ?>
                        <input type="file" class="form-control" name="edit_image" accept="image/*">
                        <input type="hidden" name="current_image" value="<?= htmlspecialchars($a['image']) ?>">
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
  <!-- Add Announcement Modal -->
  <div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addAnnouncementModalLabel">Add Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="add_announcement" value="1">
            <div class="mb-3">
              <label class="form-label">Title</label>
              <input type="text" class="form-control" name="title" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Message</label>
              <textarea class="form-control" name="message" rows="3" required></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Type</label>
              <select class="form-select" name="type">
                <option value="info">Info</option>
                <option value="warning">Warning</option>
                <option value="promo">Promo</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Image (optional)</label>
              <input type="file" class="form-control" name="image" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Add Announcement</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div> 