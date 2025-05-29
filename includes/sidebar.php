<link rel="stylesheet" href="../assets/css/sidebar.css">
<nav class="sidebar flex-shrink-0 p-3 text-secondary">
    <?php $role = $_SESSION['user_role'] ?? ''; ?>
    <?php if ($role === 'admin'): ?>
        <div class="sidebar-panel-label text-center fw-bold">ADMIN PANEL</div>
    <?php elseif ($role === 'seller'): ?>
        <div class="sidebar-panel-label text-center fw-bold ">SELLER PANEL</div>
    <?php elseif ($role === 'buyer'): ?>
        <div class="sidebar-panel-label text-center fw-bold ">BUYER PANEL</div>
    <?php endif; ?>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <?php if ($role === 'admin'): ?>
            <li class="nav-item text-uppercase fw-bold small text-primary mb-2" style="letter-spacing:1px; display:none;">Admin Panel</li>
            <li class="nav-item"><a href="index.php?page=admin_dashboard" class="nav-link <?php echo ($_GET['page'] ?? 'admin_dashboard') === 'admin_dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt me-2" style="color:#170e63;"></i>Dashboard</a></li>
            <li><a href="index.php?page=admin_users" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_users' ? 'active' : ''; ?>"><i class="fas fa-users me-2" style="color:#170e63;"></i>Users</a></li>
            <li><a href="index.php?page=admin_canteens" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_canteens' ? 'active' : ''; ?>"><i class="fas fa-school me-2" style="color:#170e63;"></i>Canteens</a></li>
            <li><a href="index.php?page=admin_stalls" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_stalls' ? 'active' : ''; ?>"><i class="fas fa-store-alt me-2" style="color:#170e63;"></i>Stalls</a></li>
            <li><a href="index.php?page=admin_products" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_products' ? 'active' : ''; ?>"><i class="fas fa-box-open me-2" style="color:#170e63;"></i>Product
        </a></li>
            <li><a href="index.php?page=admin_announcements" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_announcements' ? 'active' : ''; ?>"><i class="fas fa-bullhorn me-2" style="color:#170e63;"></i>Announcements</a></li>
            <li><a href="index.php?page=admin_account" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_account' ? 'active' : ''; ?>"><i class="fas fa-user-cog me-2" style="color:#170e63;"></i>My Account</a></li>
        <?php elseif ($role === 'seller'): ?>
            <li class="nav-item text-uppercase fw-bold small text-success mb-2" style="letter-spacing:1px; display:none; color: #198754 important!;">Seller Panel</li>
            <li class="nav-item"><a href="index.php?page=seller_dashboard" class="nav-link <?php echo ($_GET['page'] ?? 'seller_dashboard') === 'seller_dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt me-2" style="color:#198754;"></i>Dashboard</a></li>
            <li><a href="index.php?page=seller_products" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_products' ? 'active' : ''; ?>"><i class="fas fa-box-open me-2" style="color:#198754;"></i>Products</a></li>
            <li><a href="index.php?page=seller_categories" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_categories' ? 'active' : ''; ?>"><i class="fas fa-tags me-2" style="color:#198754;"></i>Categories</a></li>
            <li><a href="index.php?page=seller_orders" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_orders' ? 'active' : ''; ?>"><i class="fas fa-receipt me-2" style="color:#198754;"></i>Orders</a></li>
            <li><a href="index.php?page=seller_announcements" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_announcements' ? 'active' : ''; ?>"><i class="fas fa-bullhorn me-2" style="color:#198754;"></i>Announcements</a></li>
            <li><a href="index.php?page=seller_account" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_account' ? 'active' : ''; ?>"><i class="fas fa-user-cog me-2" style="color:#198754;"></i>My Account</a></li>
        <?php elseif ($role === 'buyer'): ?>
            <li class="nav-item text-uppercase fw-bold small text-info mb-2" style="letter-spacing:1px; display:none;">Buyer Panel</li>
            <li><a href="index.php?page=buyer_order" class="nav-link <?php echo ($_GET['page'] ?? '') === 'buyer_order' ? 'active' : ''; ?>"><i class="fas fa-cart-plus me-2" style="color:#0dcaf0;"></i>Place Order</a></li>
            <li><a href="index.php?page=buyer_orders" class="nav-link <?php echo ($_GET['page'] ?? '') === 'buyer_orders' ? 'active' : ''; ?>"><i class="fas fa-list-check me-2" style="color:#0dcaf0;"></i>My Orders</a></li>
            <li><a href="index.php?page=buyer_announcements" class="nav-link <?php echo ($_GET['page'] ?? '') === 'buyer_announcements' ? 'active' : ''; ?>"><i class="fas fa-bullhorn me-2" style="color:#0dcaf0;"></i>Announcements</a></li>
            <li><a href="index.php?page=buyer_account" class="nav-link <?php echo ($_GET['page'] ?? '') === 'buyer_account' ? 'active' : ''; ?>"><i class="fas fa-user me-2" style="color:#0dcaf0;"></i>My Account</a></li>
        <?php endif; ?>
    </ul>
</nav>