<?php
// config/db.php
// Central database connection using PDO and error handling.

$host = 'localhost';
$db   = 'restaurant_system';
$user = 'root';        // XAMPP default
$pass = '';            // XAMPP default (empty)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // In production, log error instead of echoing details
    echo 'Database connection failed.';
    exit;
}