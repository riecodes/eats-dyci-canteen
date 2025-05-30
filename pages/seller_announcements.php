<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/upload.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'seller') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}
$seller_id = $_SESSION['user_id'];
// Get all stalls owned by this seller
$stalls_stmt = $pdo->prepare('SELECT id, name FROM stalls WHERE seller_id = ? ORDER BY name ASC');
$stalls_stmt->execute([$seller_id]);
$stalls_owned = $stalls_stmt->fetchAll();
$stall_ids = array_column($stalls_owned, 'id');
if (empty($stall_ids)) {
    echo '<div class="alert alert-warning">You do not own any stalls. Please contact admin.</div>';
    return;
}
// Handle add announcement
$add_success = $add_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = $_POST['type'] ?? 'info';
    $stall_id = intval($_POST['stall_id'] ?? 0);
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        list($ok, $result) = secure_image_upload($_FILES['image']);
        if ($ok) {
            $image_url = $result;
        } else {
            $add_error = $result;
        }
    }
    if (!$title || !$message || !$stall_id || !in_array($stall_id, $stall_ids)) {
        $add_error = 'Title, message, and valid stall are required.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO announcements (title, message, type, image, seller_id, stall_id) VALUES (?, ?, ?, ?, ?, ?)');
        if ($stmt->execute([$title, $message, $type, $image_url, $seller_id, $stall_id])) {
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
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
    $edit_stall_id = intval($_POST['edit_stall_id'] ?? 0);
    $edit_image_url = $_POST['current_image'] ?? null;
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
        list($ok, $result) = secure_image_upload($_FILES['edit_image']);
        if ($ok) {
            $edit_image_url = $result;
        } else {
            $edit_error = $result;
        }
    }
    if (!$edit_title || !$edit_message || !$edit_stall_id || !in_array($edit_stall_id, $stall_ids)) {
        $edit_error = 'Title, message, and valid stall are required.';
    } else {
        $stmt = $pdo->prepare('UPDATE announcements SET title=?, message=?, type=?, image=?, stall_id=? WHERE id=? AND seller_id=?');
        if ($stmt->execute([$edit_title, $edit_message, $edit_type, $edit_image_url, $edit_stall_id, $edit_id, $seller_id])) {
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $edit_error = 'Failed to update announcement.';
        }
    }
}
// Handle delete announcement
$delete_success = $delete_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement_id'])) {
    $del_id = intval($_POST['delete_announcement_id']);
    $stmt = $pdo->prepare('DELETE FROM announcements WHERE id=? AND seller_id=?');
    if ($stmt->execute([$del_id, $seller_id])) {
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $delete_error = 'Failed to delete announcement.';
    }
}
// Mark all announcements as read for this seller
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('INSERT IGNORE INTO announcement_reads (user_id, announcement_id, read_at) SELECT ?, id, NOW() FROM announcements');
$stmt->execute([$user_id]);
// Fetch all announcements: admin + this seller's
$stmt = $pdo->prepare('SELECT * FROM announcements WHERE seller_id IS NULL OR seller_id = ? ORDER BY created_at DESC');
$stmt->execute([$seller_id]);
$announcements = $stmt->fetchAll();
// Fetch all sellers (for author mapping)
$sellers = $pdo->query("SELECT id, name FROM users WHERE role = 'seller'")->fetchAll(PDO::FETCH_KEY_PAIR);
// Fetch admin name (assume only one admin, id=1)
$admins = $pdo->query("SELECT id, name FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_KEY_PAIR);
// Fetch all stalls (for stall mapping)
$all_stalls = $pdo->query("SELECT id, name FROM stalls")->fetchAll(PDO::FETCH_KEY_PAIR);
function get_author($a, $admins, $sellers) {
    if (empty($a['seller_id'])) return $admins[1] ?? 'Admin';
    return $sellers[$a['seller_id']] ?? 'Seller';
}
function get_stall($a, $all_stalls) {
    if (empty($a['stall_id'])) return '';
    return $all_stalls[$a['stall_id']] ?? '';
}
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<style>
.announcement-feed {
  max-width: 600px;
  margin: 0 auto;
  padding-bottom: 2rem;
}
.announcement-card {
  background: #fff;
  border-radius: 1.2rem;
  box-shadow: 0 4px 16px rgba(23, 14, 99, 0.08);
  margin-bottom: 2rem;
  padding: 1.5rem 1.5rem 1rem 1.5rem;
  transition: box-shadow 0.2s;
  position: relative;
}
.announcement-card:hover {
  box-shadow: 0 8px 24px rgba(23, 14, 99, 0.13);
}
.card-header {
  display: flex;
  align-items: center;
  margin-bottom: 0.7rem;
}
.avatar {
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, #0dcaf0 60%, #170e63 100%);
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 1.3rem;
  margin-right: 0.9rem;
  box-shadow: 0 2px 8px rgba(23, 14, 99, 0.10);
  text-transform: uppercase;
  letter-spacing: 1px;
}
.author {
  font-weight: 600;
  color: #170e63;
  font-size: 1.08rem;
}
.meta {
  font-size: 0.97rem;
  color: #888;
  margin-top: 2px;
}
.card-title {
  font-size: 1.18rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: #170e63;
}
.card-message {
  font-size: 1.05rem;
  margin-bottom: 0.7rem;
  white-space: pre-line;
  color: #222;
}
.card-image {
  max-width: 100%;
  max-height: 260px;
  border-radius: 0.8rem;
  margin: 0.7rem 0;
  object-fit: cover;
  display: block;
  box-shadow: 0 2px 8px rgba(23, 14, 99, 0.08);
}
.type-badge {
  display: inline-block;
  padding: 0.22rem 0.85rem;
  border-radius: 1rem;
  font-size: 0.89rem;
  font-weight: 600;
  margin-left: 0.5rem;
  color: #fff;
  vertical-align: middle;
}
.type-info { background: #0dcaf0; }
.type-warning { background: #fd7e14; }
.type-promo { background: #198754; }
.card-actions {
  margin-top: 0.7rem;
  display: flex;
  gap: 0.5rem;
}
.card-actions button,
.card-actions .btn {
  font-size: 0.97rem;
  border-radius: 0.7rem;
  padding: 0.35rem 1.1rem;
  border: none;
  background: #f5f6fa;
  color: #170e63;
  transition: background 0.15s, color 0.15s;
}
.card-actions button:hover,
.card-actions .btn:hover {
  background: #0dcaf0;
  color: #fff;
}
@media (max-width: 700px) {
  .announcement-feed, .announcement-card {
    max-width: 100%;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }
  .announcement-card {
    padding: 1rem 0.7rem 0.7rem 0.7rem;
  }
}
</style>
<div class="container-fluid px-4 pt-4">
  <div class="dashboard-section-title mb-3">Announcements</div>
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
  <div class="announcement-feed">
    <?php foreach ($announcements as $a): ?>
      <div class="announcement-card">
        <div class="card-header">
          <div class="avatar">
            <?= empty($a['seller_id']) ? 'A' : strtoupper(substr($sellers[$a['seller_id']] ?? 'S', 0, 1)) ?>
          </div>
          <div>
            <div class="author">
              <?= empty($a['seller_id']) ? ($admins[1] ?? 'Admin') : htmlspecialchars($sellers[$a['seller_id']] ?? 'Seller') ?>
              <?php if (!empty($a['stall_id']) && isset($all_stalls[$a['stall_id']])): ?>
                <span class="text-muted" style="font-weight:400;">&middot; <?= htmlspecialchars($all_stalls[$a['stall_id']]) ?></span>
              <?php endif; ?>
            </div>
            <div class="meta">
              <?= date('M d, Y h:i A', strtotime($a['created_at'])) ?>
              <span class="type-badge type-<?= htmlspecialchars($a['type']) ?>"><?= ucfirst($a['type']) ?></span>
            </div>
          </div>
        </div>
        <div class="card-title"><?= htmlspecialchars($a['title']) ?></div>
        <div class="card-message"><?= nl2br(htmlspecialchars($a['message'])) ?></div>
        <?php if (!empty($a['image'])): ?>
          <img src="<?= htmlspecialchars($a['image']) ?>" class="card-image" alt="Announcement Image">
        <?php endif; ?>
        <?php if (!empty($a['seller_id']) && $a['seller_id'] == $seller_id): ?>
        <div class="card-actions">
          <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editAnnouncementModal<?= $a['id'] ?>">Edit</button>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this announcement?')">
            <input type="hidden" name="delete_announcement_id" value="<?= $a['id'] ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
          </form>
        </div>
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
                        <label class="form-label">Stall</label>
                        <select class="form-select" name="edit_stall_id" required>
                            <?php foreach ($stalls_owned as $stall): ?>
                                <option value="<?= $stall['id'] ?>" <?= $a['stall_id'] == $stall['id'] ? 'selected' : '' ?>><?= htmlspecialchars($stall['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image (optional)</label>
                        <input type="file" class="form-control" name="edit_image" id="edit_announcement_image_<?= $a['id'] ?>" accept="image/*" onchange="previewEditAnnouncementImage(event, <?= $a['id'] ?>)">
                        <?php if ($a['image']): ?>
                            <img id="edit_announcement_image_preview_<?= $a['id'] ?>" src="<?= htmlspecialchars($a['image']) ?>" alt="Image" style="max-width:80px;max-height:80px;display:block;margin-bottom:5px;">
                        <?php else: ?>
                            <img id="edit_announcement_image_preview_<?= $a['id'] ?>" src="#" alt="Preview" style="display:none;max-width:80px;max-height:80px;margin-bottom:5px;">
                        <?php endif; ?>
                        <input type="hidden" name="current_image" value="<?= htmlspecialchars($a['image']) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
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
                  <form method="post" enctype="multipart/form-data" id="addAnnouncementForm">
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
                          <label class="form-label">Stall</label>
                          <select class="form-select" name="stall_id" required>
                              <option value="">Select stall</option>
                              <?php foreach ($stalls_owned as $stall): ?>
                                  <option value="<?= $stall['id'] ?>"><?= htmlspecialchars($stall['name']) ?></option>
                              <?php endforeach; ?>
                          </select>
                      </div>
                      <div class="mb-3">
                          <label class="form-label">Image (optional)</label>
                          <input type="file" class="form-control" name="image" id="add_announcement_image" accept="image/*" onchange="previewAddAnnouncementImage(event)">
                          <img id="add_announcement_image_preview" src="#" alt="Preview" style="display:none;max-width:80px;max-height:80px;margin-top:8px;" />
                      </div>
                      <button type="submit" class="btn btn-primary">Add Announcement</button>
                  </form>
              </div>
          </div>
      </div>
  </div>
</div>
<script>
function previewAddAnnouncementImage(event) {
    const [file] = event.target.files;
    const preview = document.getElementById('add_announcement_image_preview');
    if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    } else {
        preview.src = '#';
        preview.style.display = 'none';
    }
}
</script>
<script>
function previewEditAnnouncementImage(event, id) {
    const [file] = event.target.files;
    const preview = document.getElementById('edit_announcement_image_preview_' + id);
    if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    } else {
        preview.src = '#';
        preview.style.display = 'none';
    }
}
</script> 