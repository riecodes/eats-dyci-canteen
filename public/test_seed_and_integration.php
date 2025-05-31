<?php
require_once __DIR__ . '../includes/db.php';

// --- SEED DATA ---

$admin_id = 1;
$seller_id = 12;
$buyer_id = 13;

// 1. Add a canteen
$pdo->exec("INSERT INTO canteens (name, image) VALUES ('Test Canteen', '../assets/imgs/canteen-default.jpg')");
$canteen_id = $pdo->lastInsertId();

// 2. Add a stall for the seller
$pdo->exec("INSERT INTO stalls (name, canteen_id, seller_id, image) VALUES 
    ('Stall One', $canteen_id, $seller_id, '../assets/imgs/stall-default.jpg')");
$stall_id = $pdo->lastInsertId();

// 3. Add a product for the stall
$pdo->exec("INSERT INTO products (name, price, stock, stall_id, seller_id, image) VALUES 
    ('Burger', 50, 10, $stall_id, $seller_id, '../assets/imgs/product-default.jpg')");

// 4. Add an admin announcement
$pdo->exec("INSERT INTO announcements (title, message, type, seller_id, stall_id) VALUES
    ('Admin Notice', 'Welcome to the canteen system!', 'info', NULL, NULL)
");

echo "Seed data inserted.\\n";

// --- INTEGRATION TESTS ---

function post($url, $data, &$cookies = '') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookies) curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    // Extract cookies
    preg_match_all('/^Set-Cookie:\\s*([^;]*)/mi', $header, $matches);
    foreach($matches[1] as $cookie) $cookies .= $cookie.'; ';
    curl_close($ch);
    return $body;
}

function get($url, &$cookies = '') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($cookies) curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $body = curl_exec($ch);
    curl_close($ch);
    return $body;
}

$base = 'http://localhost/eats-dyci-canteen/public/';

$tests = [
    [
        'role' => 'admin',
        'email' => 'a@a.com',
        'password' => 'a',
        'dashboard' => $base . '../pages/admin_dashboard.php',
        'keyword' => 'Recent Orders', // Appears on admin dashboard
    ],
    [
        'role' => 'seller',
        'email' => 's@s.com',
        'password' => 's',
        'dashboard' => $base . '../pages/seller_dashboard.php',
        'keyword' => 'Recent Orders', // Appears on seller dashboard
    ],
    [
        'role' => 'buyer',
        'email' => 'b@b.com',
        'password' => 'b',
        'dashboard' => $base . '../pages/buyer_order.php',
        'keyword' => 'CANTEENS', // Appears on buyer order page
    ],
];

foreach ($tests as $test) {
    $cookies = '';
    echo "Testing {$test['role']} login...\n";
    // Login
    $loginBody = post($base . 'login.php', [
        'email' => $test['email'],
        'password' => $test['password'],
        'login' => 1
    ], $cookies);

    // Go to dashboard
    $dashboardBody = get($test['dashboard'], $cookies);

    if (strpos($dashboardBody, $test['keyword']) !== false) {
        echo "{$test['role']} login: PASS\n";
    } else {
        echo "{$test['role']} login: FAIL\n";
    }
}

echo "\nAll seed and basic login tests complete.\n";
?>