<?php
// Database configuration
$host = 'localhost';
$db   = 'canteen_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Data Source Name
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

if (!defined('DB_HOST')) define('DB_HOST', $host ?? $db_host ?? 'localhost');
if (!defined('DB_USER')) define('DB_USER', $user ?? $db_user ?? 'root');
if (!defined('DB_PASS')) define('DB_PASS', $pass ?? $db_pass ?? '');
if (!defined('DB_NAME')) define('DB_NAME', $db ?? $db_name ?? 'canteen_db'); 