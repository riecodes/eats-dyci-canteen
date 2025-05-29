<?php
<<<<<<< HEAD
=======
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/upload.php';
>>>>>>> master
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'seller') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}
<<<<<<< HEAD
require_once __DIR__ . '/../includes/db.php';

$seller_id = $_SESSION['user_id'];
=======
$seller_id = $_SESSION['user_id'];

>>>>>>> master
// Get all stalls owned by this seller
$stall_stmt = $pdo->prepare("SELECT id, name FROM stalls WHERE owner_id = ?");
$stall_stmt->execute([$seller_id]);
$stalls = $stall_stmt->fetchAll();
$stall_ids = array_column($stalls, 'id');
<<<<<<< HEAD

=======
>>>>>>> master
if (empty($stall_ids)) {
    echo '<div class="alert alert-warning">You do not own any stalls. Please contact admin.</div>';
    return;
}

// --- QR Code Upload/Display ---
$qr_success = $qr_error = '';
// Assume one QR code per seller, store path in a new table or in users table (for now, use users.qr_code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_qr'])) {
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
<<<<<<< HEAD
        $ext = pathinfo($_FILES['qr_code']['name'], PATHINFO_EXTENSION);
        $target = '../assets/imgs/qr_seller_' . $seller_id . '.' . $ext;
        if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $target)) {
=======
        list($ok, $result) = secure_image_upload($_FILES['qr_code']);
        if ($ok) {
            $target = $result;
>>>>>>> master
            // Save path to users table
            $stmt = $pdo->prepare("UPDATE users SET qr_code=? WHERE id=?");
            if ($stmt->execute([$target, $seller_id])) {
                $qr_success = 'QR code uploaded.';
            } else {
                $qr_error = 'Failed to save QR code.';
            }
        } else {
<<<<<<< HEAD
            $qr_error = 'Failed to upload QR code.';
=======
            $qr_error = $result;
>>>>>>> master
        }
    } else {
        $qr_error = 'No file uploaded.';
    }
}
// Get seller QR code
$stmt = $pdo->prepare("SELECT qr_code FROM users WHERE id=?");
$stmt->execute([$seller_id]);
$seller_qr = $stmt->fetchColumn();
if (!$seller_qr || !file_exists($seller_qr)) {
    $seller_qr = '../assets/imgs/qrcode-placeholder.jpg';
}

// Handle status update/approval/decline
$status_success = $status_error = '';
<<<<<<< HEAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_ref'], $_POST['action']) && !isset($_POST['upload_qr'])) {
=======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_ref'], $_POST['action'])) {
>>>>>>> master
    $order_ref = $_POST['order_ref'];
    $action = $_POST['action'];
    if ($action === 'processing') {
        $stmt = $pdo->prepare("UPDATE orders SET status='processing' WHERE orderRef=?");
        if ($stmt->execute([$order_ref])) $status_success = 'Order marked as processing.';
        else $status_error = 'Failed to update order.';
    } elseif ($action === 'done') {
        $stmt = $pdo->prepare("UPDATE orders SET status='done' WHERE orderRef=?");
        if ($stmt->execute([$order_ref])) $status_success = 'Order marked as done.';
        else $status_error = 'Failed to update order.';
    } elseif ($action === 'cancelled') {
        $stmt = $pdo->prepare("UPDATE orders SET status='cancelled' WHERE orderRef=?");
        if ($stmt->execute([$order_ref])) $status_success = 'Order cancelled.';
        else $status_error = 'Failed to update order.';
    }
}

// Get all orders for the seller's stalls
$order_stmt = $pdo->prepare("SELECT * FROM orders WHERE orderRef IN (
    SELECT DISTINCT order_id FROM order_items WHERE food_id IN (
        SELECT id FROM foods WHERE stall_id IN (" . implode(',', $stall_ids) . ")
    )
) ORDER BY created_at DESC");
$order_stmt->execute();
$orders = $order_stmt->fetchAll();

// Get all order items for these orders
$order_refs = array_column($orders, 'orderRef');
$order_items = [];
if ($order_refs) {
    $in = str_repeat('?,', count($order_refs)-1) . '?';
    $stmt = $pdo->prepare("SELECT oi.*, f.name AS food_name FROM order_items oi JOIN foods f ON oi.food_id=f.id WHERE oi.order_id IN ($in)");
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
        $in = str_repeat('?,', count($buyer_ids)-1) . '?';
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id IN ($in)");
        $stmt->execute($buyer_ids);
        foreach ($stmt->fetchAll() as $row) {
            $buyers[$row['id']] = $row;
        }
    }
}
?>
<div class="container-fluid">
    <h2>Order Management</h2>
    <div class="mb-4">
        <h5>My Payment QR Code</h5>
        <img src="<?= $seller_qr ?>" alt="Seller QR Code" style="max-width:200px;max-height:200px;object-fit:contain;border:1px solid #ccc;background:#fff;">
        <form method="post" enctype="multipart/form-data" class="mt-2">
            <input type="file" name="qr_code" accept="image/*" required>
            <button type="submit" name="upload_qr" class="btn btn-primary btn-sm">Upload/Change QR Code</button>
        </form>
        <?php if ($qr_success): ?><div class="alert alert-success mt-2"><?= $qr_success ?></div><?php endif; ?>
        <?php if ($qr_error): ?><div class="alert alert-danger mt-2"><?= $qr_error ?></div><?php endif; ?>
    </div>
    <?php if ($status_success): ?><div class="alert alert-success"><?= $status_success ?></div><?php endif; ?>
    <?php if ($status_error): ?><div class="alert alert-danger"><?= $status_error ?></div><?php endif; ?>
<<<<<<< HEAD
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>Order Ref</th>
                <th>Date</th>
                <th>Buyer</th>
                <th>Items</th>
                <th>Status</th>
                <th>Total</th>
                <th>Receipt</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= htmlspecialchars($order['orderRef']) ?></td>
                <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                <td><?= isset($buyers[$order['user_id']]) ? htmlspecialchars($buyers[$order['user_id']]['name']) : 'N/A' ?></td>
                <td>
                    <?php if (!empty($order_items[$order['orderRef']])): ?>
                        <ul class="mb-0">
                        <?php foreach ($order_items[$order['orderRef']] as $item): ?>
                            <li><?= htmlspecialchars($item['food_name']) ?> x <?= $item['quantity'] ?></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </td>
                <td><span class="dashboard-badge <?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
                <td>₱<?= number_format($order['total_price'],2) ?></td>
                <td>
                    <?php if ($order['receipt_image']): ?>
                        <img src="<?= $order['receipt_image'] ?>" alt="Receipt" style="max-width:80px;max-height:80px;object-fit:cover;">
                        <?php if ($order['status'] === 'queue' || $order['status'] === 'pending'): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="order_ref" value="<?= htmlspecialchars($order['orderRef']) ?>">
                                <button type="submit" name="action" value="processing" class="btn btn-sm btn-success">Approve Payment</button>
                                <button type="submit" name="action" value="cancelled" class="btn btn-sm btn-danger" onclick="return confirm('Decline and cancel this order?')">Decline</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">No receipt</span>
                        <?php if ($order['status'] === 'queue' || $order['status'] === 'pending'): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="order_ref" value="<?= htmlspecialchars($order['orderRef']) ?>">
                                <button type="submit" name="action" value="processing" class="btn btn-sm btn-primary">Mark Processing</button>
                                <button type="submit" name="action" value="cancelled" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this order?')">Cancel</button>
                            </form>
                        <?php elseif ($order['status'] === 'processing'): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="order_ref" value="<?= htmlspecialchars($order['orderRef']) ?>">
                                <button type="submit" name="action" value="done" class="btn btn-sm btn-success">Mark Done</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
=======
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Order Ref</th>
                    <th>Date</th>
                    <th>Buyer</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Receipt</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['orderRef']) ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                    <td><?= htmlspecialchars($buyers[$order['user_id']]['name'] ?? 'Unknown') ?><br><small><?= htmlspecialchars($buyers[$order['user_id']]['email'] ?? '') ?></small></td>
                    <td><span class="badge bg-<?= $order['status'] === 'done' ? 'success' : ($order['status'] === 'processing' ? 'primary' : ($order['status'] === 'queue' ? 'warning text-dark' : 'secondary')) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
                    <td>
                        <ul class="mb-0 ps-3">
                        <?php if (!empty($order_items[$order['orderRef']])): ?>
                            <?php foreach ($order_items[$order['orderRef']] as $item): ?>
                                <li><?= htmlspecialchars($item['food_name']) ?> x <?= $item['quantity'] ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><em>No items</em></li>
                        <?php endif; ?>
                        </ul>
                    </td>
                    <td>₱<?= number_format($order['total_price'],2) ?></td>
                    <td>
                        <?php if ($order['receipt_image']): ?>
                            <img src="<?= htmlspecialchars($order['receipt_image']) ?>" alt="Receipt" style="max-width:80px;max-height:80px;display:block;margin-bottom:5px;">
                        <?php else: ?>
                            <span class="text-muted small">No receipt</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($order['status'] === 'queue' && $order['receipt_image']): ?>
                            <form method="post" class="d-flex flex-column gap-1">
                                <input type="hidden" name="order_ref" value="<?= htmlspecialchars($order['orderRef']) ?>">
                                <button type="submit" name="action" value="processing" class="btn btn-sm btn-success">Approve</button>
                                <button type="submit" name="action" value="cancelled" class="btn btn-sm btn-danger">Decline</button>
                            </form>
                        <?php elseif ($order['status'] === 'processing'): ?>
                            <form method="post">
                                <input type="hidden" name="order_ref" value="<?= htmlspecialchars($order['orderRef']) ?>">
                                <button type="submit" name="action" value="done" class="btn btn-sm btn-primary">Mark as Done</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
>>>>>>> master
</div> 