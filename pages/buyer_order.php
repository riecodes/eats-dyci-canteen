<?php
// buyer_order.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db.php';
require_once '../includes/upload.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'buyer') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    return;
}

// --- CART LOGIC ---
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart = &$_SESSION['cart'];

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $food_id = intval($_POST['food_id']);
    $qty = max(1, intval($_POST['quantity']));
    // Fetch product to check stock
    $stmt = $pdo->prepare('SELECT * FROM foods WHERE id = ?');
    $stmt->execute([$food_id]);
    $product = $stmt->fetch();
    if ($product && $qty <= $product['stock']) {
        if (isset($cart[$food_id])) {
            $cart[$food_id] += $qty;
            if ($cart[$food_id] > $product['stock']) {
                $cart[$food_id] = $product['stock'];
            }
        } else {
            $cart[$food_id] = $qty;
        }
        $cart_success = 'Added to cart!';
    } else {
        $cart_error = 'Invalid quantity or product.';
    }
}
// Handle update cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $food_id => $qty) {
        $food_id = intval($food_id);
        $qty = max(1, intval($qty));
        // Check stock
        $stmt = $pdo->prepare('SELECT stock FROM foods WHERE id = ?');
        $stmt->execute([$food_id]);
        $stock = $stmt->fetchColumn();
        if ($stock !== false && $qty <= $stock) {
            $cart[$food_id] = $qty;
        } elseif ($stock !== false) {
            $cart[$food_id] = $stock;
        }
    }
    $cart_success = 'Cart updated!';
}
// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_cart'])) {
    $food_id = intval($_POST['food_id']);
    unset($cart[$food_id]);
    $cart_success = 'Item removed from cart.';
}

// Fetch all canteens
$canteens = $pdo->query('SELECT * FROM canteens ORDER BY name ASC')->fetchAll();

// Get selected canteen and stall from GET
$selected_canteen = isset($_GET['canteen_id']) ? intval($_GET['canteen_id']) : 0;
$selected_stall = isset($_GET['stall_id']) ? intval($_GET['stall_id']) : 0;

// Fetch stalls for selected canteen
$stalls = [];
if ($selected_canteen) {
    $stmt = $pdo->prepare('SELECT * FROM stalls WHERE canteen_id = ? ORDER BY name ASC');
    $stmt->execute([$selected_canteen]);
    $stalls = $stmt->fetchAll();
}

// Fetch products for selected stall
$products = [];
if ($selected_stall) {
    $stmt = $pdo->prepare('SELECT * FROM foods WHERE stall_id = ? ORDER BY name ASC');
    $stmt->execute([$selected_stall]);
    $products = $stmt->fetchAll();
}

// --- ORDER PLACEMENT LOGIC ---
$order_success = $order_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (empty($cart)) {
        $order_error = 'Your cart is empty.';
    } else {
        // 1. Check order limits (e.g., max 3 orders per day)
        $user_id = $_SESSION['user_id'];
        $today = date('Y-m-d');
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND DATE(created_at) = ?');
        $stmt->execute([$user_id, $today]);
        $orders_today = $stmt->fetchColumn();
        $max_orders_per_day = 3; // Change as needed
        if ($orders_today >= $max_orders_per_day) {
            $order_error = 'You have reached the maximum number of orders for today.';
        } else {
            // 2. Check stock for each item
            $valid = true;
            $cart_products = [];
            foreach ($cart as $food_id => $qty) {
                $stmt = $pdo->prepare('SELECT * FROM foods WHERE id = ?');
                $stmt->execute([$food_id]);
                $prod = $stmt->fetch();
                if (!$prod || $qty > $prod['stock']) {
                    $valid = false;
                    $order_error = 'Insufficient stock for ' . htmlspecialchars($prod['name'] ?? 'a product') . '.';
                    break;
                }
                $cart_products[$food_id] = $prod;
            }
            if ($valid) {
                // 3. Insert order
                $orderRef = uniqid('ORD');
                $total_price = 0;
                foreach ($cart as $food_id => $qty) {
                    $total_price += $cart_products[$food_id]['price'] * $qty;
                }
                $stmt = $pdo->prepare('INSERT INTO orders (orderRef, user_id, total_price) VALUES (?, ?, ?)');
                if ($stmt->execute([$orderRef, $user_id, $total_price])) {
                    // 4. Insert order items and update stock
                    foreach ($cart as $food_id => $qty) {
                        $stmt = $pdo->prepare('INSERT INTO order_items (order_id, food_id, quantity) VALUES (?, ?, ?)');
                        $stmt->execute([$orderRef, $food_id, $qty]);
                        // Update stock
                        $stmt = $pdo->prepare('UPDATE foods SET stock = stock - ? WHERE id = ?');
                        $stmt->execute([$qty, $food_id]);
                    }
                    // 5. Clear cart
                    $_SESSION['cart'] = [];
                    $order_success = 'Order placed successfully!';
                } else {
                    $order_error = 'Failed to place order.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - Eats DYCI Canteen</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container mt-4">
    <h2 class="mb-4">Place an Order</h2>
    <?php if (!empty($cart_success)): ?><div class="alert alert-success"><?= $cart_success ?></div><?php endif; ?>
    <?php if (!empty($cart_error)): ?><div class="alert alert-danger"><?= $cart_error ?></div><?php endif; ?>
    <form method="get" action="">
    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <label for="canteenSelect" class="form-label">Select Canteen</label>
                <select id="canteenSelect" name="canteen_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Select Canteen --</option>
                    <?php foreach ($canteens as $canteen): ?>
                        <option value="<?= $canteen['id'] ?>" <?= $selected_canteen == $canteen['id'] ? 'selected' : '' ?>><?= htmlspecialchars($canteen['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="stallSelect" class="form-label">Select Stall</label>
                <select id="stallSelect" name="stall_id" class="form-select" onchange="this.form.submit()" <?= $selected_canteen ? '' : 'disabled' ?>>
                    <option value="">-- Select Stall --</option>
                    <?php foreach ($stalls as $stall): ?>
                        <option value="<?= $stall['id'] ?>" <?= $selected_stall == $stall['id'] ? 'selected' : '' ?>><?= htmlspecialchars($stall['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-8">
            <div id="productsSection">
                <h5>Products</h5>
                <div id="productsList" class="row g-3">
                    <?php if ($selected_stall && $products): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100">
                                    <img src="<?= htmlspecialchars($product['image'] ?? '../assets/imgs/product-default.jpg') ?>" class="card-img-top" alt="Product Image" style="max-height:150px;object-fit:cover;">
                                    <div class="card-body">
                                        <h6 class="card-title mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                                        <div class="mb-1 text-muted">₱<?= number_format($product['price'],2) ?></div>
                                        <div class="mb-2 small">Stock: <?= (int)$product['stock'] ?></div>
                                        <form method="post" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="food_id" value="<?= $product['id'] ?>">
                                            <input type="number" class="form-control form-control-sm" name="quantity" min="1" max="<?= (int)$product['stock'] ?>" value="1" style="width:70px;">
                                            <button class="btn btn-outline-primary btn-sm" type="submit" name="add_to_cart" <?= $product['stock'] < 1 ? 'disabled' : '' ?>>Add</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($selected_stall): ?>
                        <div class="col-12"><div class="alert alert-info">No products found for this stall.</div></div>
                    <?php else: ?>
                        <div class="col-12"><div class="alert alert-secondary">Select a canteen and stall to view products.</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </form>
    <hr>
    <div class="row">
        <div class="col-md-8 offset-md-4">
            <h5>Your Cart</h5>
            <div id="cartItems">
                <?php if (!empty($order_success)): ?><div class="alert alert-success"><?= $order_success ?></div><?php endif; ?>
                <?php if (!empty($order_error)): ?><div class="alert alert-danger"><?= $order_error ?></div><?php endif; ?>
                <?php if (!empty($cart)): ?>
                    <form method="post">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total = 0;
                                foreach ($cart as $food_id => $qty):
                                    $stmt = $pdo->prepare('SELECT * FROM foods WHERE id = ?');
                                    $stmt->execute([$food_id]);
                                    $prod = $stmt->fetch();
                                    if (!$prod) continue;
                                    $subtotal = $prod['price'] * $qty;
                                    $total += $subtotal;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($prod['name']) ?></td>
                                    <td>₱<?= number_format($prod['price'],2) ?></td>
                                    <td><input type="number" name="quantities[<?= $food_id ?>]" value="<?= $qty ?>" min="1" max="<?= (int)$prod['stock'] ?>" class="form-control form-control-sm" style="width:70px;"></td>
                                    <td>₱<?= number_format($subtotal,2) ?></td>
                                    <td>
                                        <button type="submit" name="remove_from_cart" value="1" class="btn btn-danger btn-sm" formaction="" formmethod="post" onclick="this.form.food_id.value=<?= $food_id ?>;">Remove</button>
                                        <input type="hidden" name="food_id" value="<?= $food_id ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th colspan="2">₱<?= number_format($total,2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                        <button type="submit" name="update_cart" class="btn btn-secondary">Update Cart</button>
                        <button type="submit" name="place_order" class="btn btn-primary">Place Order</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-secondary">Your cart is empty.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html> 