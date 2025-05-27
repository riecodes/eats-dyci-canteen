<link rel="stylesheet" href="../assets/css/sidebar.css">
<div class="d-flex">
    <nav class="sidebar flex-shrink-0 p-3 text-secondary" style="width: 240px; min-height: 100vh;">
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <?php $role = $_SESSION['user_role'] ?? ''; ?>
            <?php if ($role === 'admin'): ?>
                <li class="nav-item"><a href="index.php?page=dashboard" class="nav-link <?php echo ($_GET['page'] ?? 'dashboard') === 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                <li><a href="index.php?page=admin_users" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_users' ? 'active' : ''; ?>"><i class="bi bi-people me-2"></i>User Management</a></li>
                <li><a href="index.php?page=admin_stalls" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_stalls' ? 'active' : ''; ?>"><i class="bi bi-shop-window me-2"></i>Stall Management</a></li>
            <?php elseif ($role === 'seller'): ?>
                <li class="nav-item"><a href="index.php?page=seller_dashboard" class="nav-link <?php echo ($_GET['page'] ?? 'seller_dashboard') === 'seller_dashboard' ? 'active' : ''; ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                <li><a href="index.php?page=seller_products" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_products' ? 'active' : ''; ?>"><i class="bi bi-box-seam me-2"></i>Product Management</a></li>
                <li><a href="index.php?page=seller_categories" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_categories' ? 'active' : ''; ?>"><i class="bi bi-tags me-2"></i>Category Management</a></li>
                <li><a href="index.php?page=seller_orders" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_orders' ? 'active' : ''; ?>"><i class="bi bi-receipt me-2"></i>Order Management</a></li>
            <?php elseif ($role === 'buyer'): ?>
                <li><a href="index.php?page=buyer_order" class="nav-link <?php echo ($_GET['page'] ?? '') === 'buyer_order' ? 'active' : ''; ?>"><i class="bi bi-cart-plus me-2"></i>Order Now</a></li>
                <li><a href="index.php?page=buyer_orders" class="nav-link <?php echo ($_GET['page'] ?? '') === 'buyer_orders' ? 'active' : ''; ?>"><i class="bi bi-list-check me-2"></i>My Orders</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <main class="flex-grow-1 p-4"> 