<?php
if (!isset($_SESSION['user_id'])) {
    header('location:../public/login.php');
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

// Orders per day for current month
$orders_per_day = [];
$days_in_month = date('t');
for ($d = 1; $d <= $days_in_month; $d++) {
    $orders_per_day[sprintf('%02d', $d)] = 0;
}
$stmt = $pdo->query("SELECT DAY(created_at) as day, COUNT(*) as count FROM orders WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE()) GROUP BY day");
foreach ($stmt->fetchAll() as $row) {
    $orders_per_day[sprintf('%02d', $row['day'])] = (int)$row['count'];
}

// Product distribution by stall
$prod_dist = [];
$stmt = $pdo->query("SELECT stalls.name, COUNT(products.id) as prod_count FROM stalls LEFT JOIN products ON stalls.id = products.stall_id GROUP BY stalls.id");
foreach ($stmt->fetchAll() as $row) {
    $prod_dist[$row['name']] = (int)$row['prod_count'];
}
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
  <div class="dashboard-header">
    <h1 class="dashboard-title">Welcome, <?= $user_name ?>! </h1>
  </div>
  
  <!-- Top Stats Row - 3 cards spanning full width -->
  <div class="row dashboard-cards mb-2">
    <div class="col-12 col-md-4 mb-2">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Total Products</div>
          <div class="count"><?= $total_products ?></div>
        </div>
        <div class="icon"><i class="fa fa-box"></i></div>
      </div>
    </div>
    <div class="col-12 col-md-4 mb-2">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Orders This Month</div>
          <div class="count"><?= $orders_this_month ?></div>
        </div>
        <div class="icon"><i class="fa fa-shopping-cart"></i></div>
      </div>
    </div>
    <div class="col-12 col-md-4 mb-2">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Revenue</div>
          <div class="count">₱<?= number_format($revenue,2) ?></div>
        </div>
        <div class="icon"><i class="fa fa-money-bill-wave"></i></div>
      </div>
    </div>
  </div>
  
  <!-- Recent Orders Section -->
  <div class="dashboard-section-title mt-3 mb-2">Recent Orders</div>
  <div class="dashboard-table mb-3">
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
        <?php if (!empty($recent_orders)): ?>
          <?php foreach ($recent_orders as $order): ?>
          <tr>
            <td><?= htmlspecialchars($order['orderRef']) ?></td>
            <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
            <td><?= $order_items_count[$order['orderRef']] ?? 0 ?></td>
            <td><span class="dashboard-badge <?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
            <td>₱<?= number_format($order['total_price'],2) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center text-muted">No recent orders</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- User Stats Row - 3 cards spanning full width -->
  <div class="row dashboard-cards mb-2">
    <div class="col-12 col-md-4 mb-2">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Total Users</div>
          <div class="count"><?= $total_admins + $total_sellers + $total_buyers ?></div>
        </div>
        <div class="icon"><i class="fa fa-users"></i></div>
      </div>
    </div>
    <div class="col-12 col-md-4 mb-2">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Total Buyers</div>
          <div class="count"><?= $total_buyers ?></div>
        </div>
        <div class="icon"><i class="fa fa-user-check"></i></div>
      </div>
    </div>
    <div class="col-12 col-md-4 mb-2">
      <div class="dashboard-card">
        <div class="card-info">
          <div class="label">Total Sellers</div>
          <div class="count"><?= $total_sellers ?></div>
        </div>
        <div class="icon"><i class="fa fa-store"></i></div>
      </div>
    </div>
  </div>

  <!-- Data Visualizations -->
  <div class="dashboard-section-title mt-3 mb-2">Data Visualizations</div>
  <div class="row mt-4 mb-4">
    <div class="col-md-6 mb-4">
      <div style="background:#fff; border-radius:1rem; padding:1.2rem; margin-bottom:1.2rem; border:1px solid #f0f0f0;">
        <div style="font-size:1rem; color:#888; font-weight:500; margin-bottom:0.7rem;">Orders Per Day (This Month)</div>
        <canvas id="ordersPerDayChart" height="180"></canvas>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div style="background:#fff; border-radius:1rem; padding:1.2rem; margin-bottom:1.2rem; border:1px solid #f0f0f0;">
        <div style="font-size:1rem; color:#888; font-weight:500; margin-bottom:0.7rem;">Product Distribution by Stall</div>
        <canvas id="prodDistChart" height="180"></canvas>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Orders per day data
    const ordersPerDayLabels = <?= json_encode(array_keys($orders_per_day)) ?>;
    const ordersPerDayData = <?= json_encode(array_values($orders_per_day)) ?>;
    new Chart(document.getElementById('ordersPerDayChart').getContext('2d'), {
      type: 'line',
      data: {
        labels: ordersPerDayLabels,
        datasets: [{
          label: 'Orders',
          data: ordersPerDayData,
          borderColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-gold').trim() || '#d6b243',
          backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--shadow-gold').trim() || 'rgba(214,178,67,0.15)',
          fill: true,
          tension: 0.3,
          pointBackgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-navy').trim() || '#170e63',
          pointBorderColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-gold').trim() || '#d6b243',
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, precision: 0 } }
      }
    });
    // Product distribution by stall data
    const prodDistLabels = <?= json_encode(array_keys($prod_dist)) ?>;
    const prodDistData = <?= json_encode(array_values($prod_dist)) ?>;
    // Use palette for doughnut chart
    const gold = getComputedStyle(document.documentElement).getPropertyValue('--primary-gold').trim() || '#d6b243';
    const navy = getComputedStyle(document.documentElement).getPropertyValue('--secondary-navy').trim() || '#170e63';
    const goldLight = getComputedStyle(document.documentElement).getPropertyValue('--gold-light').trim() || '#dfc055';
    const goldDark = getComputedStyle(document.documentElement).getPropertyValue('--gold-dark').trim() || '#b8983a';
    const navyLight = getComputedStyle(document.documentElement).getPropertyValue('--navy-light').trim() || '#2a1f7a';
    const navyDark = getComputedStyle(document.documentElement).getPropertyValue('--navy-dark').trim() || '#110a4e';
    const palette = [gold, navy, goldLight, goldDark, navyLight, navyDark, '#fffbe6', '#e6e6fa', '#f4f6fb', '#c4a139'];
    new Chart(document.getElementById('prodDistChart').getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: prodDistLabels,
        datasets: [{
          data: prodDistData,
          backgroundColor: palette.slice(0, prodDistLabels.length)
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
      }
    });
  </script>
</div>