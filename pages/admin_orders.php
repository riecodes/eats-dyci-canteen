<?php
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}
require_once __DIR__ . '/../includes/db.php';

// Handle auto-void toggle and time
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_auto_void']) || isset($_POST['save_order_cutoff']))) {
    $enabled = isset($_POST['auto_void_enabled']) ? '1' : '0';
    $void_time = isset($_POST['auto_void_time']) ? $_POST['auto_void_time'] : '15:00:00';
    $cutoff_enabled = isset($_POST['order_cutoff_enabled']) ? '1' : '0';
    $cutoff_time = isset($_POST['order_cutoff_time']) ? $_POST['order_cutoff_time'] : '14:45:00';
    $stmt = $pdo->prepare("UPDATE settings SET value=? WHERE name='auto_void_enabled'");
    $stmt->execute([$enabled]);
    // Save or insert auto_void_time
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE name='auto_void_time'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('auto_void_time', ?)");
        $stmt->execute([$void_time]);
    } else {
        $stmt = $pdo->prepare("UPDATE settings SET value=? WHERE name='auto_void_time'");
        $stmt->execute([$void_time]);
    }
    // Save or insert order_cutoff_enabled
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE name='order_cutoff_enabled'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('order_cutoff_enabled', ?)");
        $stmt->execute([$cutoff_enabled]);
    } else {
        $stmt = $pdo->prepare("UPDATE settings SET value=? WHERE name='order_cutoff_enabled'");
        $stmt->execute([$cutoff_enabled]);
    }
    // Save or insert order_cutoff_time
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE name='order_cutoff_time'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('order_cutoff_time', ?)");
        $stmt->execute([$cutoff_time]);
    } else {
        $stmt = $pdo->prepare("UPDATE settings SET value=? WHERE name='order_cutoff_time'");
        $stmt->execute([$cutoff_time]);
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
// Fetch auto-void setting
$stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'auto_void_enabled'");
$stmt->execute();
$auto_void_enabled = $stmt->fetchColumn();
if ($auto_void_enabled === false) {
    // If not set, default to enabled and insert
    $auto_void_enabled = '1';
    $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('auto_void_enabled', '1')");
    $stmt->execute();
}
// Fetch auto-void time setting
$stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'auto_void_time'");
$stmt->execute();
$auto_void_time = $stmt->fetchColumn();
if ($auto_void_time === false) {
    $auto_void_time = '15:00:00';
    $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('auto_void_time', '15:00:00')");
    $stmt->execute();
}
// Fetch order cutoff enabled setting
$stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'order_cutoff_enabled'");
$stmt->execute();
$order_cutoff_enabled = $stmt->fetchColumn();
if ($order_cutoff_enabled === false) {
    $order_cutoff_enabled = '1';
    $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('order_cutoff_enabled', '1')");
    $stmt->execute();
}
// Fetch order cutoff time setting
$stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'order_cutoff_time'");
$stmt->execute();
$order_cutoff_time = $stmt->fetchColumn();
if ($order_cutoff_time === false) {
    $order_cutoff_time = '14:45:00';
    $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('order_cutoff_time', '14:45:00')");
    $stmt->execute();
}

// Get all orders (admin can see all)
$order_stmt = $pdo->prepare("SELECT * FROM orders ORDER BY created_at DESC");
$order_stmt->execute();
$orders = $order_stmt->fetchAll();

// Get all order items for these orders
$order_refs = array_column($orders, 'orderRef');
$order_items = [];
if ($order_refs) {
    $in = str_repeat('?,', count($order_refs) - 1) . '?';
    $stmt = $pdo->prepare("SELECT oi.*, p.name AS product_name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id IN ($in)");
    $stmt->execute($order_refs);
    foreach ($stmt->fetchAll() as $row) {
        $order_items[$row['order_id']][] = $row;
    }
}
// Get buyer info
$buyers = [];
if ($orders) {
    $buyer_ids = array_unique(array_column($orders, 'user_id'));
    if ($buyer_ids) {
        $in = str_repeat('?,', count($buyer_ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id, name, email, department, position, faculty FROM users WHERE id IN ($in)");
        $stmt->execute($buyer_ids);
        foreach ($stmt->fetchAll() as $row) {
            $buyers[$row['id']] = $row;
        }
    }
}

// Status map for display
$status_map = [
    'queue' => 'Queue',
    'processing' => 'Processing',
    'processed' => 'Processed',
    'done' => 'Done',
    'void' => 'Void',
];

// Auto-void orders not picked up by 3PM same day
if ($auto_void_enabled == '1') {
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    foreach ($orders as &$order) {
        $order_time = new DateTime($order['created_at'], new DateTimeZone('Asia/Manila'));
        list($h, $m) = explode(':', $auto_void_time);
        $void_time = (clone $order_time)->setTime((int)$h, (int)$m, 0);
        // If order placed after auto-void time, void immediately
        if ($order_time->format('H:i:s') > $auto_void_time && !in_array($order['status'], ['done', 'void'])) {
            $stmt = $pdo->prepare("UPDATE orders SET status='void' WHERE orderRef=?");
            $stmt->execute([$order['orderRef']]);
            $order['status'] = 'void';
        }
        // If not done/void, check if should be voided (current time after auto-void time)
        elseif (!in_array($order['status'], ['done', 'void'])) {
            if ($now > $void_time) {
                $stmt = $pdo->prepare("UPDATE orders SET status='void' WHERE orderRef=?");
                $stmt->execute([$order['orderRef']]);
                $order['status'] = 'void';
            }
        }
        // Store void_time for JS
        $order['void_time'] = $void_time->format(DateTime::ATOM);
    }
    unset($order);
}
// Sort orders oldest first
usort($orders, function ($a, $b) {
    return strtotime($a['created_at']) <=> strtotime($b['created_at']); });

// Handle edit and delete actions
$status_success = $status_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_order_ref'], $_POST['edit_status'])) {
    $edit_order_ref = $_POST['edit_order_ref'];
    $edit_status = $_POST['edit_status'];
    $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE orderRef=?");
    if ($stmt->execute([$edit_status, $edit_order_ref])) {
        $status_success = 'Order updated.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $status_error = 'Failed to update order.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_ref'])) {
    $delete_order_ref = $_POST['delete_order_ref'];
    $stmt = $pdo->prepare("DELETE FROM orders WHERE orderRef=?");
    if ($stmt->execute([$delete_order_ref])) {
        $status_success = 'Order deleted.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $status_error = 'Failed to delete order.';
    }
}

// Calculate total sales (including voided orders)
$total_sales = 0;
foreach ($orders as $order) {
    $total_sales += $order['total_price'];
}
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
    <form method="post" action="" class="mb-3">
        <label class="form-check-label">
            <input type="checkbox" class="form-check-input" name="auto_void_enabled" value="1" <?= $auto_void_enabled == '1' ? 'checked' : '' ?>>
            Enable auto-void at
        </label>
        <input type="time" name="auto_void_time" value="<?= htmlspecialchars(substr($auto_void_time,0,5)) ?>" class="form-control d-inline-block" style="width:135px; margin-left:8px; margin-right:8px; vertical-align:middle;" required>
        <button type="submit" name="save_auto_void" class="btn btn-primary btn-sm ms-2">Save</button>
    </form>
    <form method="post" action="" class="mb-3">
        <label class="form-check-label">
            <input type="checkbox" class="form-check-input" name="order_cutoff_enabled" value="1" <?= $order_cutoff_enabled == '1' ? 'checked' : '' ?>>
            Order cannot be placed after
        </label>
        <input type="time" name="order_cutoff_time" value="<?= htmlspecialchars(substr($order_cutoff_time,0,5)) ?>" class="form-control d-inline-block" style="width:135px; margin-left:8px; margin-right:8px; vertical-align:middle;" required>
        <button type="submit" name="save_order_cutoff" class="btn btn-primary btn-sm ms-2">Save</button>
    </form>
    <div class="dashboard-section-title mb-3">All Orders</div>    
    <?php if ($status_success): ?><div class="alert alert-success mb-2"><?= $status_success ?></div><?php endif; ?>
    <?php if ($status_error): ?><div class="alert alert-danger mb-2"><?= $status_error ?></div><?php endif; ?>
    <div class="dashboard-table mb-4 table-responsive">
        <table class="table mb-0" style="min-width:1100px;">
            <thead class="table-light">
                <tr>
                    <th>Order Ref</th>
                    <th>Date</th>
                    <th>Buyer</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Receipt</th>
                    <th>Voiding</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td style="max-width:120px; background:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; border-radius:0.5rem; padding:0.5rem 1rem;">
                            <?= htmlspecialchars($order['orderRef']) ?>
                        </td>
                        <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                        <td><?= isset($buyers[$order['user_id']]) ? htmlspecialchars($buyers[$order['user_id']]['name']) : 'N/A' ?></td>
                        <td>
                            <?php if (!empty($order_items[$order['orderRef']])): ?>
                                <span style="font-size:0.97em;">
                                <?php
                                $item_strs = [];
                                foreach ($order_items[$order['orderRef']] as $item) {
                                    $item_strs[] = htmlspecialchars($item['product_name']) . ' (' . $item['quantity'] . 'pcs)';
                                }
                                echo implode(', ', $item_strs);
                                ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><span class="dashboard-badge <?= htmlspecialchars($order['status']) ?>">
                            <?= $status_map[$order['status']] ?? htmlspecialchars(ucfirst($order['status'])) ?>
                        </span></td>
                        <td>₱<?= number_format($order['total_price'], 2) ?></td>
                        <td>
                            <?php if ($order['receipt_image']): ?>
                                <img src="<?= $order['receipt_image'] ?>" alt="Receipt"
                                    style="max-width:80px;max-height:80px;object-fit:cover;cursor:pointer;"
                                    data-bs-toggle="modal" data-bs-target="#receiptModal<?= $order['orderRef'] ?>">
                                <div class="modal fade" id="receiptModal<?= $order['orderRef'] ?>" tabindex="-1"
                                    aria-labelledby="receiptModalLabel<?= $order['orderRef'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content bg-transparent border-0">
                                            <div class="modal-body text-center p-0">
                                                <img src="<?= $order['receipt_image'] ?>" alt="Receipt"
                                                    style="max-width:90vw;max-height:90vh;object-fit:contain;box-shadow:0 0 24px #0008;border-radius:1rem;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">No receipt</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($auto_void_enabled == '1' && $order['status'] === 'processed'): ?>
                                <span class="void-timer" data-void-time="<?= htmlspecialchars($order['void_time']) ?>"></span>
                                <noscript>
                                <?php
                                $order_time = new DateTime($order['created_at'], new DateTimeZone('Asia/Manila'));
                                list($h, $m) = explode(':', $auto_void_time);
                                $void_time = (clone $order_time)->setTime((int)$h, (int)$m, 0);
                                $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
                                $diff_sec = $void_time->getTimestamp() - $now->getTimestamp();
                                if ($diff_sec > 0) {
                                    $h = floor($diff_sec / 3600);
                                    $m = floor(($diff_sec % 3600) / 60);
                                    $s = $diff_sec % 60;
                                    echo '<span class="badge bg-warning text-dark">Will be voided in ' . sprintf('%02d:%02d:%02d', $h, $m, $s) . '</span>';
                                } else {
                                    echo '<span class="badge bg-danger">Voiding...</span>';
                                }
                                ?>
                                </noscript>
                            <?php elseif ($order['status'] === 'void'): ?>
                                <span class="badge bg-danger">Voided (auto)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                            data-bs-target="#viewOrderModal<?= $order['orderRef'] ?>">View</a></li>
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                            data-bs-target="#editOrderModal<?= $order['orderRef'] ?>">Edit</a></li>
                                    <li>
                                        <form method="post" onsubmit="return confirm('Delete this order?')"><input
                                                type="hidden" name="delete_order_ref"
                                                value="<?= htmlspecialchars($order['orderRef']) ?>"><button type="submit"
                                                class="dropdown-item text-danger">Delete</button></form>
                                    </li>
                                </ul>
                            </div>
                            <!-- View Modal -->
                            <div class="modal fade" id="viewOrderModal<?= $order['orderRef'] ?>" tabindex="-1"
                                aria-labelledby="viewOrderModalLabel<?= $order['orderRef'] ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content" style="background:#fff; border-radius:1.2rem; padding:2rem 2.5rem;">
                                        <div class="modal-header" style="border-bottom:0; padding-bottom:0.5rem;">
                                            <h5 class="modal-title" id="viewOrderModalLabel<?= $order['orderRef'] ?>">Order Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body" style="padding:2rem 1.5rem 1.5rem 1.5rem;">
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <h6 class="fw-bold mb-2">Order Info</h6>
                                                    <div><strong>Order Ref:</strong>
                                                        <?= htmlspecialchars($order['orderRef']) ?></div>
                                                    <div><strong>Date:</strong>
                                                        <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></div>
                                                    <div><strong>Status:</strong> <span
                                                            class="dashboard-badge <?= htmlspecialchars($order['status']) ?>">
                                                            <?= $status_map[$order['status']] ?? htmlspecialchars($order['status']) ?></span>
                                                    </div>
                                                    <div><strong>Total:</strong>
                                                        ₱<?= number_format($order['total_price'], 2) ?></div>
                                            
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <h6 class="fw-bold mb-2">Buyer Info</h6>
                                                    <div><strong>Name:</strong>
                                                        <?= isset($buyers[$order['user_id']]) ? htmlspecialchars($buyers[$order['user_id']]['name']) : 'N/A' ?>
                                                    </div>
                                                    <div><strong>Email:</strong>
                                                        <?= isset($buyers[$order['user_id']]) ? htmlspecialchars($buyers[$order['user_id']]['email']) : '' ?>
                                                    </div>
                                                    <div><strong>Department:</strong>
                                                        <?= isset($buyers[$order['user_id']]) ? ($buyers[$order['user_id']]['department'] ?? '-') : '-' ?>
                                                    </div>
                                                    <div><strong>Position:</strong>
                                                        <?= isset($buyers[$order['user_id']]) ? htmlspecialchars($buyers[$order['user_id']]['position'] ?? '-') : '-' ?>
                                                    </div>
                                                    <div><strong>Faculty:</strong>
                                                        <?= isset($buyers[$order['user_id']]) ? htmlspecialchars($buyers[$order['user_id']]['faculty'] ?? '-') : '-' ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <h6 class="fw-bold mb-2">Items</h6>
                                            <div style="font-size:1.05em;">
                                            <?php
                                            $item_strs = [];
                                            foreach ($order_items[$order['orderRef']] as $item) {
                                                $item_strs[] = htmlspecialchars($item['product_name']) . ' (' . $item['quantity'] . 'pcs)';
                                            }
                                            echo implode(', ', $item_strs);
                                            ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Edit Modal (simple, for status only) -->
                            <div class="modal fade" id="editOrderModal<?= $order['orderRef'] ?>" tabindex="-1"
                                aria-labelledby="editOrderModalLabel<?= $order['orderRef'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editOrderModalLabel<?= $order['orderRef'] ?>">Edit
                                                Order</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="post">
                                                <input type="hidden" name="edit_order_ref"
                                                    value="<?= htmlspecialchars($order['orderRef']) ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select class="form-select" name="edit_status">
                                                        <option value="queue" <?= $order['status'] === 'queue' ? 'selected' : '' ?>>Queue</option>
                                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                        <option value="processed" <?= $order['status'] === 'processed' ? 'selected' : '' ?>>Processed</option>
                                                        <option value="done" <?= $order['status'] === 'done' ? 'selected' : '' ?>>Done</option>
                                                        <option value="void" <?= $order['status'] === 'void' ? 'selected' : '' ?>>Void</option>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  function updateTimers() {
    document.querySelectorAll('.void-timer').forEach(function(span) {
      var voidTime = new Date(span.getAttribute('data-void-time'));
      var now = new Date();
      var diff = Math.floor((voidTime - now) / 1000);
      if (diff > 0) {
        var h = Math.floor(diff / 3600);
        var m = Math.floor((diff % 3600) / 60);
        var s = diff % 60;
        span.textContent = 'Will be voided in ' + String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        span.className = 'void-timer badge bg-warning text-dark';
      } else {
        span.textContent = 'Voiding...';
        span.className = 'void-timer badge bg-danger';
      }
    });
  }
  setInterval(updateTimers, 1000);
  updateTimers();
});
</script>
<!-- Voiding time calculation code (PHP):
$order_time = new DateTime($order['created_at'], new DateTimeZone('Asia/Manila'));
$void_time = (clone $order_time)->setTime(15, 0, 0); // 3PM same day
$now = new DateTime('now', new DateTimeZone('Asia/Manila'));
$diff_sec = $void_time->getTimestamp() - $now->getTimestamp();
--> 