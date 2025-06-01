<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/config.php';
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'buyer') {
    header('location:../index.php');
    exit();
}
$buyer_id = $_SESSION['user_id'];
// Fetch current buyer info
$stmt = $pdo->prepare('SELECT name, email, department, position, faculty FROM users WHERE id = ? AND role = "buyer"');
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
    $department = trim($_POST['department'] ?? '');
    if ($department === '') { $department = null; }
    $position = $_POST['position'] ?? null;
    $faculty = trim($_POST['faculty'] ?? '');
    if (($position ?? '') !== 'Teacher') {
        $faculty = null;
    }
    if ($faculty === '') { $faculty = null; }
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
                $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, password = ?, department = ?, position = ?, faculty = ? WHERE id = ?');
                $ok = $stmt->execute([$name, $email, $hash, $department, $position, $faculty, $buyer_id]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, department = ?, position = ?, faculty = ? WHERE id = ?');
                $ok = $stmt->execute([$name, $email, $department, $position, $faculty, $buyer_id]);
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
            <label class="form-label" for="account_name">Full Name</label>
            <input type="text" class="form-control" id="account_name" name="name" value="<?= htmlspecialchars($buyer['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="account_email">Email address</label>
            <input type="email" class="form-control" id="account_email" name="email" value="<?= htmlspecialchars($buyer['email']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="account_password">New Password (leave blank to keep current)</label>
            <input type="password" class="form-control" id="account_password" name="password" autocomplete="new-password">
        </div>
        <div class="mb-3">
            <label class="form-label" for="account_position">Identification</label>
            <select class="form-select" id="account_position" name="position" required onchange="toggleFacultyDropdown()">                
                <option value="Student" <?= ($buyer['position'] ?? '') === 'Student' ? 'selected' : '' ?>>Student</option>
                <option value="Staff" <?= ($buyer['position'] ?? '') === 'Staff' ? 'selected' : '' ?>>Staff</option>
                <option value="Teacher" <?= ($buyer['position'] ?? '') === 'Teacher' ? 'selected' : '' ?>>Teacher</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="account_department">Department</label>
            <select class="form-select" id="account_department" name="department">
                <option value="">No Department</option>
                <option value="CPE" <?= ($buyer['department'] ?? '') === 'CPE' ? 'selected' : '' ?>>CPE</option>
                <option value="CS" <?= ($buyer['department'] ?? '') === 'CS' ? 'selected' : '' ?>>CS</option>
                <option value="IT" <?= ($buyer['department'] ?? '') === 'IT' ? 'selected' : '' ?>>IT</option>
            </select>
        </div>
        <div class="mb-3" id="facultyDropdownContainer" style="display:<?= ($buyer['position'] ?? '') === 'Teacher' ? '' : 'none' ?>;">
            <label class="form-label" for="account_faculty">Faculty</label>
            <select class="form-select" id="account_faculty" name="faculty">
                <option value="">No Faculty</option>
                <option value="CCS" <?= ($buyer['faculty'] ?? '') === 'CCS' ? 'selected' : '' ?>>CCS</option>
            </select>
        </div>
        <script>
        function toggleFacultyDropdown() {
            var pos = document.getElementById('account_position').value;
            var facultyDiv = document.getElementById('facultyDropdownContainer');
            var facultySelect = document.getElementById('account_faculty');
            if (pos === 'Teacher') {
                facultyDiv.style.display = '';
            } else {
                facultyDiv.style.display = 'none';
                if (facultySelect) facultySelect.value = '';
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            toggleFacultyDropdown();
            document.getElementById('account_position').addEventListener('change', toggleFacultyDropdown);
        });
        </script>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>
 