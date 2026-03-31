<?php
$pageClass = "udashboard-page";
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireUserLogin();
require __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];

// Fetch recent orders with item details using JOIN
$stmt = $pdo->prepare("
    SELECT o.id AS order_id, o.total_price, o.status, o.created_at,
           oi.quantity, oi.price, m.name AS item_name
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN menu_items m ON oi.menu_item_id = m.id
    WHERE o.user_id = :uid
    ORDER BY o.created_at DESC
");
$stmt->execute([':uid' => $userId]);
$ordersRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group items by order
$orders = [];
foreach ($ordersRaw as $row) {
    $oid = $row['order_id'];
    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'total_price' => $row['total_price'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'items' => []
        ];
    }
    $orders[$oid]['items'][] = [
        'name' => $row['item_name'],
        'quantity' => $row['quantity'],
        'price' => $row['price']
    ];
}

// Fetch recent reservations
$stmt = $pdo->prepare("
    SELECT id, reservation_date, reservation_time, guests, status
    FROM reservations
    WHERE user_id = :uid
    ORDER BY reservation_date DESC
    LIMIT 10
");
$stmt->execute([':uid' => $userId]);
$reservations = $stmt->fetchAll();
?>


<div class="dashboard-header">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
    <div class="dashboard-divider"></div>
    <p>Your Personal Dining Dashboard</p>
</div>

<section class="dashboard-section">
    <div class="section-header">
        <h2>Your Recent Orders</h2>
        <a href="order.php" class="dash-btn">Place Order</a>
    </div>

    <?php if ($orders): ?>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Ordered At</th>
                <th>Receipt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $oid => $order): ?>
            <tr>
                <td>Order #<?php echo (int)$oid; ?></td>
                <td>
                    <?php foreach ($order['items'] as $it): ?>
                    <?php echo htmlspecialchars($it['name']); ?>
                    (x<?php echo (int)$it['quantity']; ?>)<br>
                    <?php endforeach; ?>
                </td>

                <td>Rs <?php echo number_format($order['total_price'], 2); ?></td>
                <td><?php echo htmlspecialchars($order['status']); ?></td>
                <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                <td><a href="order_receipt.php?id=<?php echo (int)$oid; ?>" class="receipt-btn"><i
                            class="fa-solid fa-receipt"></i></a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-box">
        <p>No orders yet.</p>
    </div>
    <?php endif; ?>
</section>

<section class="dashboard-section">
    <div class="section-header">
        <h2>Your Reservations</h2>
        <a href="reservation.php" class="dash-btn">Make Reservation</a>
    </div>
    <?php if ($reservations): ?>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Time</th>
                <th>Guests</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($reservations as $r): ?>
            <tr>
                <td>TR #<?php echo (int)$r['id']; ?></td>
                <td><?php echo htmlspecialchars($r['reservation_date']); ?></td>
                <td><?php echo htmlspecialchars($r['reservation_time']); ?></td>
                <td><?php echo (int)$r['guests']; ?></td>
                <td>
                    <?php
                        $status = $r['status'];
                        $color = match($status) {
                        'Pending' => 'orange',
                        'Approved' => 'green',
                        'Cancelled' => 'red',
                        default => 'gray'
                    };
                    ?>
                    <span style="color:<?php echo $color; ?>">
                        <?php echo htmlspecialchars($status); ?>
                    </span>
                </td>
                <td>
                    <?php if ($r['status'] === 'Pending'): ?>
                        <a href="edit_reservation.php?id=<?php echo (int)$r['id']; ?>" class="receipt-btn">
                        Edit
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php else: ?>
    <div class="empty-box">
        <p>No reservations yet.</p>
    </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>