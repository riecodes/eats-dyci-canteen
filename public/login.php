<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
require_once '../includes/db.php';

$email = $password = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Both fields are required.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            // Redirect by role
            if ($user['role'] === 'admin') {
                header('Location: index.php?page=dashboard');
            } elseif ($user['role'] === 'seller') {
                header('Location: index.php?page=seller_dashboard');
            } else {
                header('Location: index.php?page=buyer_order');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="login-container">
    <div class="d-flex align-items-center justify-content-center">
        <img src="../assets/imgs/dyci-logo.png" alt="DYCI Logo" class="login-logo">        
    </div>
    <div class="login-title">EatsDYCI</div>
    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
        </div>
        <div class="mb-1">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="login-links align-items-center">
            <div></div>
            <span data-bs-toggle="tooltip" data-bs-placement="left" title="Forgot your password? Please contact an admin to reset your account.">
                <i class="fa fa-info-circle text-info" style="cursor:pointer;"></i>
            </span>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
    <div class="register-link mt-3">
        <span data-bs-toggle="tooltip" data-bs-placement="right" title="Buyers can no longer self-register. Please contact an admin to create your account.">
            <i class="fa fa-info-circle text-info" style="cursor:pointer;"></i>
        </span>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
</body>
</html> 