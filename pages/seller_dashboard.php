<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/config.php';
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'seller') {
    header('location:../public/login.php');
    exit();
}
$seller_id = $_SESSION['user_id'];
// Get all stalls owned by this seller
$stall_stmt = $pdo->prepare("SELECT id, name FROM stalls WHERE seller_id = ?");
$stall_stmt->execute([$seller_id]);
$stalls = $stall_stmt->fetchAll();
$stall_ids = array_column($stalls, 'id');
if (empty($stall_ids)) {
    echo '<div class="alert alert-warning">You do not own any stalls. Please contact admin.</div>';
    return;
}
// Total products
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE stall_id IN (" . implode(',', $stall_ids) . ")");
$stmt->execute();
$total_products = $stmt->fetchColumn();
// Total orders
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT order_id) FROM order_items WHERE product_id IN (SELECT id FROM products WHERE stall_id IN (" . implode(',', $stall_ids) . "))");
$stmt->execute();
$total_orders = $stmt->fetchColumn();
// Total sales (sum of order_items * price) for completed orders only
$stmt = $pdo->prepare("SELECT SUM(oi.quantity * p.price) FROM order_items oi JOIN products p ON oi.product_id=p.id JOIN orders o ON oi.order_id=o.orderRef WHERE p.stall_id IN (" . implode(',', $stall_ids) . ") AND o.status IN ('done','completed')");
$stmt->execute();
$total_sales = $stmt->fetchColumn();
if ($total_sales === null) $total_sales = 0;
// Recent orders (last 5)
$stmt = $pdo->prepare("SELECT o.orderRef, o.created_at, o.status, o.total_price FROM orders o WHERE o.orderRef IN (
    SELECT DISTINCT order_id FROM order_items WHERE product_id IN (
        SELECT id FROM products WHERE stall_id IN (" . implode(',', $stall_ids) . ")
    )
) ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute();
$recent_orders = $stmt->fetchAll();
// Add status map for badge display
$status_map = [
    'queue' => 'Queue',
    'processing' => 'Processing',
    'processed' => 'Processed',
    'done' => 'Complete',
    'cancelled' => 'Cancelled',
    'void' => 'Void',
    'pending' => 'Pending',
];
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
  <div class="dashboard-header">
    <h1 class="dashboard-title">Welcome, <?= htmlspecialchars($_SESSION['user_name'])?>! </h1>    
  </div>
  <div class="row dashboard-cards">
    <div class="col-12 col-md-4 mb-3">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Total Products</div>
          <div class="count"><?= $total_products ?></div>
        </div>
        <div class="icon"><i class="fa fa-box"></i></div>
      </div>
    </div>
    <div class="col-12 col-md-4 mb-3">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Total Orders</div>
          <div class="count"><?= $total_orders ?></div>
        </div>
        <div class="icon"><i class="fa fa-shopping-cart"></i></div>
      </div>
    </div>
    <div class="col-12 col-md-4 mb-3">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Total Sales</div>
          <div class="count">₱<?= number_format($total_sales,2) ?></div>
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
          <th>Status</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($recent_orders)): ?>
          <?php foreach ($recent_orders as $order): ?>
          <tr>
            <td><?= htmlspecialchars($order['orderRef']) ?></td>
            <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
            <td><span class="dashboard-badge <?= htmlspecialchars($order['status']) ?>"><?=
                $status_map[$order['status']] ?? htmlspecialchars($order['status'])
            ?></span></td>
            <td>₱<?= number_format($order['total_price'],2) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="4" class="text-center text-muted">No recent orders</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div> 