<?php
// includes/header.php
// Shared HTML header and navigation for the site.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Ensure consistent promo/order time calculations.
// Adjust if your restaurant operates in a different timezone.
date_default_timezone_set('Asia/Colombo');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Management & Ordering System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/Restaurant-System/assets/css/style.css?v=2">
    <?php if(isset($pageClass) && $pageClass === 'admin-page'): ?>
    <link rel="stylesheet" href="/Restaurant-System/assets/css/admin.css">
    <?php endif; ?>
    <?php if(isset($pageClass) && $pageClass === 'user-page'): ?>
    <link rel="stylesheet" href="/Restaurant-System/assets/css/user.css">
    <?php endif; ?>
    <?php if(isset($pageClass) && $pageClass === 'menu-page'): ?>
    <link rel="stylesheet" href="/Restaurant-System/assets/css/menu.css">
    <?php endif; ?>
    <?php if(isset($pageClass) && $pageClass === 'udashboard-page'): ?>
    <link rel="stylesheet" href="/Restaurant-System/assets/css/userdash.css">
    <?php endif; ?>
    <?php if(isset($pageClass) && $pageClass === 'admindash-page'): ?>
    <link rel="stylesheet" href="/Restaurant-System/assets/css/admindash.css">
    <?php endif; ?>
    <?php if(isset($pageClass) && $pageClass === 'order_receipt-page'): ?>
    <link rel="stylesheet" href="/Restaurant-System/assets/css/order_receipt.css">
    <?php endif; ?>
    <?php if(isset($pageClass) && $pageClass === 'payment-page'): ?>
    <link rel="stylesheet" href="/Restaurant-System/assets/css/payment.css">
    <?php endif; ?>

    <script src="/Restaurant-System/assets/js/main.js?v=2"></script>
</head>

<body class="<?php echo $pageClass ?? ''; ?>">
    <header class="main-header">
        <div class="nav-wrapper">

            <!-- Left Navigation -->
            <nav class="nav nav-left">
                <a href="/Restaurant-System/index.php">Home</a>
                <a href="/Restaurant-System/user/menu.php">Menu</a>
            </nav>

            <!-- Center Logo -->
            <div class="logo"><i class="fa-solid fa-utensils"></i> Restaroma</div>

            <!-- Right Navigation -->
            <nav class="nav nav-right">
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/Restaurant-System/user/udashboard.php">Dashboard</a>
                <a href="/Restaurant-System/user/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                <?php else: ?>
                <a href="/Restaurant-System/user/login.php">Login</a>
                <a href="/Restaurant-System/user/register.php">Register</a>
                <a href="/Restaurant-System/admin/adashboard.php" class="admin-link"><i class="fas fa-user-shield"></i></a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="main-content">
        <div class="container">