<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/config.php';
require_once '../includes/db.php';

// Mark all announcements as read for this user
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('INSERT IGNORE INTO announcement_reads (user_id, announcement_id, read_at) SELECT ?, id, NOW() FROM announcements');
$stmt->execute([$user_id]);

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
.announcement-post {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(23, 14, 99, 0.06);
    padding: 1.1rem 1.2rem 0.8rem 1.2rem;
    margin-bottom: 1.2rem;
    max-width: 480px;
    margin-left: auto;
    margin-right: auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.3rem;
}
.announcement-post .author {
    font-weight: 600;
    color: #170e63;
    margin-bottom: 0.1rem;
    align-self: center;
}
.announcement-post .meta {
    font-size: 0.97rem;
    color: #888;
    margin-bottom: 0.2rem;
    align-self: center;
}
.announcement-post .type-badge {
    display: inline-block;
    padding: 0.2rem 0.7rem;
    border-radius: 1rem;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.2rem;
    color: #fff;
}
.announcement-post .type-info { background: #0dcaf0; }
.announcement-post .type-warning { background: #fd7e14; }
.announcement-post .type-promo { background: #198754; }
.announcement-post .post-image {
    max-width: 100%;
    max-height: 220px;
    border-radius: 0.7rem;
    margin: 0.5rem 0 0.3rem 0;
    object-fit: cover;
    display: block;
    align-self: center;
}
.announcement-post .post-title {
    font-size: 1.13rem;
    font-weight: 700;
    margin-bottom: 0.18rem;
    color: #170e63;
    align-self: center;
}
.announcement-post .post-message {
    font-size: 1.01rem;
    margin-bottom: 0.2rem;
    white-space: pre-line;
    align-self: center;
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
</style>
<div class="container-fluid px-4 pt-4">
    <div class="dashboard-section-title mb-3">Announcements</div>
    <?php if (empty($announcements)): ?>
        <div class="alert alert-info">No announcements yet.</div>
    <?php endif; ?>
    <div class="announcement-feed">
    <?php foreach ($announcements as $a): ?>
        <div class="announcement-post">
            <div class="card-header" style="display:flex;align-items:center;gap:0.7rem;">
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
            <div class="post-title"><?= htmlspecialchars($a['title']) ?></div>
            <div class="post-message"><?= nl2br(htmlspecialchars($a['message'])) ?></div>
            <?php if (!empty($a['image'])): ?>
                <img src="<?= htmlspecialchars($a['image']) ?>" class="post-image" alt="Announcement Image">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
</div> 