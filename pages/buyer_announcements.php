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
.announcement-post {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(23, 14, 99, 0.06);
    padding: 1.5rem 1.5rem 1rem 1.5rem;
    margin-bottom: 2rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}
.announcement-post .author {
    font-weight: 600;
    color: #170e63;
    margin-bottom: 0.2rem;
}
.announcement-post .meta {
    font-size: 0.95rem;
    color: #888;
    margin-bottom: 0.5rem;
}
.announcement-post .type-badge {
    display: inline-block;
    padding: 0.2rem 0.7rem;
    border-radius: 1rem;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #fff;
}
.announcement-post .type-info { background: #0dcaf0; }
.announcement-post .type-warning { background: #fd7e14; }
.announcement-post .type-promo { background: #198754; }
.announcement-post .post-image {
    max-width: 100%;
    max-height: 250px;
    border-radius: 0.7rem;
    margin: 0.7rem 0;
    object-fit: cover;
    display: block;
}
.announcement-post .post-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0.3rem;
    color: #170e63;
}
.announcement-post .post-message {
    font-size: 1.05rem;
    margin-bottom: 0.5rem;
    white-space: pre-line;
}
</style>
<div class="container-fluid px-4 pt-4">
    <div class="dashboard-section-title mb-3">Announcements</div>
    <?php if (empty($announcements)): ?>
        <div class="alert alert-info">No announcements yet.</div>
    <?php endif; ?>
    <?php foreach ($announcements as $a): ?>
        <div class="announcement-post">
            <div class="author">
                <?= htmlspecialchars(get_author($a, $admins, $sellers)) ?>
                <?php if (!empty($a['stall_id']) && get_stall($a, $stalls)): ?>
                    <span class="text-muted" style="font-weight:400;">&middot; <?= htmlspecialchars(get_stall($a, $stalls)) ?></span>
                <?php endif; ?>
            </div>
            <div class="meta">
                <span><?= date('M d, Y h:i A', strtotime($a['created_at'])) ?></span>
                <span class="type-badge type-<?= htmlspecialchars($a['type']) ?> ms-2"><?= ucfirst($a['type']) ?></span>
            </div>
            <div class="post-title"><?= htmlspecialchars($a['title']) ?></div>
            <div class="post-message"><?= nl2br(htmlspecialchars($a['message'])) ?></div>
            <?php if (!empty($a['image'])): ?>
                <img src="<?= htmlspecialchars($a['image']) ?>" class="post-image" alt="Announcement Image">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div> 