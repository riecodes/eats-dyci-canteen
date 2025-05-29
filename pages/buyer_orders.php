<?php
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'buyer') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}
require_once __DIR__ . '/../includes/db.php';
require_once '../includes/upload.php';

$buyer_id = $_SESSION['user_id'];
$upload_success = $upload_error = '';

// Handle receipt upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_receipt'], $_POST['order_ref'])) {
    $order_ref = $_POST['order_ref'];
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        list($ok, $result) = secure_image_upload($_FILES['receipt']);
        if ($ok) {
            $target = $result;
            $stmt = $pdo->prepare('UPDATE orders SET receipt_image = ? WHERE orderRef = ? AND user_id = ?');
            if ($stmt->execute([$target, $order_ref, $buyer_id])) {
                $upload_success = 'Receipt uploaded!';
            } else {
                $upload_error = 'Failed to save receipt.';
            }
        } else {
            $upload_error = $result;
        }
    } else {
        $upload_error = 'No file uploaded.';
    }
}

// Get all orders for this buyer
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$buyer_id]);
$orders = $stmt->fetchAll();
?>
<div class="container-fluid">
    <h2>My Orders</h2>
    <?php if ($upload_success): ?><div class="alert alert-success"><?= $upload_success ?></div><?php endif; ?>
    <?php if ($upload_error): ?><div class="alert alert-danger"><?= $upload_error ?></div><?php endif; ?>
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>Order Ref</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
                <th>Receipt</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= htmlspecialchars($order['orderRef']) ?></td>
                <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                <td><span class="dashboard-badge <?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
                <td>₱<?= number_format($order['total_price'],2) ?></td>
                <td>
                    <?php if ($order['receipt_image']): ?>
                        <img src="<?= $order['receipt_image'] ?>" alt="Receipt" style="max-width:80px;max-height:80px;object-fit:cover;">
                    <?php elseif (in_array($order['status'], ['queue','pending'])): ?>
                        <form method="post" enctype="multipart/form-data" style="display:inline">
                            <input type="hidden" name="order_ref" value="<?= htmlspecialchars($order['orderRef']) ?>">
                            <input type="file" name="receipt" accept="image/*" required>
                            <button type="submit" class="btn btn-sm btn-primary">Upload Receipt</button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted">N/A</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div> 