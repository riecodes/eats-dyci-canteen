<?php
if (!isset($_SESSION['user_id'])) {
    header('location:../index.php');
    exit;
}
require_once __DIR__ . '/../includes/db.php';
$user_name = htmlspecialchars($_SESSION['user_name'] ?? '');
$user_role = htmlspecialchars($_SESSION['user_role'] ?? '');

// Get counts
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$total_sellers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn();
$total_buyers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'")->fetchColumn();
$total_stalls = $pdo->query("SELECT COUNT(*) FROM stalls")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Orders this month
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())");
$orders_this_month = $stmt->fetchColumn();

// Revenue this month (done/processing orders)
$stmt = $pdo->query("SELECT SUM(total_price) FROM orders WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE()) AND status IN ('done','processing')");
$revenue = $stmt->fetchColumn();
if ($revenue === null) $revenue = 0;

// Recent orders (last 5)
$stmt = $pdo->query("SELECT orderRef, created_at, status, total_price FROM orders ORDER BY created_at DESC LIMIT 5");
$recent_orders = $stmt->fetchAll();

// Get item counts for recent orders
$order_items_count = [];
if ($recent_orders) {
    $order_refs = array_map(function($o){return $o['orderRef'];}, $recent_orders);
    $in = str_repeat('?,', count($order_refs)-1) . '?';
    $stmt = $pdo->prepare("SELECT order_id, SUM(quantity) as item_count FROM order_items WHERE order_id IN ($in) GROUP BY order_id");
    $stmt->execute($order_refs);
    foreach ($stmt->fetchAll() as $row) {
        $order_items_count[$row['order_id']] = $row['item_count'];
    }
}
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4">
  <div class="dashboard-header">
    <h1 class="dashboard-title">Dashboard</h1>
    <div class="role-badge">Welcome, <?= $user_name ?> (<?= ucfirst($user_role) ?>)</div>
  </div>
  <div class="row dashboard-cards">
    <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Total Products</div>
          <div class="count"><?= $total_products ?></div>
        </div>
        <div class="icon"><i class="fa fa-box"></i></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Orders This Month</div>
          <div class="count"><?= $orders_this_month ?></div>
        </div>
        <div class="icon"><i class="fa fa-shopping-cart"></i></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Revenue</div>
          <div class="count">₱<?= number_format($revenue,2) ?></div>
        </div>
        <div class="icon"><i class="fa fa-money-bill-wave"></i></div>
      </div>
    </div>
  </div>
  <div class="dashboard-section-title">Recent Orders</div>
  <div class="dashboard-table mb-4">
    <table class="table mb-0">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Date</th>
          <th>Items</th>
          <th>Status</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent_orders as $order): ?>
        <tr>
          <td><?= htmlspecialchars($order['orderRef']) ?></td>
          <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
          <td><?= $order_items_count[$order['orderRef']] ?? 0 ?></td>
          <td><span class="dashboard-badge <?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
          <td>₱<?= number_format($order['total_price'],2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="row dashboard-cards">
    <div class="col-12 col-sm-6 col-md-4 col-lg-4 mb-3">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Total Users</div>
          <div class="count"><?= $total_admins + $total_sellers + $total_buyers ?></div>
        </div>
        <div class="icon"><i class="fa fa-users"></i></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-lg-4 mb-3">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Total Buyers</div>
          <div class="count"><?= $total_buyers ?></div>
        </div>
        <div class="icon"><i class="fa fa-user-check"></i></div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-lg-4 mb-3">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Total Sellers</div>
          <div class="count"><?= $total_sellers ?></div>
        </div>
        <div class="icon"><i class="fa fa-store"></i></div>
      </div>
    </div>
  </div>

  <div class="dashboard-section-title">Sales Overview</div>
  <div class="dashboard-table">
    <div class="p-4 text-center text-muted">(Sales overview chart or summary coming soon...)</div>
  </div>
</div>