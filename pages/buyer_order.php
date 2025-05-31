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

// Get selected canteen and stall from GET
$selected_canteen = isset($_GET['canteen_id']) ? intval($_GET['canteen_id']) : 0;
$selected_stall = isset($_GET['stall_id']) ? intval($_GET['stall_id']) : 0;

// If stall changes, clear cart
if (isset($_GET['stall_id'])) {
    if (!isset($_SESSION['last_stall_id'])) {
        $_SESSION['last_stall_id'] = $selected_stall;
    } elseif ($_SESSION['last_stall_id'] != $selected_stall) {
        $_SESSION['cart'] = [];
        $cart = &$_SESSION['cart'];
        $cart_success = 'Cart cleared: You changed stall.';
        $_SESSION['last_stall_id'] = $selected_stall;
    }
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $qty = max(1, intval($_POST['quantity']));
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if ($product && $qty <= $product['stock'] && $product['stall_id'] == $selected_stall) {
        if (isset($cart[$product_id])) {
            $cart[$product_id] += $qty;
            if ($cart[$product_id] > $product['stock']) {
                $cart[$product_id] = $product['stock'];
            }
        } else {
            $cart[$product_id] = $qty;
        }
        $cart_success = 'Added to cart!';
    } else {
        $cart_error = 'Invalid quantity or product.';
    }
}
// Handle update cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $product_id => $qty) {
        $product_id = intval($product_id);
        $qty = max(1, min(5, intval($qty)));
        // Check stock
        $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ?');
        $stmt->execute([$product_id]);
        $stock = $stmt->fetchColumn();
        if ($stock !== false && $qty <= $stock) {
            $cart[$product_id] = $qty;
        } elseif ($stock !== false) {
            $cart[$product_id] = min($stock, 5);
        }
    }
    $cart_success = 'Cart updated!';
}
// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_cart'])) {
    $product_id = intval($_POST['product_id']);
    unset($cart[$product_id]);
    $cart_success = 'Item removed from cart.';
}

// Fetch all canteens
$canteens = $pdo->query('SELECT * FROM canteens ORDER BY name ASC')->fetchAll();

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
    $stmt = $pdo->prepare('SELECT * FROM products WHERE stall_id = ? ORDER BY name ASC');
    $stmt->execute([$selected_stall]);
    $products = $stmt->fetchAll();
}

// --- ORDER PLACEMENT LOGIC ---
$order_success = $order_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $user_id = $_SESSION['user_id'];
    // Check if user exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() == 0) {
        session_destroy();
        echo '<div class="alert alert-danger">Your account no longer exists. Please contact the admin.</div>';
        return;
    }
    if (empty($cart)) {
        $order_error = 'Your cart is empty.';
    } else {
        $valid = true;
        foreach ($cart as $product_id => $qty) {
            $stmt = $pdo->prepare('SELECT stall_id FROM products WHERE id = ?');
            $stmt->execute([$product_id]);
            $prod_stall_id = $stmt->fetchColumn();
            if ($prod_stall_id != $selected_stall) {
                $valid = false;
                $order_error = 'All items in your cart must be from the same stall.';
                break;
            }
        }
        if (!$valid) {
            // Do not proceed
    } else {
            // Enforce max 5 total items per cart (not just unique products)
            $total_qty = array_sum($cart);
            if ($total_qty > 5) {
                $order_error = 'You can only order up to 5 items in total per order.';
                $valid = false;
            }
            // 2. Check stock for each item
            $cart_products = [];
                foreach ($cart as $product_id => $qty) {
                    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
                    $stmt->execute([$product_id]);
                $prod = $stmt->fetch();
                if (!$prod || $qty > $prod['stock']) {
                    $valid = false;
                    $order_error = 'Insufficient stock for ' . htmlspecialchars($prod['name'] ?? 'a product') . '.';
                    break;
                }
                    $cart_products[$product_id] = $prod;
            }
                // 3. Validate receipt image (portrait, file type)
                $receipt_path = null;
            if ($valid) {
                    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
                        $img_info = getimagesize($_FILES['receipt_image']['tmp_name']);
                        if ($img_info && $img_info[1] > $img_info[0]) { // portrait
                            $ext = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
                            $target = '../assets/imgs/receipt_' . uniqid() . '.' . $ext;
                            if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $target)) {
                                $receipt_path = $target;
                            } else {
                                $order_error = 'Failed to upload receipt image.';
                                $valid = false;
                            }
                        } else {
                        $order_error = 'Receipt image must be portrait (height greater than width). Please upload a portrait image.';
                            $valid = false;
                        }
                    } else {
                        $order_error = 'Receipt image is required.';
                        $valid = false;
                    }
                }
            // Prevent orders after 2:45 PM
            $current_time = new DateTime();
            $cutoff_time = (clone $current_time)->setTime(14, 45, 0); // 2:45 PM today
            if ($current_time >= $cutoff_time) {
                $order_error = 'Orders cannot be placed after 2:45 PM.';
                $valid = false;
                }
                // 4. Insert order
                if ($valid) {
                    $orderRef = uniqid('ORD');
                    $total_price = 0;
                    foreach ($cart as $product_id => $qty) {
                        $total_price += $cart_products[$product_id]['price'] * $qty;
                    }
                    $note = trim($_POST['order_note'] ?? '');
                    $stmt = $pdo->prepare('INSERT INTO orders (orderRef, user_id, total_price, receipt_image, note, status) VALUES (?, ?, ?, ?, ?, ?)');
                    if ($stmt->execute([$orderRef, $user_id, $total_price, $receipt_path, $note, 'queue'])) {
                        // 5. Insert order items and update stock
                        foreach ($cart as $product_id => $qty) {
                            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)');
                            $stmt->execute([$orderRef, $product_id, $qty]);
                            // Update stock
                            $stmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
                            $stmt->execute([$qty, $product_id]);
                        }
                        // 6. Clear cart
                    $_SESSION['cart'] = [];
                    $order_success = 'Order placed successfully!';
                } else {
                    $order_error = 'Failed to place order.';
            }
        }
    }
}
}

// Show canteens as cards at the top
if (!$selected_canteen) {
    echo '<div class="dashboard-section-title mb-3"><div class="dashboard-card" style="font-size:1.5rem; font-weight:700; letter-spacing:2px; text-align:center;">CANTEENS</div></div>';
    // Search bar
    echo '<div class="mb-3"><input type="text" id="canteenSearch" class="form-control" placeholder="Search canteens by name..."></div>';
    echo '<div class="row dashboard-cards g-4 mb-4" id="canteenCards">';
    foreach ($canteens as $canteen) {
        echo '<div class="col-md-4 mb-3 canteen-card-item">';
        echo '<div class="dashboard-card h-100">';
        $img = htmlspecialchars($canteen['image'] ?? '../assets/imgs/canteen-default.jpg');
        echo '<img src="' . $img . '" class="card-img-top mb-2" alt="Canteen Image" style="aspect-ratio:5/4; width:100%; max-width:260px; object-fit:cover; border-radius:1rem; margin:auto; display:block;">';
        echo '<div class="card-body p-0">';
        echo '<h5 class="card-title mb-2 text-center canteen-name" style="font-size:1.2rem; font-weight:600;">' . htmlspecialchars($canteen['name']) . '</h5>';
        echo '<form method="get" action="index.php">';
        echo '<input type="hidden" name="page" value="buyer_order">';
        echo '<input type="hidden" name="canteen_id" value="' . $canteen['id'] . '">';
        echo '<button type="submit" class="btn btn-primary w-100">Select</button>';
        echo '</form>';
        echo '</div></div></div>';
    }
    echo '</div>';
    // JS for search
    echo '<script>';
    echo 'document.getElementById("canteenSearch").addEventListener("input", function() {';
    echo '  var val = this.value.toLowerCase();';
    echo '  document.querySelectorAll(".canteen-card-item").forEach(function(card) {';
    echo '    var name = card.querySelector(".canteen-name").textContent.toLowerCase();';
    echo '    card.style.display = name.includes(val) ? "" : "none";';
    echo '  });';
    echo '});';
    echo '</script>';
    return;
}

// After canteen selection, show all stalls for the selected canteen as cards
if ($selected_canteen && !$selected_stall) {
    echo '<div class="dashboard-section-title mb-3"><div class="dashboard-card" style="font-size:1.5rem; font-weight:700; letter-spacing:2px; text-align:center;">STALLS</div></div>';
    // Search bar for stalls
    echo '<div class="mb-3"><input type="text" id="stallSearch" class="form-control" placeholder="Search stalls by name..."></div>';
    $stmt = $pdo->prepare('SELECT * FROM stalls WHERE canteen_id = ? ORDER BY name ASC');
    $stmt->execute([$selected_canteen]);
    $stalls = $stmt->fetchAll();
    echo '<div class="row dashboard-cards g-4 mb-4" id="stallCards">';
    foreach ($stalls as $stall) {
        echo '<div class="col-md-4 mb-3 stall-card-item">';
        echo '<div class="dashboard-card h-100">';
        $img = htmlspecialchars($stall['image'] ?? '../assets/imgs/stall-default.jpg');
        echo '<img src="' . $img . '" class="card-img-top mb-2" alt="Stall Image" style="aspect-ratio:5/4; width:100%; max-width:260px; object-fit:cover; border-radius:1rem; margin:auto; display:block;">';
        echo '<div class="card-body p-0">';
        echo '<h5 class="card-title mb-2 text-center stall-name" style="font-size:1.2rem; font-weight:600;">' . htmlspecialchars($stall['name']) . '</h5>';
        echo '<p class="card-text mb-2 text-center">' . htmlspecialchars($stall['description'] ?? '') . '</p>';
        echo '<form method="get" action="index.php">';
        echo '<input type="hidden" name="page" value="buyer_order">';
        echo '<input type="hidden" name="canteen_id" value="' . $selected_canteen . '">';
        echo '<input type="hidden" name="stall_id" value="' . $stall['id'] . '">';
        echo '<button type="submit" class="btn btn-primary w-100">Select</button>';
        echo '</form>';
        echo '</div></div></div>';
    }
    echo '</div>';
    // JS for stall search
    echo '<script>';
    echo 'document.getElementById("stallSearch").addEventListener("input", function() {';
    echo '  var val = this.value.toLowerCase();';
    echo '  document.querySelectorAll(".stall-card-item").forEach(function(card) {';
    echo '    var name = card.querySelector(".stall-name").textContent.toLowerCase();';
    echo '    card.style.display = name.includes(val) ? "" : "none";';
    echo '  });';
    echo '});';
    echo '</script>';
    return;
}

// After stall selection, show all products for the selected stall as cards
if ($selected_canteen && $selected_stall) {
    echo '<div class="dashboard-section-title mb-3"><div class="dashboard-card" style="font-size:1.5rem; font-weight:700; letter-spacing:2px; text-align:center;">PRODUCTS</div></div>';
    // Search bar for products
    echo '<div class="mb-3"><input type="text" id="productSearch" class="form-control" placeholder="Search products by name..."></div>';
    $stmt = $pdo->prepare('SELECT * FROM products WHERE stall_id = ? ORDER BY name ASC');
    $stmt->execute([$selected_stall]);
    $products = $stmt->fetchAll();
    echo '<div class="dashboard-table mb-4">';
    echo '<table class="table mb-0" id="productTable">';
    echo '<thead class="table-light"><tr><th>Image</th><th>Name</th><th>Price</th><th>Stock</th><th>Add to Cart</th></tr></thead><tbody>';
    foreach ($products as $product) {
        if ((int)$product['stock'] <= 0) continue;
        $img = htmlspecialchars($product['image'] ?? '../assets/imgs/product-default.jpg');
        echo '<tr class="product-row">';
        echo '<td><img src="' . $img . '" alt="Product Image" style="aspect-ratio:5/4; width:70px; height:56px; object-fit:cover; border-radius:0.7rem;"></td>';
        echo '<td class="product-name">' . htmlspecialchars($product['name']) . '</td>';
        echo '<td>₱' . number_format($product['price'],2) . '</td>';
        echo '<td>' . (int)$product['stock'] . '</td>';
        echo '<td>';
        echo '<form method="post" class="d-flex align-items-center gap-2 mb-0">';
        echo '<input type="hidden" name="product_id" value="' . $product['id'] . '">';
        echo '<input type="number" class="form-control form-control-sm" name="quantity" min="1" max="5" value="1" style="width:70px;">';
        $cart_count = array_sum($cart);
        $disabled = ($product['stock'] < 1 || $cart_count >= 5) ? 'disabled' : '';
        echo '<button class="btn btn-outline-primary btn-sm" type="submit" name="add_to_cart" ' . $disabled . '>Add</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
    // JS for product search
    echo '<script>';
    echo 'document.getElementById("productSearch").addEventListener("input", function() {';
    echo '  var val = this.value.toLowerCase();';
    echo '  document.querySelectorAll("#productTable .product-row").forEach(function(row) {';
    echo '    var name = row.querySelector(".product-name").textContent.toLowerCase();';
    echo '    row.style.display = name.includes(val) ? "" : "none";';
    echo '  });';
    echo '});';
    echo '</script>';
    // Cart and checkout section
    if (!empty($cart)) {
        // Feedback/notification above cart
        if (!empty($cart_success)) {
            echo '<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">' . htmlspecialchars($cart_success) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
        if (!empty($cart_error)) {
            echo '<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">' . htmlspecialchars($cart_error) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
        echo '<div class="mt-4">';
        echo '<h5>Your Cart</h5>';
        echo '<form method="post" enctype="multipart/form-data">';
        echo '<div class="dashboard-table mb-4">';
        echo '<table class="table mb-0">';
        echo '<thead class="table-light"><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th></tr></thead><tbody>';
                                $total = 0;
        foreach ($cart as $product_id => $qty) {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
            $stmt->execute([$product_id]);
                                    $prod = $stmt->fetch();
                                    if (!$prod) continue;
                                    $subtotal = $prod['price'] * $qty;
                                    $total += $subtotal;
            echo '<tr>';
            echo '<td>' . htmlspecialchars($prod['name']) . '</td>';
            echo '<td>₱' . number_format($prod['price'],2) . '</td>';
            echo '<td><input type="number" name="quantities[' . $product_id . ']" value="' . $qty . '" min="1" max="5" class="form-control form-control-sm" style="width:70px;"></td>';
            echo '<td>₱' . number_format($subtotal,2) . '</td>';
            echo '<td><button type="submit" name="remove_from_cart" value="1" class="btn btn-danger btn-sm" formaction="" formmethod="post" onclick="this.form.product_id.value=' . $product_id . ';">Remove</button>';
            echo '<input type="hidden" name="product_id" value="' . $product_id . '"></td>';
            echo '</tr>';
        }
        echo '</tbody><tfoot><tr><th colspan="3" class="text-end">Total</th><th colspan="2">₱' . number_format($total,2) . '</th></tr></tfoot></table>';
        echo '</div>';
        echo '<button type="submit" name="update_cart" class="btn btn-secondary">Update Cart</button> ';
        echo '<button type="submit" name="proceed_checkout" class="btn btn-primary">Proceed to Checkout</button>';
        echo '</form>';
        echo '</div>';
        // Checkout section
        if (isset($_POST['proceed_checkout']) && empty($order_success)) {
            // Fetch seller QR code
            $stmt = $pdo->prepare('SELECT s.seller_id, u.qr_code FROM stalls s JOIN users u ON s.seller_id = u.id WHERE s.id = ?');
            $stmt->execute([$selected_stall]);
            $seller = $stmt->fetch();
            $seller_qr = $seller['qr_code'] ?? '../assets/imgs/qrcode-placeholder.jpg';
            echo '<div class="mt-4">';
            echo '<h5>Checkout</h5>';
            echo '<div class="mb-3">Seller GCash QR Code:<br><img src="' . htmlspecialchars($seller_qr) . '" alt="Seller QR Code" style="max-width:200px;max-height:200px;object-fit:contain;background:#fff;cursor:pointer;aspect-ratio:1/1;" onclick="showQrModal(this.src)">';
            echo '<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">';
            echo '<div class="modal-dialog modal-dialog-centered">';
            echo '<div class="modal-content bg-transparent border-0">';
            echo '<div class="modal-body text-center p-0">';
            echo '<img id="qrModalImg" src="" alt="QR Code" style="max-width:90vw;max-height:90vh;object-fit:contain;box-shadow:0 0 24px #0008;border-radius:1rem;">';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '<form method="post" enctype="multipart/form-data">';
            echo '<div class="mb-3">';
            echo '<label class="form-label">Upload Receipt (portrait only)</label>';
            echo '<input type="file" class="form-control" name="receipt_image" accept="image/*" required>';
            echo '</div>';
            echo '<div class="mb-3">';
            echo '<label class="form-label">Note (optional)</label>';
            echo '<textarea class="form-control" name="order_note" rows="2" placeholder="Add a note for the seller..."></textarea>';
            echo '</div>';
            echo '<button type="submit" name="place_order" class="btn btn-success">Place Order</button>';
            echo '</form>';
            echo '<script>';
            echo 'function showQrModal(src) {';
            echo '  var modal = new bootstrap.Modal(document.getElementById(\'qrModal\'));
  document.getElementById(\'qrModalImg\').src = src;
  modal.show();
}';
            echo 'window.addEventListener(\'DOMContentLoaded\', function() {';
            echo '  document.querySelectorAll(\'input[type=number][name^=quantities], input[type=number][name=quantity]\').forEach(function(input) {';
            echo '    input.addEventListener(\'input\', function() {';
            echo '      if (parseInt(this.value) > 5) this.value = 5;';
            echo '      if (parseInt(this.value) < 1) this.value = 1;';
            echo '    });';
            echo '  });';
            echo '});';
            echo '</script>';
            echo '</div>';
        }
    }
}

// Show error feedback if not portrait
if (!empty($order_error)) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($order_error) . '</div>';
}

// Success/Error Modal after placing order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    echo '<div class="modal fade" id="orderResultModal" tabindex="-1" aria-labelledby="orderResultModalLabel" aria-hidden="true">';
    echo '<div class="modal-dialog">';
    echo '<div class="modal-content">';
    echo '<div class="modal-header">';
    if (!empty($order_success)) {
        echo '<h5 class="modal-title text-success" id="orderResultModalLabel">Order Placed Successfully!</h5>';
    } else {
        echo '<h5 class="modal-title text-danger" id="orderResultModalLabel">Order Failed</h5>';
    }
    echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
    echo '</div>';
    echo '<div class="modal-body">';
    if (!empty($order_success)) {
        // Show order details
        if (isset($orderRef)) {
            echo '<div><strong>Order Reference:</strong> ' . htmlspecialchars($orderRef) . '</div>';
        }
        if (!empty($cart_products)) {
            echo '<div class="mt-2"><strong>Items:</strong><ul>';
            foreach ($cart_products as $prod) {
                $qty = isset($cart[$prod['id']]) ? $cart[$prod['id']] : 1;
                echo '<li>' . htmlspecialchars($prod['name']) . ' x ' . $qty . '</li>';
            }
            echo '</ul></div>';
        }
        if (isset($total_price)) {
            echo '<div><strong>Total:</strong> ₱' . number_format($total_price, 2) . '</div>';
        }
        echo '<div class="alert alert-success mt-3">' . htmlspecialchars($order_success) . '</div>';
    } else if (!empty($order_error)) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($order_error) . '</div>';
    }
    echo '</div>';
    echo '<div class="modal-footer">';
    echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '<script>document.addEventListener("DOMContentLoaded",function(){var m=new bootstrap.Modal(document.getElementById("orderResultModal"));m.show();});</script>';
}
