<link rel="stylesheet" href="../assets/css/sidebar.css">
<nav class="sidebar flex-shrink-0 p-3 text-secondary">
    <?php $role = $_SESSION['user_role'] ?? ''; ?>
    <?php if ($role === 'admin'): ?>
        <div class="sidebar-panel-label sidebar-panel-label-admin text-center fw-bold">ADMIN PANEL</div>
    <?php elseif ($role === 'seller'): ?>
        <div class="sidebar-panel-label sidebar-panel-label-seller text-center fw-bold">SELLER PANEL</div>
    <?php elseif ($role === 'buyer'): ?>
        <div class="sidebar-panel-label sidebar-panel-label-buyer text-center fw-bold">BUYER PANEL</div>
    <?php endif; ?>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <?php if ($role === 'admin'): ?>
            <li class="nav-item text-uppercase fw-bold small text-primary mb-2" style="letter-spacing:1px; display:none;">Admin Panel</li>
            <li class="nav-item"><a href="index.php?page=admin_dashboard" class="nav-link <?php echo ($_GET['page'] ?? 'admin_dashboard') === 'admin_dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt me-2 sidebar-icon-admin"></i>Dashboard</a></li>
            <li><a href="index.php?page=admin_users" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_users' ? 'active' : ''; ?>"><i class="fas fa-users me-2 sidebar-icon-admin"></i>Users</a></li>
            <li><a href="index.php?page=admin_canteens" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_canteens' ? 'active' : ''; ?>"><i class="fas fa-school me-2 sidebar-icon-admin"></i>Canteens</a></li>
            <li><a href="index.php?page=admin_orders" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_orders' ? 'active' : ''; ?>"><i class="fas fa-receipt me-2 sidebar-icon-admin"></i>Orders</a></li>
            <li><a href="index.php?page=admin_stalls" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_stalls' ? 'active' : ''; ?>"><i class="fas fa-store-alt me-2 sidebar-icon-admin"></i>Stalls</a></li>
            <li><a href="index.php?page=admin_products" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_products' ? 'active' : ''; ?>"><i class="fas fa-box-open me-2 sidebar-icon-admin"></i>Product</a></li>
            <li><a href="index.php?page=admin_categories" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_categories' ? 'active' : ''; ?>"><i class="fas fa-tags me-2 sidebar-icon-admin"></i>Categories</a></li>
            <li><a href="index.php?page=admin_announcements" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_announcements' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn me-2 sidebar-icon-admin"></i>Announcements
            </a></li>
            <li><a href="index.php?page=admin_account" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_account' ? 'active' : ''; ?>"><i class="fas fa-user-cog me-2 sidebar-icon-admin"></i>My Account</a></li>
            <li><a href="index.php?page=admin_backup" class="nav-link <?php echo ($_GET['page'] ?? '') === 'admin_backup' ? 'active' : ''; ?>">
                <i class="fas fa-database me-2 sidebar-icon-admin"></i>Backup Database
            </a></li>
        <?php elseif ($role === 'seller'): ?>
            <li class="nav-item text-uppercase fw-bold small text-success mb-2" style="letter-spacing:1px; display:none;">Seller Panel</li>
            <li class="nav-item"><a href="index.php?page=seller_dashboard" class="nav-link <?php echo ($_GET['page'] ?? 'seller_dashboard') === 'seller_dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt me-2 sidebar-icon-seller"></i>Dashboard</a></li>
            <li><a href="index.php?page=seller_products" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_products' ? 'active' : ''; ?>"><i class="fas fa-box-open me-2 sidebar-icon-seller"></i>Products</a></li>
            <li><a href="index.php?page=seller_categories" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_categories' ? 'active' : ''; ?>"><i class="fas fa-tags me-2 sidebar-icon-seller"></i>Categories</a></li>
            <li><a href="index.php?page=seller_orders" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_orders' ? 'active' : ''; ?>">
                <i class="fas fa-receipt me-2 sidebar-icon-seller"></i>Orders
                <?php
                require_once __DIR__ . '/../includes/db.php';
                $user_id = $_SESSION['user_id'] ?? null;
                $order_count = 0;
                if ($user_id) {
                    $seller_id = $user_id;
                    $unseen_orders = $pdo->prepare('SELECT COUNT(DISTINCT o.orderRef) FROM orders o JOIN order_items oi ON o.orderRef = oi.order_id JOIN products p ON oi.product_id = p.id JOIN stalls s ON p.stall_id = s.id WHERE s.seller_id = ? AND o.seen_by_seller = 0 AND o.status = "queue"');
                    $unseen_orders->execute([$seller_id]);
                    $order_count = $unseen_orders->fetchColumn();
                }
                if ($order_count > 0): ?>
                    <span class="badge bg-danger ms-2"><?= $order_count ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="index.php?page=seller_announcements" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_announcements' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn me-2 sidebar-icon-seller"></i>Announcements
                <?php
                require_once __DIR__ . '/../includes/db.php';
                $user_id = $_SESSION['user_id'] ?? null;
                $announcement_count = 0;
                if ($user_id) {
                    $unseen_announcements = $pdo->prepare('SELECT COUNT(*) FROM announcements a WHERE NOT EXISTS (SELECT 1 FROM announcement_reads r WHERE r.announcement_id = a.id AND r.user_id = ?)');
                    $unseen_announcements->execute([$user_id]);
                    $announcement_count = $unseen_announcements->fetchColumn();
                }
                if ($announcement_count > 0): ?>
                    <span class="badge bg-danger ms-2"><?= $announcement_count ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="index.php?page=seller_account" class="nav-link <?php echo ($_GET['page'] ?? '') === 'seller_account' ? 'active' : ''; ?>"><i class="fas fa-user-cog me-2 sidebar-icon-seller"></i>My Account</a></li>
        <?php elseif ($role === 'buyer'): ?>
            <li class="nav-item text-uppercase fw-bold small text-info mb-2" style="letter-spacing:1px; display:none;">Buyer Panel</li>
            <li><a href="index.php?page=buyer_order" class="nav-link <?php echo ($_GET['page'] ?? '') === 'buyer_order' ? 'active' : ''; ?>"><i class="fas fa-cart-plus me-2 sidebar-icon-buyer"></i>Place Order</a></li>
            <li><a href="index.php?page=buyer_orders" class="nav-link <?php echo ($_GET['page'] ?? '') === 'buyer_orders' ? 'active' : ''; ?>"><i class="fas fa-list-check me-2 sidebar-icon-buyer"></i>My Orders</a></li>
            <li><a href="index.php?page=buyer_announcements" class="nav-link <?php echo ($_GET['page'] ?? '') === 'buyer_announcements' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn me-2 sidebar-icon-buyer"></i>Announcements
                <?php
                require_once __DIR__ . '/../includes/db.php';
                $user_id = $_SESSION['user_id'] ?? null;
                $announcement_count = 0;
                if ($user_id) {
                    $unseen_announcements = $pdo->prepare('SELECT COUNT(*) FROM announcements a WHERE NOT EXISTS (SELECT 1 FROM announcement_reads r WHERE r.announcement_id = a.id AND r.user_id = ?)');
                    $unseen_announcements->execute([$user_id]);
                    $announcement_count = $unseen_announcements->fetchColumn();
                }
                if ($announcement_count > 0): ?>
                    <span class="badge bg-danger ms-2"><?= $announcement_count ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="index.php?page=buyer_account" class="nav-link <?php echo ($_GET['page'] ?? '') === 'buyer_account' ? 'active' : ''; ?>"><i class="fas fa-user me-2 sidebar-icon-buyer"></i>My Account</a></li>
        <?php endif; ?>
    </ul>
</nav>