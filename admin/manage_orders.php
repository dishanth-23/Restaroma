<?php
// admin/manage_orders.php
// Admin can view all orders and update status.
$pageClass = "admindash-page";
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require __DIR__ . '/../includes/header.php';

$statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];
$success = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    verify_csrf_or_die();
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $paymentStatus = $_POST['payment_status'] ?? null;

    if (in_array($status, $statuses, true)) {
        $params = [
            ':s'  => $status,
            ':id' => $orderId
        ];

        $sql = "UPDATE orders SET status = :s";

        if (in_array($paymentStatus, ['Unpaid','Paid','Refunded'], true)) {
            $sql .= ", payment_status = :ps";
            $params[':ps'] = $paymentStatus;
        }

        $sql .= " WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $success = 'Order updated.';
    }
}


// Fetch orders with user info
$sql = "SELECT 
            o.id,
            o.total_price,
            o.status,
            o.created_at,
            o.payment_method,
            o.payment_status,
            o.payment_reference,
            u.name AS user_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC";
$orders = $pdo->query($sql)->fetchAll();

$orderItemsByOrder = [];
if (!empty($orders)) {
    $orderIds = array_map(static fn(array $o) => (int)$o['id'], $orders);
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmtItems = $pdo->prepare("
        SELECT oi.order_id, oi.quantity, oi.price, m.name, m.image
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.order_id ASC
    ");
    $stmtItems->execute($orderIds);
    foreach ($stmtItems->fetchAll() as $item) {
        $oid = (int)$item['order_id'];
        if (!isset($orderItemsByOrder[$oid])) {
            $orderItemsByOrder[$oid] = [];
        }
        $orderItemsByOrder[$oid][] = $item;
    }
}
?>
<h1>Manage Orders</h1>

<?php if ($success): ?>
<div class="alert alert-success">
    <p><?php echo htmlspecialchars($success); ?></p>
</div>
<?php endif; ?>

<table class="mtable">
    <thead>
        <tr>
            <th>#</th>
            <th>User</th>
            <th>Total (Rs)</th>
            <th>Payment Method</th>
            <th>Payment Status</th>
            <th>Status</th>
            <th>Created</th>
            <th>Items</th>
            <th>Update Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $o): ?>

        <?php $items = $orderItemsByOrder[(int)$o['id']] ?? []; ?>

        <tr>
            <td data-label="#"> <?php echo (int)$o['id']; ?> </td>

            <td data-label="User">
                <?php echo htmlspecialchars($o['user_name']); ?>
            </td>

            <td data-label="Total (Rs)">
                <?php echo number_format($o['total_price'], 2); ?>
            </td>

            <td data-label="Payment Method">
                <?php echo htmlspecialchars($o['payment_method']); ?>
            </td>

            <td data-label="Payment Status">
                <?php echo htmlspecialchars($o['payment_status']); ?>
            </td>

            <td data-label="Status">
                <?php echo htmlspecialchars($o['status']); ?>
            </td>

            <td data-label="Created">
                <?php echo htmlspecialchars($o['created_at']); ?>
            </td>

            <td data-label="Items">
                <?php foreach ($items as $it): ?>
                <div style="display:flex; align-items:center; margin-bottom:6px;">
                    <?php if ($it['image']): ?>
                    <img src="../assets/images/<?php echo htmlspecialchars($it['image']); ?>"
                         alt=""
                         style="width:30px; height:30px; object-fit:cover; border-radius:4px; margin-right:6px;">
                    <?php endif; ?>
                    <span>
                        <?php echo (int)$it['quantity']; ?> x 
                        <?php echo htmlspecialchars($it['name']); ?>
                        (Rs <?php echo number_format($it['price'], 2); ?>)
                    </span>
                </div>
                <?php endforeach; ?>
            </td>

            <td data-label="Update Status">
                <form method="post" class="status-update">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">

                    <select name="status">
                        <?php foreach ($statuses as $s): ?>
                        <option value="<?php echo $s; ?>" <?php if ($o['status'] === $s) echo 'selected'; ?>>
                            <?php echo $s; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="payment_status">
                        <option value="Unpaid" <?php if($o['payment_status']=='Unpaid') echo 'selected'; ?>>Unpaid</option>
                        <option value="Paid" <?php if($o['payment_status']=='Paid') echo 'selected'; ?>>Paid</option>
                        <option value="Refunded" <?php if($o['payment_status']=='Refunded') echo 'selected'; ?>>Refunded</option>
                    </select>

                    <button type="submit" class="save-btn">Save</button>
                </form>
            </td>
        </tr>

        <?php endforeach; ?>
    </tbody>
</table>

<?php
require __DIR__ . '/../includes/footer.php';
?>