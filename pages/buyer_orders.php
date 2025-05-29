<?php
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'buyer') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}
require_once __DIR__ . '/../includes/db.php';

$buyer_id = $_SESSION['user_id'];
$upload_success = $upload_error = '';

// Handle receipt upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_ref'], $_FILES['receipt_image'])) {
    $order_ref = $_POST['order_ref'];
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
        $target = '../assets/imgs/receipt_' . $order_ref . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $target)) {
            $stmt = $pdo->prepare("UPDATE orders SET receipt_image=? WHERE orderRef=? AND user_id=?");
            if ($stmt->execute([$target, $order_ref, $buyer_id])) {
                $upload_success = 'Receipt uploaded.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $upload_error = 'Failed to save receipt.';
            }
        } else {
            $upload_error = 'Failed to upload receipt.';
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
<link rel="stylesheet" href="../assets/css/dashboard.css">
<div class="container-fluid px-4 pt-4">
    <div class="dashboard-section-title mb-3">My Orders</div>
    <?php if ($upload_success): ?><div class="alert alert-success mb-2"><?= $upload_success ?></div><?php endif; ?>
    <?php if ($upload_error): ?><div class="alert alert-danger mb-2"><?= $upload_error ?></div><?php endif; ?>
    <div class="dashboard-table mb-4">
    <table class="table mb-0">
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
                        <form method="post" enctype="multipart/form-data" style="display:inline" onsubmit="return showReceiptPreview(event, 'receipt_preview_<?= $order['orderRef'] ?>')">
                            <input type="hidden" name="order_ref" value="<?= htmlspecialchars($order['orderRef']) ?>">
                            <input type="file" name="receipt_image" accept="image/*" required onchange="previewReceiptImage(event, 'receipt_preview_<?= $order['orderRef'] ?>')">
                            <img id="receipt_preview_<?= $order['orderRef'] ?>" src="#" alt="Preview" style="display:none;max-width:80px;max-height:80px;margin-top:8px;" />
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
</div>
<script>
function previewReceiptImage(event, id) {
    const [file] = event.target.files;
    const preview = document.getElementById(id);
    if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    } else {
        preview.src = '#';
        preview.style.display = 'none';
    }
}
</script> 