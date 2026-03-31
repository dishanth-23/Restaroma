<?php
// admin/adashboard.php
// Simple overview of counts for menu items, orders, reservations.
$pageClass = "admindash-page";
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require __DIR__ . '/../includes/header.php';

$counts = [];

$counts['menu_items'] = $pdo->query("SELECT COUNT(*) AS c FROM menu_items")->fetch()['c'];
$counts['orders'] = $pdo->query("SELECT COUNT(*) AS c FROM orders")->fetch()['c'];
$counts['reservations'] = $pdo->query("SELECT COUNT(*) AS c FROM reservations")->fetch()['c'];
$counts['promos'] = $pdo->query("SELECT COUNT(*) AS c FROM promos")->fetch()['c'];
?>

<p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>.</p>

<div class="admin-grid">
    <div class="admin-card">
        <div class="admin-img" style="background-image: url('../assets/cards/menu.png');"></div>
        <div class="admin-info">
            <h3>Menu Items</h3>
            <p><?php echo (int)$counts['menu_items']; ?></p>
        </div>
        <br>
        <a href="manage_menu.php" class="manage-btn">Manage Menu</a>
    </div>

    <div class="admin-card">
        <div class="admin-img" style="background-image: url('../assets/cards/orders.png');"></div>
        <div class="admin-info">
            <h3>Orders</h3>
            <p><?php echo (int)$counts['orders']; ?></p>
        </div>
        <br>
        <a href="manage_orders.php" class="manage-btn">Manage Orders</a>
    </div>

    <div class="admin-card">
        <div class="admin-img" style="background-image: url('../assets/cards/reservation.png');"></div>
        <div class="admin-info">
            <h3>Reservations</h3>
            <p><?php echo (int)$counts['reservations']; ?></p>
        </div>
        <br>
        <a href="manage_reservations.php" class="manage-btn">Manage Reservations</a>
    </div>

    <div class="admin-card">
        <div class="admin-img" style="background-image: url('../assets/cards/promos.png');"></div>
        <div class="admin-info">
            <h3>Promotions</h3>
            <p><?php echo (int)$counts['promos']; ?></p>
        </div>
        <br>
        <a href="manage_promos.php" class="manage-btn">Manage Promos</a>
    </div>
</div>

<a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>

<?php
require __DIR__ . '/../includes/footer.php';
?>