<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/config.php';
require_once '../includes/db.php';

// Fetch all announcements (admin and seller)
$announcements = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC')->fetchAll();

// Fetch all sellers (for author mapping)
$sellers = $pdo->query("SELECT id, name FROM users WHERE role = 'seller'")->fetchAll(PDO::FETCH_KEY_PAIR);
// Fetch admin name (assume only one admin, id=1)
$admins = $pdo->query("SELECT id, name FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_KEY_PAIR);
// Fetch all stalls (for stall mapping)
$stalls = $pdo->query("SELECT id, name FROM stalls")->fetchAll(PDO::FETCH_KEY_PAIR);

function get_author($a, $admins, $sellers) {
    if (empty($a['seller_id'])) return $admins[1] ?? 'Admin';
    return $sellers[$a['seller_id']] ?? 'Seller';
}
function get_stall($a, $stalls) {
    if (empty($a['stall_id'])) return '';
    return $stalls[$a['stall_id']] ?? '';
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
    <?php if (empty($announcements)): ?>
        <div class="alert alert-info">No announcements yet.</div>
    <?php endif; ?>
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
                        <?php if (!empty($a['stall_id']) && isset($stalls[$a['stall_id']])): ?>
                            <span class="text-muted" style="font-weight:400;">&middot; <?= htmlspecialchars($stalls[$a['stall_id']]) ?></span>
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
        </div>
    <?php endforeach; ?>
    </div>
</div> 