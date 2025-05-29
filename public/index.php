<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EATS-DYCI-CANTEEN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
<<<<<<< HEAD
</head>
<body>
<?php
session_start();
require_once '../includes/db.php';
// Topbar and sidebar are always included
include '../includes/topbar.php';
include '../includes/sidebar.php';
// Routing logic
$page = $_GET['page'] ?? 'dashboard';
$page_file = __DIR__ . '/../pages/' . basename($page) . '.php';
if (file_exists($page_file)) {
    include $page_file;
} else {
    include __DIR__ . '/../pages/dashboard.php';
}
?> 
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
=======
    <link rel="stylesheet" href="../assets/css/index.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    require_once '../includes/db.php';
    ?>
    <?php include '../includes/topbar.php'; ?>
    <div class="main-content d-flex">
        <?php include '../includes/sidebar.php'; ?>
        <div class="page-content-scroll flex-grow-1">
            <?php
            // Routing logic
            $page = $_GET['page'] ?? 'admin_dashboard';
            $page_file = __DIR__ . '/../pages/' . basename($page) . '.php';
            if (file_exists($page_file)) {
                include $page_file;
            } else {
                include __DIR__ . '/../pages/admin_dashboard.php';
            }
            ?>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
>>>>>>> master
</body>
</html> 