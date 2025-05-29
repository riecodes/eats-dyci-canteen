<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/config.php';
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'buyer') {
    header('location:../public/login.php');
    exit();
}
$buyer_id = $_SESSION['user_id'];
// Fetch current buyer info
$stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ? AND role = "buyer"');
$stmt->execute([$buyer_id]);
$buyer = $stmt->fetch();
if (!$buyer) {
    echo '<div class="alert alert-danger">Account not found.</div>';
    return;
}
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$name || !$email) {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        // Check for email conflict
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $buyer_id]);
        if ($stmt->fetch()) {
            $error = 'Email already in use.';
        } else {
            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?');
                $ok = $stmt->execute([$name, $email, $hash, $buyer_id]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
                $ok = $stmt->execute([$name, $email, $buyer_id]);
            }
            if ($ok) {
                $success = 'Account updated!';
                $_SESSION['user_name'] = $name;
            } else {
                $error = 'Failed to update account.';
            }
        }
    }
}
?>
<div class="container mt-4">
    <h2 class="mb-4">My Account</h2>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <input type="hidden" name="update_account" value="1">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($buyer['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email address</label>
            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($buyer['email']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">New Password (leave blank to keep current)</label>
            <input type="password" class="form-control" name="password" autocomplete="new-password">
        </div>
 