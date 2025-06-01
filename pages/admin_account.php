<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/config.php';
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('location:../index.php');
    exit();
}
$admin_id = $_SESSION['user_id'];
// Fetch current admin info
$stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ? AND role = "admin"');
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
if (!$admin) {
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
        $stmt->execute([$email, $admin_id]);
        if ($stmt->fetch()) {
            $error = 'Email already in use.';
        } else {
            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?');
                $ok = $stmt->execute([$name, $email, $hash, $admin_id]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
                $ok = $stmt->execute([$name, $email, $admin_id]);
            }
            if ($ok) {
                $success = 'Account updated!';
                $_SESSION['user_name'] = $name;
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $error = 'Failed to update account.';
            }
        }
    }
}
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
  <div class="dashboard-section-title mb-3">My Account</div>
  <?php if ($success): ?><div class="alert alert-success mb-2"><?= $success ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger mb-2"><?= $error ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="update_account" value="1">
    <div class="mb-3">
      <label class="form-label">Full Name</label>
      <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email address</label>
      <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">New Password (leave blank to keep current)</label>
      <input type="password" class="form-control" name="password" autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary">Save Changes</button>
  </form>
</div> 